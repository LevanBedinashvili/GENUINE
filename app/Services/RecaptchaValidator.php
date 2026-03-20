<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * reCAPTCHA Validator
 * Validates reCAPTCHA v3 tokens with production-safe failsafe
 * SECURITY: Fails closed if reCAPTCHA not configured in production
 */
class RecaptchaValidator
{
    /**
     * Validate reCAPTCHA token
     * Production: Fails CLOSED if reCAPTCHA not configured (secure default)
     * Development: Allows if not configured but logs warning
     *
     * @param string $token reCAPTCHA token from client
     * @return array Response with 'success', 'score' (0-1), 'action', 'challenge_ts'
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
            Log::critical('SECURITY: reCAPTCHA not configured in production!', [
                'token_present' => !empty($token),
            ]);
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
                'score' => 1.0,
                'action' => 'createPayment',
                'challenge_ts' => now()->toIso8601String(),
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

            // Log verification result
            Log::channel('payments')->info('reCAPTCHA verified', [
                'success' => $data['success'] ?? false,
                'score' => $data['score'] ?? 'N/A',
                'action' => $data['action'] ?? 'N/A',
            ]);

            return $data;

        } catch (\Exception $e) {
            Log::channel('payments')->error('reCAPTCHA verification exception', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            // Fail closed - if verification fails, reject request
            throw new RuntimeException('reCAPTCHA verification failed: ' . $e->getMessage());
        }
    }

    /**
     * Check if reCAPTCHA response indicates valid user
     * Validates both success flag and score threshold
     *
     * @param array $recaptchaResponse Response from validate()
     * @param float $minScore Minimum acceptable score (0-1, default 0.5)
     * @return bool True if reCAPTCHA indicates valid user
     */
    public static function isValid(array $recaptchaResponse, float $minScore = 0.5): bool
    {
        // Must have success flag and it must be true
        if (!($recaptchaResponse['success'] ?? false)) {
            return false;
        }

        // reCAPTCHA v2 (checkbox) does not return a score — success alone is sufficient
        if (!isset($recaptchaResponse['score'])) {
            return true;
        }

        // reCAPTCHA v3 — check score meets minimum threshold
        $score = $recaptchaResponse['score'];
        if ($score < $minScore) {
            Log::channel('payments')->warning('reCAPTCHA score too low', [
                'score' => $score,
                'min_score' => $minScore,
            ]);
            return false;
        }

        return true;
    }
}
