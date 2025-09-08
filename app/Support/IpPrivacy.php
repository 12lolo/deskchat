<?php
namespace App\Support;

class IpPrivacy {
    public static function hmac(string $ip): string {
        $secret = config('app.ip_hmac_secret');
        return hash_hmac('sha256', $ip, $secret);
    }
}
