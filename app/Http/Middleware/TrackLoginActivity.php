<?php

namespace App\Http\Middleware;

use App\Models\LoginActivity;
use App\Services\DeviceDetectorService;
use App\Services\IpGeolocationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TrackLoginActivity
{
    public function __construct(
        private IpGeolocationService $geo,
        private DeviceDetectorService $device,
    ) {}

    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }

    public function terminate(Request $request, $response): void
    {
        if (
            $request->isMethod('POST') &&
            str_contains($request->path(), 'login') &&
            Auth::check() &&
            $response->getStatusCode() === 302
        ) {
            $user      = Auth::user();
            $ip        = $request->ip();
            $ua        = $request->userAgent() ?? '';
            $geo       = $this->geo->lookup($ip);
            $device    = $this->device->detect($ua);

            LoginActivity::create([
                'user_id'     => $user->id,
                'ip_address'  => $ip,
                'country'     => $geo['country'],
                'city'        => $geo['city'],
                'device_type' => $device['device_type'],
                'browser'     => $device['browser'],
                'os'          => $device['os'],
                'user_agent'  => substr($ua, 0, 512),
                'logged_in_at' => now(),
            ]);
        }
    }
}
