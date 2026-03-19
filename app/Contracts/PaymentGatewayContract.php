<?php

namespace App\Contracts;

use Exception;

/**
 * Payment Gateway Contract
 * Defines interface for payment gateway implementations
 * Allows switching between BOG, Stripe, PayPal, etc.
 */
interface PaymentGatewayContract
{
    /**
     * Create a new payment order
     *
     * @param array $data Order data with structure:
     *        [
     *            'external_order_id' => string,
     *            'amount' => float|string,
     *            'currency' => 'GEL',
     *            'description' => string,
     *            'callback_url' => string,
     *            'redirect_urls' => ['success' => url, 'fail' => url],
     *            'buyer' => ['full_name' => string]
     *        ]
     * @param array $options Display options (language, theme, etc.)
     * @return array Order response with 'id' and 'redirect_url'
     * @throws Exception
     */
    public function createOrder(array $data, array $options = []): array;

    /**
     * Get order details from payment gateway
     *
     * @param string $orderId External order ID from gateway
     * @return array Order details with status, amount, etc.
     * @throws Exception
     */
    public function getOrderDetails(string $orderId): array;

    /**
     * Verify payment callback signature
     *
     * @param array $data Callback payload data
     * @param string $signature Signature from callback
     * @return bool True if signature is valid
     */
    public function verifySignature(array $data, string $signature): bool;
}
