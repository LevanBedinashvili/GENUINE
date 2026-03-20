<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * reCAPTCHA v2 (Checkbox) Validator
 * SECURITY: Fails closed if reCAPTCHA not configured in production
 */
class RecaptchaValidator
{
    /**
     * Validate reCAPTCHA v2 checkbox token
     * Production: Fails CLOSED if not configured (secure default)
     * Development: Allows if not configured but logs warning
     *
     * @param string $token reCAPTCHA response token from checkbox
     * @return array Response with 'success', 'challenge_ts', 'hostname'
     * @throws RuntimeException if verification fails in production
     */
    public static function validate(string $token): array
    {
        if (!$token) {
            throw new RuntimeException('reCAPTCHA token is required');
        }

        $secret = config('services.recaptcha.secret_key');

        // PRODUCTION: Must have reCAPTCHA configured
        if (config('app.env') === 'production' && !$secret) {
            Log::critical('SECURITY: reCAPTCHA not configured in production!');
            throw new RuntimeException(
                'reCAPTCHA configuration missing. Please contact support.'
            );
        }

        // DEVELOPMENT: If not configured, allow but warn
        if (!$secret) {
            Log::warning('reCAPTCHA not configured - bot protection disabled', [
                'env' => config('app.env'),
            ]);
            return [
                'success' => true,
                'challenge_ts' => now()->toIso8601String(),
                'hostname' => 'localhost',
            ];
        }

        try {
            $response = Http::asForm()
                ->timeout(10)
                ->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret' => $secret,
                    'response' => $token,
                ]);

            if (!$response->successful()) {
                Log::warning('reCAPTCHA API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new RuntimeException('reCAPTCHA API error');
            }

            $data = $response->json();

            Log::channel('payments')->info('reCAPTCHA v2 verified', [
                'success' => $data['success'] ?? false,
                'hostname' => $data['hostname'] ?? 'N/A',
                'error_codes' => $data['error-codes'] ?? [],
            ]);

            return $data;

        } catch (\Exception $e) {
            Log::channel('payments')->error('reCAPTCHA verification exception', [
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException('reCAPTCHA verification failed: ' . $e->getMessage());
        }
    }

    /**
     * Check if reCAPTCHA v2 response indicates valid user
     * For v2 checkbox, success=true is sufficient (no score)
     *
     * @param array $recaptchaResponse Response from validate()
     * @return bool True if reCAPTCHA verification passed
     */
    public static function isValid(array $recaptchaResponse): bool
    {
        return (bool) ($recaptchaResponse['success'] ?? false);
    }
}
