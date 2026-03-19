<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Facades\Cache;

/**
 * Dashboard Statistics Service
 * Provides cached dashboard statistics to prevent expensive aggregation queries
 * Automatically refreshes every 5 minutes or when transaction completes
 */
class DashboardStatsService
{
    private const CACHE_KEY = 'dashboard_stats';
    private const CACHE_TTL = 300; // 5 minutes

    /**
     * Get cached dashboard statistics
     * Automatically refreshes every 5 minutes or when manually invalidated
     *
     * @return array Dashboard stats
     */
    public function getStats(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return $this->computeStats();
        });
    }

    /**
     * Compute fresh statistics
     * Called only when cache misses (every 5 minutes or after invalidation)
     * This method does the heavy lifting
     *
     * @return array Computed statistics
     */
    private function computeStats(): array
    {
        return [
            'total_revenue' => $this->getTotalRevenue(),
            'completed_transactions' => $this->getCompletedCount(),
            'failed_transactions' => $this->getFailedCount(),
            'pending_transactions' => $this->getPendingCount(),
            'revenue_today' => $this->getRevenueToday(),
            'revenue_this_month' => $this->getRevenueThisMonth(),
            'average_transaction_value' => $this->getAverageValue(),
            'cache_refreshed_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Get total revenue from completed transactions
     */
    private function getTotalRevenue(): float
    {
        return (float)(Transaction::where('status', 'completed')
            ->sum('amount') ?? 0);
    }

    /**
     * Count completed transactions
     */
    private function getCompletedCount(): int
    {
        return Transaction::where('status', 'completed')->count();
    }

    /**
     * Count failed transactions
     */
    private function getFailedCount(): int
    {
        return Transaction::where('status', 'failed')->count();
    }

    /**
     * Count pending transactions
     */
    private function getPendingCount(): int
    {
        return Transaction::where('status', 'pending')->count();
    }

    /**
     * Get revenue from today's completed transactions
     */
    private function getRevenueToday(): float
    {
        return (float)(Transaction::where('status', 'completed')
            ->whereBetween('created_at', [
                now()->startOfDay(),
                now()->endOfDay(),
            ])
            ->sum('amount') ?? 0);
    }

    /**
     * Get revenue from this month's completed transactions
     */
    private function getRevenueThisMonth(): float
    {
        return (float)(Transaction::where('status', 'completed')
            ->whereBetween('created_at', [
                now()->startOfMonth(),
                now()->endOfMonth(),
            ])
            ->sum('amount') ?? 0);
    }

    /**
     * Get average transaction value from completed transactions
     */
    private function getAverageValue(): float
    {
        return (float)(Transaction::where('status', 'completed')
            ->avg('amount') ?? 0);
    }

    /**
     * Invalidate cache - call when transaction status changes
     * Forces fresh computation on next getStats() call
     */
    public function invalidate(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Force refresh immediately (bypasses cache entirely)
     * Called when you need fresh stats right now
     *
     * @return array Fresh statistics
     */
    public function refresh(): array
    {
        $this->invalidate();
        return $this->getStats();
    }
}
