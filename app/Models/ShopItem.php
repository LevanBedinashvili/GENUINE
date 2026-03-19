<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopItem extends Model
{
    /**
     * Protect primary key from mass assignment
     */
    protected $fillable = [
        'category_id',
        'name',
        'description',
        'price',
        'ingame_item_id',
        'quantity',
        'image_url',
        'is_active',
        'sort_order',
        'max_quantity_per_purchase',
    ];

    /**
     * Cast attributes to native types
     */
    protected $casts = [
        'price' => 'decimal:2',
        'ingame_item_id' => 'integer',
        'quantity' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'max_quantity_per_purchase' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationship to shop category
     */
    public function category()
    {
        return $this->belongsTo(ShopCategory::class, 'category_id');
    }

    /**
     * Relationship to transactions
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Currency type constants
     */
    const CURRENCY_COINS = 1;
    const CURRENCY_MONEY = 2;

    /**
     * Scope: Get active items only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Order by sort position
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc');
    }

    /**
     * Get currency label
     */
    // public function getCurrencyLabel()
    // {
    //     return $this->currency_type == self::CURRENCY_COINS ? 'G-COIN' : 'Money';
    // }

    /**
     * Get total sales count for this item
     */
    public function getTotalSalesCount()
    {
        return $this->transactions()
            ->where('status', 'completed')
            ->sum('quantity');
    }

    /**
     * Get total revenue from this item
     */
    public function getTotalRevenue()
    {
        return $this->transactions()
            ->where('status', 'completed')
            ->sum(\DB::raw('amount * quantity'));
    }
}
