<?php

namespace App\Http\Controllers;

use App\Models\GoogleCalendarToken;
use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GoogleCalendarController extends Controller
{
    public function __construct(private readonly GoogleCalendarService $calendar) {}

    public function redirect()
    {
        $state = Str::random(40);
        session(['google_oauth_state' => $state]);

        return redirect($this->calendar->getAuthUrl($state));
    }

    public function callback(Request $request)
    {
        if ($request->state !== session('google_oauth_state')) {
            return redirect()->route('profile')->with('error', 'Invalid OAuth state. Please try again.');
        }

        if ($request->filled('error')) {
            return redirect()->route('profile')->with('error', 'Google Calendar access was denied.');
        }

        $tokens = $this->calendar->exchangeCode($request->code);

        if (!$tokens) {
            return redirect()->route('profile')->with('error', 'Could not connect to Google Calendar. Please try again.');
        }

        GoogleCalendarToken::updateOrCreate(
            ['user_id' => auth()->id()],
            [
                'access_token'  => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'] ?? null,
                'token_type'    => $tokens['token_type'] ?? 'Bearer',
                'expires_at'    => now()->addSeconds(($tokens['expires_in'] ?? 3600) - 60),
            ]
        );

        return redirect()->route('profile')->with('success', 'Google Calendar connected successfully!');
    }

    public function disconnect()
    {
        auth()->user()->googleCalendarToken?->delete();
        return back()->with('success', 'Google Calendar disconnected.');
    }
}
