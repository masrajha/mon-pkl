<?php

namespace App\Support;

class DeviceInfo
{
    public static function from(mixed $deviceInfo): ?array
    {
        if (is_string($deviceInfo)) {
            $decoded = json_decode($deviceInfo, true);
            $deviceInfo = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($deviceInfo)) {
            return null;
        }

        $userAgent = trim((string) data_get($deviceInfo, 'user_agent', ''));

        if ($userAgent === '') {
            return null;
        }

        $ua = strtolower($userAgent);
        $device = [
            'key' => 'unknown',
            'label' => 'Perangkat',
            'icon' => 'fa-circle-question',
            'icon_style' => 'solid',
        ];

        if (str_contains($ua, 'iphone')) {
            $device = ['key' => 'iphone', 'label' => 'iPhone', 'icon' => 'fa-apple', 'icon_style' => 'brands'];
        } elseif (str_contains($ua, 'ipad')) {
            $device = ['key' => 'ipad', 'label' => 'iPad', 'icon' => 'fa-apple', 'icon_style' => 'brands'];
        } elseif (str_contains($ua, 'android')) {
            $device = ['key' => 'android', 'label' => 'Android', 'icon' => 'fa-android', 'icon_style' => 'brands'];
        } elseif (str_contains($ua, 'windows')) {
            $device = ['key' => 'windows', 'label' => 'Windows', 'icon' => 'fa-windows', 'icon_style' => 'brands'];
        } elseif (str_contains($ua, 'macintosh') || str_contains($ua, 'mac os')) {
            $device = ['key' => 'macos', 'label' => 'macOS', 'icon' => 'fa-apple', 'icon_style' => 'brands'];
        } elseif (str_contains($ua, 'cros')) {
            $device = ['key' => 'chromeos', 'label' => 'ChromeOS', 'icon' => 'fa-chrome', 'icon_style' => 'brands'];
        } elseif (str_contains($ua, 'linux')) {
            $device = ['key' => 'linux', 'label' => 'Linux', 'icon' => 'fa-linux', 'icon_style' => 'brands'];
        }

        $device['icon_class'] = 'fa-'.$device['icon_style'].' '.$device['icon'];
        $device['user_agent'] = $userAgent;

        return $device;
    }
}
