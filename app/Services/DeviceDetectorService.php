<?php

namespace App\Services;

class DeviceDetectorService
{
    public function detect(string $userAgent): array
    {
        return [
            'device_type' => $this->detectDeviceType($userAgent),
            'browser'     => $this->detectBrowser($userAgent),
            'os'          => $this->detectOs($userAgent),
        ];
    }

    private function detectDeviceType(string $ua): string
    {
        $ua = strtolower($ua);
        if (str_contains($ua, 'mobile') || str_contains($ua, 'android') && !str_contains($ua, 'tablet')) {
            return 'Mobile';
        }
        if (str_contains($ua, 'tablet') || str_contains($ua, 'ipad')) {
            return 'Tablet';
        }
        return 'Desktop';
    }

    private function detectBrowser(string $ua): string
    {
        return match(true) {
            str_contains($ua, 'Edg/')     => 'Edge',
            str_contains($ua, 'OPR/')     => 'Opera',
            str_contains($ua, 'Chrome/')  => 'Chrome',
            str_contains($ua, 'Firefox/') => 'Firefox',
            str_contains($ua, 'Safari/')  => 'Safari',
            default                       => 'Unknown Browser',
        };
    }

    private function detectOs(string $ua): string
    {
        return match(true) {
            str_contains($ua, 'Windows')      => 'Windows',
            str_contains($ua, 'Mac OS X')     => 'macOS',
            str_contains($ua, 'Linux')        => 'Linux',
            str_contains($ua, 'Android')      => 'Android',
            str_contains($ua, 'iPhone') || str_contains($ua, 'iPad') => 'iOS',
            default                           => 'Unknown OS',
        };
    }
}
