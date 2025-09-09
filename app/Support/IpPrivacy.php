<?php
namespace App\Support;

class IpPrivacy {
    public static function hmac(?string $ip): string {
        $ip = $ip ?: '0.0.0.0';
        $secret = config('app.ip_hmac_secret');
        if (!$secret) {
            $secret = config('app.key') ?: 'dev';
        }
        return hash_hmac('sha256', $ip, (string)$secret);
    }
}
