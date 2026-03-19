<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopCategory extends Model
{
    /**
     * Mass assignable attributes
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'sort_order',
        'is_active',
        'icon_url',
    ];

    /**
     * Cast attributes to native types
     */
    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationship to shop items
     */
    public function items()
    {
        return $this->hasMany(ShopItem::class, 'category_id');
    }

    /**
     * Relationship to active shop items
     */
    public function activeItems()
    {
        return $this->items()->where('is_active', true)->orderBy('sort_order');
    }

    /**
     * Scope: Get active categories only
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
     * Get total items count in this category
     */
    public function getTotalItemsCount()
    {
        return $this->items()->where('is_active', true)->count();
    }

    /**
     * Get total revenue from this category
     */
    public function getTotalRevenue()
    {
        return $this->items()
            ->join('transactions', 'shop_items.id', '=', 'transactions.shop_item_id')
            ->where('transactions.status', 'completed')
            ->sum(\DB::raw('transactions.amount'))
            ->value ?? 0;
    }
}
