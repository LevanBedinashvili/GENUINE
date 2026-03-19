<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class AuditLogger
{
    /**
     * Log shop validation attempt
     */
    public static function logValidationAttempt(array $data): void
    {
        $logData = [
            'type' => 'shop_validation',
            'timestamp' => now()->toIso8601String(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'username' => $data['username'] ?? null,
            'amount' => $data['amount'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'result' => $data['result'] ?? null,
            'message' => $data['message'] ?? null,
        ];

        Log::channel('shop')->info('Validation Attempt', $logData);
    }

    /**
     * Log validation error
     */
    public static function logValidationError(string $error, array $context = []): void
    {
        $logData = array_merge([
            'type' => 'validation_error',
            'timestamp' => now()->toIso8601String(),
            'ip_address' => request()->ip(),
            'error' => $error,
        ], $context);

        Log::channel('shop')->error('Validation Error', $logData);
    }

    /**
     * Log suspicious activity
     */
    public static function logSuspiciousActivity(string $reason, array $data = []): void
    {
        $logData = array_merge([
            'type' => 'suspicious_activity',
            'timestamp' => now()->toIso8601String(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'reason' => $reason,
        ], $data);

        Log::channel('security')->warning('Suspicious Activity Detected', $logData);
    }

    /**
     * Log API request
     */
    public static function logApiRequest(string $endpoint, array $params = [], string $result = 'success'): void
    {
        $logData = [
            'type' => 'api_request',
            'timestamp' => now()->toIso8601String(),
            'endpoint' => $endpoint,
            'ip_address' => request()->ip(),
            'method' => request()->method(),
            'params' => $params,
            'result' => $result,
        ];

        Log::channel('api')->info('API Request', $logData);
    }
}
