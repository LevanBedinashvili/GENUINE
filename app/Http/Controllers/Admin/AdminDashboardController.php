<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\ShopItem;
use App\Models\ShopCategory;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;

class AdminDashboardController extends Controller
{
    public function index()
    {
        // Calculate statistics using safe queries with parameterized bindings
        $stats = [
            'total_revenue' => Transaction::successful()->sum('amount') ?? 0,
            'total_transactions' => Transaction::count(),
            'completed_transactions' => Transaction::successful()->count(),
            'pending_transactions' => Transaction::pending()->count(),
            'failed_transactions' => Transaction::failed()->count(),
            'total_categories' => ShopCategory::count(),
            'total_items' => ShopItem::count(),
            'total_users_purchased' => Transaction::successful()->distinct('account_id')->count('account_id'),
        ];

        $recentTransactions = Transaction::with(['shopItem.category'])
            ->where('status', Transaction::STATUS_COMPLETED)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $topItems = ShopItem::withCount(['transactions as completed_count' => function ($query) {
            $query->where('status', Transaction::STATUS_COMPLETED);
        }])
        ->orderBy('completed_count', 'desc')
        ->limit(5)
        ->get();

        return view('admin.dashboard', compact(
            'stats',
            'recentTransactions',
            'topItems'
        ));
    }

    public function transactions(Request $request)
    {
        $query = Transaction::with(['shopItem.category', 'account']);

        if ($request->filled('status')) {
            $status = $request->get('status');
            // Validate status against allowed values to prevent injection
            if (in_array($status, [Transaction::STATUS_COMPLETED, Transaction::STATUS_FAILED, Transaction::STATUS_PENDING])) {
                $query->where('status', $status);
            }
        }

        if ($request->filled('date_from')) {
            try {
                $from = \Carbon\Carbon::createFromFormat('Y-m-d', $request->get('date_from'))->startOfDay();
                $query->whereDate('created_at', '>=', $from);
            } catch (\Exception $e) {
                // Invalid date format - ignore to prevent injection
            }
        }

        if ($request->filled('date_to')) {
            try {
                $to = \Carbon\Carbon::createFromFormat('Y-m-d', $request->get('date_to'))->endOfDay();
                $query->whereDate('created_at', '<=', $to);
            } catch (\Exception $e) {
            }
        }

        if ($request->filled('amount_from') && is_numeric($request->get('amount_from'))) {
            $query->where('amount', '>=', (float) $request->get('amount_from'));
        }

        if ($request->filled('amount_to') && is_numeric($request->get('amount_to'))) {
            $query->where('amount', '<=', (float) $request->get('amount_to'));
        }

        $transactions = $query->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.transactions.index', [
            'transactions' => $transactions,
            'statusOptions' => [
                'completed' => 'Completed',
                'pending' => 'Pending',
                'failed' => 'Failed',
            ],
        ]);
    }

    public function searchPlayer(Request $request)
    {
        $playerName = $request->get('player_name');
        
        $playerName = trim($playerName);
        if (strlen($playerName) < 2 || strlen($playerName) > 32) {
            return redirect()->back()->with('error', 'Invalid player name length');
        }

        // Escape SQL LIKE wildcards to prevent wildcard injection
        $escapedName = str_replace(['%', '_', '\\'], ['\\%', '\\_', '\\\\'], $playerName);

        $transactions = Transaction::select('transactions.*')
            ->join('accounts', 'transactions.account_id', '=', 'accounts.Id')
            ->where('accounts.playerName', 'LIKE', '%' . $escapedName . '%')
            ->with(['shopItem.category'])
            ->orderBy('transactions.created_at', 'desc')
            ->paginate(15);

        return view('admin.transactions.search', [
            'transactions' => $transactions,
            'search_term' => $playerName,
        ]);
    }

    public function verifyAndApprove(Request $request, $transactionId)
    {
        try {
            $transaction = Transaction::findOrFail($transactionId);

            if ($transaction->status !== Transaction::STATUS_PENDING) {
                return redirect()->back()->with('error', 'Transaction already processed');
            }

            if (!Transaction::validateUniqueTransaction(
                $transaction->external_tx_id,
                $transaction->amount,
                $transaction->account_id
            )) {
                return redirect()->back()->with('error', 'Duplicate transaction detected - possible fraud');
            }

            $transaction->markAsCompleted();

            return redirect()->back()->with('success', 'Transaction approved and player updated');
        } catch (QueryException $e) {
            // Log database errors securely
            \Log::error('Transaction approval failed', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return redirect()->back()->with('error', 'Database error - please try again');
        }
    }
    public function markAsFailed(Request $request, $transactionId)
    {
        $transaction = Transaction::findOrFail($transactionId);

        if ($transaction->status === Transaction::STATUS_COMPLETED) {
            return redirect()->back()->with('error', 'Cannot fail completed transaction');
        }

        $transaction->update([
            'status' => Transaction::STATUS_FAILED,
            'metadata' => array_merge(
                $transaction->metadata ?? [],
                ['failed_by' => auth()->id(), 'failed_at' => now()]
            ),
        ]);

        return redirect()->back()->with('success', 'Transaction marked as failed');
    }
}
