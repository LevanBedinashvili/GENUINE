<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Bank of Georgia Payment Signature Validator
 * 
 * Verifies BOG payment callback signatures to prevent fraudulent payment notifications
 * Implements HMAC-SHA256 verification as per BOG API documentation
 * 
 * SECURITY CRITICAL:
 * - All payment callbacks MUST be verified before processing
 * - Protects against man-in-the-middle attacks
 * - Prevents unauthorized payment confirmation
 */
class BogPaymentSignatureValidator
{
    private string $publicKey;

    public function __construct()
    {
        $this->publicKey = config('bog.public_key') ?? env('BOG_PUBLIC_KEY', '');
    }

    /**
     * Verify BOG payment callback signature
     * 
     * HMAC-SHA256 signature verification:
     * Signature is computed over: transaction_id|amount|currency|status
     * 
     * @param array $data Payment callback data
     * @param string $signature Signature from BOG
     * @return bool True if signature is valid, false otherwise
     * @throws Exception
     */
    public function verify(array $data, string $signature): bool
    {
        // Validate required fields
        $required = ['transaction_id', 'amount', 'currency', 'status'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                Log::warning('BOG callback validation failed: missing field', [
                    'field' => $field,
                    'data_keys' => array_keys($data),
                ]);
                return false;
            }
        }

        // Build the string to verify - order matters!
        $verifyString = implode('|', [
            (string) $data['transaction_id'],
            (string) $data['amount'],
            (string) $data['currency'],
            (string) $data['status'],
        ]);

        // Compute expected signature using BOG public key
        $expectedSignature = $this->computeSignature($verifyString);

        // Compare signatures using constant-time comparison to prevent timing attacks
        if (!hash_equals($expectedSignature, $signature)) {
            Log::warning('BOG callback signature verification failed', [
                'transaction_id' => $data['transaction_id'] ?? 'unknown',
                'expected_signature' => $expectedSignature,
                'provided_signature' => $signature,
            ]);
            return false;
        }

        Log::info('BOG callback signature verified successfully', [
            'transaction_id' => $data['transaction_id'],
        ]);

        return true;
    }

    /**
     * Compute HMAC-SHA256 signature
     * 
     * @param string $message Message to sign
     * @return string Hexadecimal signature
     */
    private function computeSignature(string $message): string
    {
        if (empty($this->publicKey)) {
            throw new Exception('BOG public key not configured');
        }

        return hash_hmac('sha256', $message, $this->publicKey);
    }

    /**
     * Validate payment status transition
     * 
     * Ensures status changes follow valid transitions:
     * - pending -> succeeded, failed, or cancelled
     * - succeeded -> cannot change (payment complete)
     * - failed -> cannot change
     * - cancelled -> cannot change
     * 
     * @param string $currentStatus Current transaction status in database
     * @param string $newStatus New status from BOG callback
     * @return bool True if transition is valid
     */
    public function isValidStatusTransition(string $currentStatus, string $newStatus): bool
    {
        // Define valid state transitions
        $validTransitions = [
            'pending' => ['succeeded', 'failed', 'cancelled'],
            'succeeded' => [],  // Terminal state
            'failed' => [],     // Terminal state
            'cancelled' => [],  // Terminal state
        ];

        // Check if transition is allowed
        $allowed = $validTransitions[$currentStatus] ?? [];
        $isValid = in_array($newStatus, $allowed);

        if (!$isValid) {
            Log::warning('Invalid payment status transition attempted', [
                'current_status' => $currentStatus,
                'new_status' => $newStatus,
            ]);
        }

        return $isValid;
    }
}
