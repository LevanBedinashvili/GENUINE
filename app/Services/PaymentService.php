<?php

namespace App\Services;

use App\Contracts\PaymentGatewayContract;
use App\Models\Transaction;
use App\Models\ShopItem;
use App\Models\Account;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

/**
 * Payment Service
 * Orchestrates payment creation and status management
 * Extracted from controller for better testability and separation of concerns
 */
class PaymentService
{
    public function __construct(
        private PaymentGatewayContract $gateway,
        private DashboardStatsService $statsService
    ) {
    }

    /**
     * Create a new payment order
     * Coordinates: reCAPTCHA → Validation → Transaction creation → BOG order
     *
     * @param array $validated Validated input from request
     * @return array Success response with redirect_url and transaction_id
     * @throws Exception
     */
    public function createPayment(array $validated): array
    {
        // 1. Verify reCAPTCHA
        $recaptchaResponse = RecaptchaValidator::validate(
            $validated['recaptcha_token'] ?? ''
        );

        if (!RecaptchaValidator::isValid($recaptchaResponse)) {
            throw new Exception('reCAPTCHA verification failed');
        }

        // 2. Validate shop item exists
        $shopItem = ShopItem::findOrFail($validated['shop_item_id']);

        // 3. Validate account exists (case-insensitive)
        $account = Account::byPlayerName($validated['username'])->first();
        if (!$account) {
            Log::channel('payments')->warning('Payment attempt with non-existent account', [
                'username' => $validated['username'],
            ]);
            throw new Exception('Account not found');
        }

        // 4. Validate amount format
        if (!MoneyValidator::isValid($validated['amount'])) {
            throw new Exception('Invalid amount');
        }

        // 5. Create transaction and BOG order in single transaction
        $result = DB::transaction(function () use ($validated, $shopItem, $account) {
            $transaction = $this->createTransaction($validated, $shopItem, $account);
            
            // 6. Create order with payment gateway
            $bogOrder = $this->gateway->createOrder(
                $this->buildGatewayOrderData($transaction),
                $this->getGatewayOptions()
            );

            $transaction->update([
                'external_tx_id' => $bogOrder['id'],
                'payment_response' => ['order_created' => $bogOrder],
            ]);

            Log::channel('payments')->info('BOG order created successfully', [
                'transaction_id' => $transaction->id,
                'bog_order_id' => $bogOrder['id'],
            ]);

            return [
                'success' => true,
                'redirect_url' => $bogOrder['redirect_url'],
                'transaction_id' => $transaction->id,
            ];
        });

        Log::channel('payments')->info('Payment creation completed successfully', [
            'transaction_id' => $result['transaction_id'],
        ]);

        return $result;
    }

    /**
     * Create transaction record in database
     */
    private function createTransaction(array $validated, ShopItem $shopItem, Account $account): Transaction
    {
        $transaction = Transaction::create([
            'account_id' => $account->Id,
            'shop_item_id' => $shopItem->id,
            'amount' => $validated['amount'],
            'currency_type' => Transaction::CURRENCY_MONEY,
            'status' => Transaction::STATUS_PENDING,
            'payment_method' => 'Credit Card',
            'ip_address' => request()->ip(),
            'metadata' => [
                'username' => $validated['username'],
                'player_name' => $account->playerName,
            ],
        ]);

        Log::channel('payments')->info('Local transaction created', [
            'transaction_id' => $transaction->id,
            'account_id' => $account->Id,
            'amount' => $transaction->amount,
        ]);

        return $transaction;
    }

    /**
     * Build order data for payment gateway
     */
    private function buildGatewayOrderData(Transaction $transaction): array
    {
        return [
            'external_order_id' => 'GEN-' . uniqid(),
            'callback_url' => route('payment.callback'),
            'purchase_units' => [
                'currency' => 'GEL',
                'total_amount' => $transaction->amount,
                'basket' => [[
                    'product_id' => (string)$transaction->shopItem->id,
                    'quantity' => 1,
                    'unit_price' => $transaction->amount,
                    'description' => $transaction->shopItem->name,
                ]],
            ],
            'redirect_urls' => [
                'success' => route('payment.success', ['transaction_id' => $transaction->id]),
                'fail' => route('payment.fail', ['transaction_id' => $transaction->id]),
            ],
            'buyer' => [
                'full_name' => $transaction->account->playerName,
            ],
            'application_type' => 'web',
            'capture' => 'automatic',
        ];
    }

    /**
     * Get gateway options (language, theme, etc.)
     */
    private function getGatewayOptions(): array
    {
        return [
            'language' => app()->getLocale() === 'ka' ? 'ka' : 'en',
            'theme' => 'dark',
        ];
    }

    /**
     * Check transaction status with BOG
     * Caches result for 10 seconds to prevent excessive API calls
     *
     * @param int $transactionId
     * @return string Transaction status
     * @throws Exception
     */
    public function checkTransactionStatus(int $transactionId): string
    {
        $transaction = Transaction::findOrFail($transactionId);

        // Cache result for 10 seconds to prevent excessive API calls
        $cacheKey = 'transaction_status_' . $transactionId;
        $cachedStatus = Cache::get($cacheKey);

        if ($cachedStatus) {
            return $cachedStatus;
        }

        try {
            $bogStatus = $this->gateway->getOrderDetails(
                $transaction->external_tx_id
            );

            // Update if status changed
            if ($bogStatus['status'] !== $transaction->status) {
                $transaction->update(['status' => $bogStatus['status']]);
                Cache::forget($cacheKey);
                $this->statsService->invalidate();

                Log::channel('payments')->info('Transaction status updated', [
                    'transaction_id' => $transactionId,
                    'new_status' => $bogStatus['status'],
                ]);
            } else {
                // Cache unchanged result for 10 seconds
                Cache::put($cacheKey, $bogStatus['status'], 10);
            }

            return $transaction->status;
            
        } catch (Exception $e) {
            Log::channel('payments')->error('Status check failed', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);
            
            // Return last known status if API fails
            return $transaction->status;
        }
    }

    /**
     * Get transaction with relationships
     */
    public function getTransaction(int $transactionId): Transaction
    {
        return Transaction::with(['account', 'shopItem'])
            ->findOrFail($transactionId);
    }
}
