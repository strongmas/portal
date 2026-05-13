<?php

namespace App\Services;

use App\Models\GoogleCalendarToken;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleCalendarService
{
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;

    public function __construct()
    {
        $this->clientId     = config('services.google.client_id', '');
        $this->clientSecret = config('services.google.client_secret', '');
        $this->redirectUri  = config('services.google.redirect_uri', url('/calendar/callback'));
    }

    public function getAuthUrl(string $state): string
    {
        $params = http_build_query([
            'client_id'     => $this->clientId,
            'redirect_uri'  => $this->redirectUri,
            'response_type' => 'code',
            'scope'         => 'https://www.googleapis.com/auth/calendar.events',
            'access_type'   => 'offline',
            'prompt'        => 'consent',
            'state'         => $state,
        ]);

        return "https://accounts.google.com/o/oauth2/v2/auth?{$params}";
    }

    public function exchangeCode(string $code): ?array
    {
        try {
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'code'          => $code,
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
                'redirect_uri'  => $this->redirectUri,
                'grant_type'    => 'authorization_code',
            ]);

            if ($response->ok()) {
                return $response->json();
            }
        } catch (\Throwable $e) {
            Log::error('Google Calendar token exchange failed: ' . $e->getMessage());
        }

        return null;
    }

    public function refreshToken(GoogleCalendarToken $token): bool
    {
        try {
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
                'refresh_token' => $token->refresh_token,
                'grant_type'    => 'refresh_token',
            ]);

            if ($response->ok()) {
                $data = $response->json();
                $token->update([
                    'access_token' => $data['access_token'],
                    'expires_at'   => now()->addSeconds($data['expires_in'] - 60),
                ]);
                return true;
            }
        } catch (\Throwable $e) {
            Log::error('Google Calendar token refresh failed: ' . $e->getMessage());
        }

        return false;
    }

    public function createEvent(User $user, array $eventData): ?string
    {
        $token = $user->googleCalendarToken;
        if (!$token) return null;

        if ($token->isExpired() && $token->refresh_token) {
            if (!$this->refreshToken($token)) return null;
            $token->refresh();
        }

        try {
            $response = Http::withToken($token->access_token)
                ->post('https://www.googleapis.com/calendar/v3/calendars/primary/events', $eventData);

            if ($response->ok()) {
                return $response->json('id');
            }
        } catch (\Throwable $e) {
            Log::error('Google Calendar event creation failed: ' . $e->getMessage());
        }

        return null;
    }

    public function deleteEvent(User $user, string $eventId): void
    {
        $token = $user->googleCalendarToken;
        if (!$token) return;

        if ($token->isExpired() && $token->refresh_token) {
            $this->refreshToken($token);
            $token->refresh();
        }

        try {
            Http::withToken($token->access_token)
                ->delete("https://www.googleapis.com/calendar/v3/calendars/primary/events/{$eventId}");
        } catch (\Throwable $e) {
            Log::warning('Google Calendar event deletion failed: ' . $e->getMessage());
        }
    }
}
