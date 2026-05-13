<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IpGeolocationService
{
    public function lookup(string $ip): array
    {
        if (in_array($ip, ['127.0.0.1', '::1', 'localhost'])) {
            return ['country' => 'Local', 'city' => 'Development'];
        }

        try {
            $response = Http::timeout(3)->get("http://ip-api.com/json/{$ip}", [
                'fields' => 'status,country,city',
            ]);

            if ($response->ok() && $response->json('status') === 'success') {
                return [
                    'country' => $response->json('country'),
                    'city'    => $response->json('city'),
                ];
            }
        } catch (\Throwable $e) {
            Log::warning("IP geolocation failed for {$ip}: " . $e->getMessage());
        }

        return ['country' => null, 'city' => null];
    }
}
