<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class Transaction extends Model
{
    /**
     * Mass assignable attributes - matching actual DB schema
     */
    protected $fillable = [
        'account_id',
        'shop_item_id',
        'amount',
        'currency_type',
        'status',
        'external_tx_id',
        'payment_method',
        'payment_response',
        'ip_address',
        'metadata',
        'result_viewed',
        'result_viewed_at',
    ];

    /**
     * Cast attributes to native types
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'currency_type' => 'integer',
        'status' => 'string',
        'payment_response' => 'array',
        'metadata' => 'array',
        'result_viewed' => 'boolean',
        'result_viewed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationship to SA:MP accounts table
     */
    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id', 'Id');
    }

    /**
     * Relationship to shop items
     */
    public function shopItem(): BelongsTo
    {
        return $this->belongsTo(ShopItem::class);
    }

    /**
     * Currency type constants
     */
    const CURRENCY_COINS = 1;
    const CURRENCY_MONEY = 2;
    const CURRENCY_ITEM = 3;

    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    /**
     * Mark transaction as completed
     * Only allows completion if transaction is not already failed
     */
    public function markAsCompleted(): self
    {
        // Prevent completion of already failed transactions
        if ($this->status === self::STATUS_FAILED) {
            Log::channel('payments')->warning('Attempted to complete failed transaction', [
                'transaction_id' => $this->id,
                'current_status' => $this->status,
                'attempted_status' => self::STATUS_COMPLETED,
            ]);
            throw new \Exception('Cannot complete a failed transaction');
        }
        
        $this->update(['status' => self::STATUS_COMPLETED]);
        return $this;
    }

    /**
     * Mark transaction as failed
     * Once failed, cannot be completed again
     */
    public function markAsFailed(): self
    {
        $this->update(['status' => self::STATUS_FAILED]);
        
        // Log the failure for audit
        Log::channel('payments')->warning('Transaction marked as failed', [
            'transaction_id' => $this->id,
            'amount' => $this->amount,
            'external_tx_id' => $this->external_tx_id,
        ]);
        
        return $this;
    }

    /**
     * Mark payment result as viewed
     */
    public function markResultAsViewed(): self
    {
        $this->update([
            'result_viewed' => true,
            'result_viewed_at' => now(),
        ]);
        return $this;
    }

    /**
     * Check if result has been viewed
     */
    public function isResultViewed(): bool
    {
        return $this->result_viewed;
    }

    /**
     * Scope: Get successful (completed) transactions
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope: Get pending transactions
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope: Get failed transactions
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Check if transaction is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if transaction is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if transaction is failed
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Validate if transaction is unique (double-spending prevention)
     */
    public static function validateUniqueTransaction(?string $externalTxId, float $amount, int $accountId): bool
    {
        if (empty($externalTxId)) {
            return true;
        }

        $existing = self::where('external_tx_id', $externalTxId)
            ->where('amount', $amount)
            ->where('account_id', $accountId)
            ->where('status', self::STATUS_COMPLETED)
            ->exists();

        return !$existing;
    }

    /**
     * Generate a unique order ID for BOG
     */
    public static function generateOrderId(): string
    {
        return 'ORD-' . \Illuminate\Support\Str::uuid()->toString();
    }
}
