<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Services\BogPaymentService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class UpdatePendingTransactions extends Command
{
    protected $signature = 'transactions:update-pending
                            {--all : Check all pending transactions (not just old ones)}
                            {--minutes=2 : How many minutes old transaction should be before checking (default: 2)}
                            {--limit=50 : Maximum number of transactions to process (default: 50)}';

    protected $description = 'Check pending transactions with BOG API and update status (fallback for missed webhooks)';

    protected BogPaymentService $bogPaymentService;

    public function __construct(BogPaymentService $bogPaymentService)
    {
        parent::__construct();
        $this->bogPaymentService = $bogPaymentService;
    }

    public function handle(): int
    {
        try {
            $startTime = microtime(true);
            
            $this->line("\n<fg=blue>═══════════════════════════════════════════</>");
            $this->line("<fg=blue>Payment Status Updater - Fallback Check</>");
            $this->line("<fg=blue>═══════════════════════════════════════════</>\n");

            Log::channel('payments')->info('Scheduler cron job started', [
                'timestamp' => now()->toIso8601String(),
                'pid' => getmypid(),
            ]);

            $checkAll = $this->option('all');
            $minutesOld = (int) $this->option('minutes');
            $limit = (int) $this->option('limit');
            $verbose = $this->getOutput()->isVerbose();

            $query = Transaction::where('status', Transaction::STATUS_PENDING);

            if (!$checkAll) {
                $query->where('created_at', '<', now()->subMinutes($minutesOld));
            }

            $pendingTransactions = $query->orderBy('created_at', 'asc')
                ->limit($limit)
                ->get();

            if ($pendingTransactions->isEmpty()) {
                $this->line("<fg=yellow>ℹ No pending transactions found.</>");
                
                Log::channel('payments')->info('✓ Scheduler cron job completed (no pending)', [
                    'timestamp' => now()->toIso8601String(),
                    'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
                ]);
                
                return 0;
            }

            $this->line("<fg=cyan>Found {$pendingTransactions->count()} pending transaction(s).</>\n");

            $completed = 0;
            $failed = 0;
            $errors = 0;
            $stillPending = 0;
            $completedTransactionIds = [];
            $failedTransactionIds = [];

            foreach ($pendingTransactions as $transaction) {
                try {
                    if ($verbose) {
                        $this->line("Processing transaction <fg=cyan>#{$transaction->id}</> " .
                                   "(<fg=gray>{$transaction->created_at->diffForHumans()}</>, " .
                                   "Amount: {$transaction->amount} GEL)");
                    }

                    if (empty($transaction->external_tx_id)) {
                        $this->line("  <fg=yellow>⚠️ No BOG order ID</> - skipping");
                        continue;
                    }

                    $orderDetails = $this->bogPaymentService->getOrderDetails($transaction->external_tx_id);
                    $bogStatus = $orderDetails['order_status']['key'] ?? 'unknown';

                    if ($verbose) {
                        $this->line("  BOG Status: <fg=cyan>{$bogStatus}</>");
                    }

                    DB::transaction(function () use ($transaction, $bogStatus, $orderDetails, $verbose) {
                        if (in_array($bogStatus, ['succeeded', 'completed', 'success', 'captured'])) {
                            $transaction->update(['status' => Transaction::STATUS_COMPLETED]);
                            
                            Log::channel('payments')->info('✓ Transaction auto-completed by scheduler', [
                                'transaction_id' => $transaction->id,
                                'bog_status' => $bogStatus,
                                'amount' => $transaction->amount,
                                'account_id' => $transaction->account_id,
                                'completed_at' => now()->toIso8601String(),
                            ]);

                        } elseif (in_array($bogStatus, ['failed', 'declined', 'rejected', 'cancelled', 'error'])) {
                            $transaction->update(['status' => Transaction::STATUS_FAILED]);
                            
                            Log::channel('payments')->warning('✗ Transaction auto-failed by scheduler', [
                                'transaction_id' => $transaction->id,
                                'bog_status' => $bogStatus,
                                'reason' => $bogStatus,
                                'account_id' => $transaction->account_id,
                                'failed_at' => now()->toIso8601String(),
                            ]);

                        } else {
                        }

                        $paymentResponse = $transaction->payment_response ?? [];
                        $paymentResponse['scheduler_check'] = [
                            'checked_at' => now()->toIso8601String(),
                            'bog_status' => $bogStatus,
                            'order_details' => $orderDetails,
                        ];
                        $transaction->update(['payment_response' => $paymentResponse]);
                    });

                    if (in_array($bogStatus, ['succeeded', 'completed', 'success', 'captured'])) {
                        $this->line("  <fg=green>✅ COMPLETED</>");
                        $completed++;
                        $completedTransactionIds[] = $transaction->id;
                    } elseif (in_array($bogStatus, ['failed', 'declined', 'rejected', 'cancelled', 'error'])) {
                        $this->line("  <fg=red>❌ FAILED</>");
                        $failed++;
                        $failedTransactionIds[] = $transaction->id;
                    } else {
                        $this->line("  <fg=yellow>⏳ STILL PENDING</>");
                        $stillPending++;
                    }

                } catch (\Throwable $e) {
                    $this->line("  <fg=red>✗ ERROR:</> {$e->getMessage()}");
                    
                    Log::channel('payments')->error('Scheduler check failed', [
                        'transaction_id' => $transaction->id,
                        'error' => $e->getMessage(),
                        'error_at' => now()->toIso8601String(),
                    ]);
                    
                    $errors++;
                }

                usleep(300000);
            }

            // Summary
            $this->line("\n<fg=blue>═══════════════════════════════════════════</>");
            $this->line("<fg=blue>Summary:</>");
            $this->line("<fg=blue>═══════════════════════════════════════════</>");
            $this->line("  <fg=green>Completed:</> {$completed}");
            $this->line("  <fg=red>Failed:</>     {$failed}");
            $this->line("  <fg=yellow>Pending:</>     {$stillPending}");
            $this->line("  <fg=red>Errors:</>      {$errors}");
            $this->line("<fg=blue>═══════════════════════════════════════════\n");

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            Log::channel('payments')->info('✓ Scheduler cron job completed successfully', [
                'timestamp' => now()->toIso8601String(),
                'duration_ms' => $duration,
                'total_checked' => $pendingTransactions->count(),
                'completed' => $completed,
                'failed' => $failed,
                'still_pending' => $stillPending,
                'errors' => $errors,
                'completed_transaction_ids' => $completedTransactionIds,
                'failed_transaction_ids' => $failedTransactionIds,
            ]);

            return 0;

        } catch (\Throwable $e) {
            $this->error("\nFatal Error: {$e->getMessage()}");
            
            Log::channel('payments')->error('Scheduler cron job FAILED', [
                'timestamp' => now()->toIso8601String(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 1;
        }
    }
}
