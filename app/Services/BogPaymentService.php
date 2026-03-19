<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

/**
 * Bank of Georgia Payment Service
 * Implements BOG Payments API v1
 * 
 * API Documentation: https://api.bog.ge/docs/payments/
 */
class BogPaymentService
{
    private string $clientId;
    private string $clientSecret;
    private string $publicKey;
    private string $baseUrl = 'https://api.bog.ge/payments/v1';
    private string $authUrl = 'https://oauth2.bog.ge/auth/realms/bog/protocol/openid-connect/token';
    private string $paymentUrl = 'https://payment.bog.ge';
    private bool $testMode = false;

    public function __construct()
    {
        $this->clientId = config('bog.client_id') ?? env('BOG_CLIENT_ID');
        $this->clientSecret = config('bog.client_secret') ?? env('BOG_CLIENT_SECRET');
        $this->publicKey = config('bog.public_key') ?? env('BOG_PUBLIC_KEY');
        $this->testMode = config('bog.test_mode') ?? env('BOG_TEST_MODE', false);
    }

    /**
     * Generate a UUID v4
     * 
     * @return string UUID v4 format
     */
    private function generateUuid4(): string
    {
        // Generate 16 bytes (128 bits) of random data
        $data = random_bytes(16);
        
        // Set version (4) and variant bits
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // version 4
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // variant bits
        
        // Convert to hexadecimal string with hyphens
        return sprintf(
            '%08x-%04x-%04x-%04x-%12x',
            unpack('N', substr($data, 0, 4))[1],
            unpack('n', substr($data, 4, 2))[1],
            unpack('n', substr($data, 6, 2))[1],
            unpack('n', substr($data, 8, 2))[1],
            unpack('N', substr($data, 10, 6))[1] . unpack('n', substr($data, 14, 2))[1]
        );
    }

    /**
     * Get OAuth2 access token using client credentials
     * Token is cached for performance (expires_in - 60 seconds buffer)
     * 
     * @return string Access token
     * @throws Exception
     */
    public function getAccessToken(): string
    {
        // Check cache first
        $cacheKey = 'bog_access_token_' . md5($this->clientId);
        $cachedToken = Cache::get($cacheKey);
        
        if ($cachedToken) {
            return $cachedToken;
        }

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])
            ->withBasicAuth($this->clientId, $this->clientSecret)
            ->asForm()
            ->post($this->authUrl, [
                'grant_type' => 'client_credentials',
            ]);

            if (!$response->successful()) {
                $errorBody = $response->body();
                Log::channel('payments')->error('BOG OAuth failed', [
                    'status' => $response->status(),
                    'body' => $errorBody,
                ]);
                throw new Exception('BOG OAuth failed: ' . $response->status() . ' - ' . $errorBody);
            }

            $data = $response->json();
            $token = $data['access_token'] ?? null;
            $expiresIn = $data['expires_in'] ?? 300;

            if (!$token) {
                throw new Exception('Access token not found in BOG response');
            }

            // Cache token with 60 second buffer before expiry
            $cacheSeconds = max(1, $expiresIn - 60);
            Cache::put($cacheKey, $token, $cacheSeconds);

            Log::channel('payments')->debug('BOG access token obtained', [
                'expires_in' => $expiresIn,
                'cached_for' => $cacheSeconds,
            ]);

            return $token;

        } catch (Exception $e) {
            Log::channel('payments')->error('Failed to get BOG access token', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Create a new payment order
     * 
     * @param array $orderData Order details
     *   - callback_url: string (required) - Callback URL for payment status
     *   - external_order_id: string (optional) - Your internal order ID
     *   - purchase_units: array (required) - Order details
     *     - currency: string (default: GEL)
     *     - total_amount: float (required)
     *     - basket: array (required) - Items
     *       - product_id: string (required)
     *       - quantity: number (required)
     *       - unit_price: number (required)
     *       - description: string (optional)
     *   - redirect_urls: array (required)
     *     - success: string (required)
     *     - fail: string (required)
     *   - buyer: array (optional)
     *     - full_name: string (optional)
     *     - masked_email: string (optional)
     *     - masked_phone: string (optional)
     *   - capture: string (optional) - 'automatic' or 'manual'
     *   - application_type: string (optional) - 'web' or 'mobile'
     * @param array $options Additional options
     *   - language: string (default: ka) - 'ka' or 'en'
     *   - theme: string (default: light) - 'light' or 'dark'
     * @return array API response with order_id and redirect URL
     * @throws Exception
     */
    public function createOrder(array $orderData, array $options = []): array
    {
        try {
            // Generate proper UUID v4 for Idempotency-Key
            $idempotencyKey = $this->generateUuid4();
            $language = $options['language'] ?? 'ka';
            $theme = $options['theme'] ?? 'light';

            // Validate required fields
            if (empty($orderData['callback_url'])) {
                throw new Exception('callback_url is required');
            }
            if (empty($orderData['purchase_units']['total_amount'])) {
                throw new Exception('purchase_units.total_amount is required');
            }
            if (empty($orderData['purchase_units']['basket'])) {
                throw new Exception('purchase_units.basket is required');
            }
            if (empty($orderData['redirect_urls']['success']) || empty($orderData['redirect_urls']['fail'])) {
                throw new Exception('redirect_urls.success and redirect_urls.fail are required');
            }

            // Build request payload
            $payload = [
                'callback_url' => $orderData['callback_url'],
                'purchase_units' => [
                    'currency' => $orderData['purchase_units']['currency'] ?? 'GEL',
                    'total_amount' => (float) $orderData['purchase_units']['total_amount'],
                    'basket' => $this->buildBasket($orderData['purchase_units']['basket']),
                ],
                'redirect_urls' => [
                    'success' => $orderData['redirect_urls']['success'],
                    'fail' => $orderData['redirect_urls']['fail'],
                ],
            ];

            // Add optional fields
            if (!empty($orderData['external_order_id'])) {
                $payload['external_order_id'] = (string) $orderData['external_order_id'];
            }
            if (!empty($orderData['buyer'])) {
                $payload['buyer'] = $this->buildBuyer($orderData['buyer']);
            }
            if (!empty($orderData['capture'])) {
                $payload['capture'] = in_array($orderData['capture'], ['automatic', 'manual']) 
                    ? $orderData['capture'] 
                    : 'automatic';
            }
            if (!empty($orderData['application_type'])) {
                $payload['application_type'] = in_array($orderData['application_type'], ['web', 'mobile'])
                    ? $orderData['application_type']
                    : 'web';
            }

            Log::channel('payments')->info('Creating BOG payment order', [
                'external_order_id' => $orderData['external_order_id'] ?? null,
                'amount' => $payload['purchase_units']['total_amount'],
                'currency' => $payload['purchase_units']['currency'],
            ]);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
                'Idempotency-Key' => $idempotencyKey,
                'Accept-Language' => $language,
                'Theme' => $theme,
            ])->post("{$this->baseUrl}/ecommerce/orders", $payload);

            if (!$response->successful()) {
                $errorBody = $response->body();
                Log::channel('payments')->error('BOG order creation failed', [
                    'status' => $response->status(),
                    'body' => $errorBody,
                    'payload' => $payload,
                ]);
                throw new Exception('Failed to create BOG order: ' . $response->status() . ' - ' . $errorBody);
            }

            $result = $response->json();

            Log::channel('payments')->info('BOG order created successfully', [
                'order_id' => $result['id'] ?? null,
                'external_order_id' => $orderData['external_order_id'] ?? null,
            ]);

            // Add convenience fields to result
            $result['redirect_url'] = $result['_links']['redirect']['href'] ?? null;
            $result['details_url'] = $result['_links']['details']['href'] ?? null;

            return $result;

        } catch (Exception $e) {
            Log::channel('payments')->error('BOG order creation error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Get order details by order ID
     * 
     * @param string $orderId BOG order ID
     * @return array Order details with normalized status
     * @throws Exception
     */
    public function getOrderDetails(string $orderId): array
    {
        try {
            Log::channel('payments')->debug('Fetching order details', ['order_id' => $orderId]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
            ])->get("{$this->baseUrl}/receipt/{$orderId}");

            if (!$response->successful()) {
                $errorBody = $response->body();
                Log::channel('payments')->error('Failed to fetch order details', [
                    'order_id' => $orderId,
                    'status' => $response->status(),
                    'body' => $errorBody,
                ]);
                throw new Exception('Failed to fetch order: ' . $response->status());
            }

            $data = $response->json();

            // Normalize status data
            if (isset($data['order_status'])) {
                if (is_array($data['order_status'])) {
                    $data['order_status']['key'] = $data['order_status']['key'] ?? $data['order_status']['status'] ?? 'unknown';
                } else {
                    $data['order_status'] = [
                        'key' => $data['order_status'],
                        'status' => $data['order_status'],
                    ];
                }
            }

            Log::channel('payments')->info('Order details fetched', [
                'order_id' => $orderId,
                'status' => $data['order_status']['key'] ?? 'unknown',
            ]);

            return $data;

        } catch (Exception $e) {
            Log::channel('payments')->error('Order details fetch error', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Verify callback signature from BOG
     * Uses SHA256withRSA algorithm with BOG's public key
     * 
     * @param string $signature Base64 encoded signature from Callback-Signature header
     * @param string $payload Raw request body (JSON string)
     * @return bool True if signature is valid
     */
    public function verifyCallbackSignature(string $signature, string $payload): bool
    {
        try {
            if (empty($this->publicKey)) {
                Log::channel('payments')->warning('BOG public key not configured, skipping signature verification');
                return true; // In development, allow if no key configured
            }

            // Format public key if needed
            $publicKey = $this->formatPublicKey($this->publicKey);

            // Decode base64 signature
            $decodedSignature = base64_decode($signature, true);
            if ($decodedSignature === false) {
                Log::channel('payments')->error('Failed to decode callback signature');
                return false;
            }

            // Verify signature using SHA256 with RSA
            $result = openssl_verify($payload, $decodedSignature, $publicKey, OPENSSL_ALGO_SHA256);

            if ($result !== 1) {
                Log::channel('payments')->warning('BOG callback signature verification failed', [
                    'openssl_error' => openssl_error_string(),
                ]);
                return false;
            }

            Log::channel('payments')->debug('BOG callback signature verified successfully');
            return true;

        } catch (Exception $e) {
            Log::channel('payments')->error('Callback signature verification error', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Parse callback data from BOG webhook
     * 
     * Handles both callback format and order details response format
     * 
     * @param array $callbackData Decoded JSON payload from BOG
     * @return array Normalized payment data with safe null coalescing
     */
    public function parseCallback(array $callbackData): array
    {
        try {
            $event = $callbackData['event'] ?? null;
            $body = $callbackData['body'] ?? $callbackData; // Body might be root level

            // Extract status - try multiple possible locations
            $statusData = $body['order_status'] ?? null;
            if (is_array($statusData)) {
                $status = $statusData['key'] ?? $statusData['status'] ?? null;
            } else {
                $status = $statusData;
            }

            // Extract payment method - try multiple locations
            $paymentMethod = null;
            if (!empty($body['payment_detail']['payment_method']['type'])) {
                $paymentMethod = $body['payment_detail']['payment_method']['type'];
            } elseif (!empty($body['payment_method']['type'])) {
                $paymentMethod = $body['payment_method']['type'];
            } elseif (!empty($body['payment_method'])) {
                $paymentMethod = $body['payment_method'];
            }

            $parsed = [
                'event' => $event,
                'order_id' => $body['order_id'] ?? $body['id'] ?? null,
                'external_order_id' => $body['external_order_id'] ?? null,
                'status' => $status,
                'amount' => $body['purchase_units']['total_amount'] ?? $body['total_amount'] ?? null,
                'currency' => $body['purchase_units']['currency'] ?? $body['currency'] ?? 'GEL',
                'payment_method' => $paymentMethod,
                'transaction_id' => $body['payment_order_id'] ?? null,
                'processed_at' => $body['processed_at'] ?? null,
                'raw_data' => $callbackData,
            ];

            Log::channel('payments')->debug('Callback parsed', $parsed);
            return $parsed;

        } catch (\Throwable $e) {
            Log::channel('payments')->error('Callback parsing error', [
                'error' => $e->getMessage(),
                'data' => $callbackData,
            ]);
            
            return [
                'event' => null,
                'order_id' => null,
                'external_order_id' => null,
                'status' => 'unknown',
                'amount' => null,
                'currency' => 'GEL',
                'payment_method' => null,
                'raw_data' => $callbackData,
            ];
        }
    }

    /**
     * Build basket items for API payload
     */
    private function buildBasket(array $items): array
    {
        $basket = [];
        foreach ($items as $item) {
            $basket[] = [
                'product_id' => (string) ($item['product_id'] ?? uniqid('prod_', true)),
                'quantity' => (float) ($item['quantity'] ?? 1),
                'unit_price' => (float) ($item['unit_price'] ?? 0),
                'description' => $item['description'] ?? null,
            ];
        }
        return $basket;
    }

    /**
     * Build buyer object for API payload
     */
    private function buildBuyer(array $buyer): array
    {
        $result = [];
        if (!empty($buyer['full_name'])) {
            $result['full_name'] = $buyer['full_name'];
        }
        if (!empty($buyer['masked_email'])) {
            $result['masked_email'] = $buyer['masked_email'];
        }
        if (!empty($buyer['masked_phone'])) {
            $result['masked_phone'] = $buyer['masked_phone'];
        }
        return $result;
    }

    /**
     * Format public key for openssl functions
     */
    private function formatPublicKey(string $key): string
    {
        // If key already has PEM headers, return as-is
        if (strpos($key, '-----BEGIN') !== false) {
            return $key;
        }

        // Otherwise, wrap in RSA PUBLIC KEY format
        return "-----BEGIN PUBLIC KEY-----\n" .
               chunk_split($key, 64, "\n") .
               "-----END PUBLIC KEY-----";
    }

    /**
     * Get the payment page URL for an order
     */
    public function getPaymentPageUrl(string $orderId): string
    {
        return "{$this->paymentUrl}/?order_id={$orderId}";
    }

    /**
     * Check if service is in test mode
     */
    public function isTestMode(): bool
    {
        return $this->testMode;
    }
}
