<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ShopRateLimit
{
    private $blockedIps = [
        '8.209.235.244',        // Most aggressive - 50+ requests
        '111.31.152.194',       // High frequency attacker
        '111.31.142.228',       // Repeated attacks
        '100.42.228.18',        // Multiple suspicious attempts
        '100.42.228.50',        // Multiple suspicious attempts
        '100.42.228.194',       // Multiple suspicious attempts
        '205.185.126.243',      // Suspicious username attempts
        '38.244.11.68',         // Suspicious username attempts
        '103.144.90.14',        // Suspicious username attempts
        '177.54.40.98',         // Suspicious username attempts
        '47.243.176.188',       // Suspicious username attempts
        '89.23.123.132',        // Suspicious username attempts
        '106.75.155.94',        // Suspicious username attempts
        '154.62.127.157',       // Suspicious username attempts
        '221.217.163.135',      // Suspicious username attempts
        '213.148.26.194',       // Suspicious username attempts
        '104.28.254.46',        // Suspicious username attempts
        '170.78.97.83',         // Suspicious username attempts
        '189.193.231.170',      // Suspicious username attempts
        '47.84.16.234',         // Suspicious username attempts
        '154.12.54.162',       // Suspicious username attempts
        '157.20.207.25',       // Suspicious username attempts
        '103.106.113.13',      // Suspicious username attempts
        '45.144.177.194',      // Suspicious username attempts
        '201.151.68.22',       // Suspicious username attempts
        '77.73.238.98',        // Suspicious username attempts
        '109.224.242.105',     // Suspicious username attempts
        '204.141.229.236',     // Suspicious username attempts
        '185.116.43.7',        // Suspicious username attempts
        '161.248.201.226',     // Suspicious username attempts
        '103.165.157.250',     // Suspicious username attempts
        '91.203.61.52',        // Suspicious username attempts
        '185.230.7.214',       // Suspicious username attempts
        '157.15.0.151',        // Suspicious username attempts
        '154.19.38.251',       // Suspicious username attempts
        '43.163.109.136',      // Suspicious username attempts
        '181.177.233.98',      // Suspicious username attempts
        '92.124.222.138',      // Suspicious username attempts
        '45.64.176.168',       // Suspicious username attempts
        '52.229.164.162',      // Suspicious username attempts
        '163.192.34.52',       // Suspicious username attempts
        '108.61.161.138',      // Suspicious username attempts
        '111.119.219.142',     // Suspicious username attempts
        '192.162.238.243',     // Suspicious username attempts
        '91.243.71.22',        // Suspicious username attempts
        '79.104.197.62',       // Suspicious username attempts
        '107.175.101.231',     // Suspicious username attempts
        '111.31.142.228',      // Suspicious username attempts
        '202.49.176.24',       // Suspicious username attempts
        '121.101.129.131',     // Suspicious username attempts
        '103.14.227.99',       // Suspicious username attempts
        '176.88.6.213',        // Suspicious username attempts
        '198.40.53.58',        // Suspicious username attempts
        '186.148.179.53',      // Suspicious username attempts
        '38.147.191.16',       // Suspicious username attempts
        '47.242.154.76',       // Suspicious username attempts
        '124.220.10.20',       // Suspicious username attempts
        '27.147.131.50',       // Suspicious username attempts
        '103.174.64.247',      // Suspicious username attempts
        '46.101.251.27',       // Suspicious username attempts
        '128.85.48.167',       // Suspicious username attempts
        '210.87.92.49',        // Suspicious username attempts
        '103.146.123.98',      // Suspicious username attempts
        '206.85.1.104',        // Suspicious username attempts
        '103.172.28.40',       // Suspicious username attempts
        '103.90.211.126',      // Suspicious username attempts
        '45.64.176.203',      // Suspicious username attempts
        '31.56.177.20',        // Suspicious username attempts
        '163.47.158.61',       // Suspicious username attempts
        '90.221.120.195',      // Suspicious username attempts
        '45.173.12.140',       // Suspicious username attempts
        '58.243.105.106',      // Suspicious username attempts
        '45.174.243.192',      // Suspicious username attempts
        '104.28.219.139',      // Suspicious username attempts
        '35.237.28.196',       // Suspicious username attempts
        '110.42.10.241',       // Suspicious username attempts
        '82.115.72.39',        // Suspicious username attempts
        '2.56.173.4',          // Suspicious username attempts
        '117.86.177.38',       // Suspicious username attempts
        '103.191.73.2',        // Suspicious username attempts
        '187.251.117.243',     // Suspicious username attempts
        '201.163.218.34',      // Suspicious username attempts
        '157.15.67.86',        // Suspicious username attempts
        '94.206.41.42',        // Suspicious username attempts
        '188.168.24.45',       // Suspicious username attempts
        '187.150.208.146',     // Suspicious username attempts
        '121.36.4.177',        // Suspicious username attempts
        '210.4.65.181',        // Suspicious username attempts
        '152.32.202.225',      // Suspicious username attempts
        '64.71.175.26',        // Suspicious username attempts
        '151.237.60.203',      // Suspicious username attempts
    ];

    public function handle(Request $request, Closure $next)
    {
        $ip = $request->ip();
        
        if (in_array($ip, $this->blockedIps)) {
            Log::channel('payments')->warning('Blocked IP attempted access', [
                'ip' => $ip,
                'path' => $request->path(),
                'user_agent' => $request->userAgent(),
            ]);
            
            return response()->json([
                'error' => 'Access denied',
                'message' => 'Your IP has been blocked due to suspicious activity'
            ], 403);
        }
        
        $blockKey = "shop_blocked_{$ip}";
        if (Cache::has($blockKey)) {
            Log::channel('payments')->warning('Auto-blocked IP attempted access', [
                'ip' => $ip,
                'path' => $request->path(),
                'blocked_until' => Cache::get($blockKey),
            ]);
            
            return response()->json([
                'error' => 'Access denied',
                'message' => 'Your IP has been temporarily blocked due to excessive requests'
            ], 403);
        }
        
        $key = "shop_global_{$ip}";
        
        if (Cache::has($key) && Cache::get($key) > 25) {
            Log::channel('payments')->warning('IP globally rate limited', [
                'ip' => $ip,
                'requests' => Cache::get($key),
                'path' => $request->path(),
                'user_agent' => $request->userAgent(),
            ]);
            
            Cache::put($blockKey, now()->addMinutes(5)->timestamp, 300);
            
            return response()->json([
                'error' => 'Too many requests',
                'message' => 'Rate limit exceeded. Your IP has been temporarily blocked.'
            ], 429);
        }
        
        $currentCount = Cache::get($key, 0);
        Cache::put($key, $currentCount + 1, 60); // 1 minute expiration
        
        $userAgent = strtolower($request->userAgent());
        $suspiciousPatterns = [
            'bot', 'crawler', 'spider', 'scraper', 'curl', 'wget', 
            'python', 'java', 'perl', 'ruby', 'php', 'node',
            'go-http', 'okhttp', 'axios', 'postman', 'insomnia'
        ];
        
        return $next($request);
    }
}
