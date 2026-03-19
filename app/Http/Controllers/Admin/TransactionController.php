<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'account_id' => 'required|integer|exists:accounts,Id',
            'amount' => 'required|numeric|min:0.01|max:99999.99',
            'currency_type' => 'required|in:1,2,3',
            'external_tx_id' => 'required|string|unique:transactions,external_tx_id',
            'payment_method' => 'required|string',
            'payment_response' => 'nullable|json',
            'ip_address' => 'required|ip',
        ]);

        // Check for existing transaction with same external ID
        if (Transaction::where('external_tx_id', $validated['external_tx_id'])->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Duplicate transaction ID - already processed',
            ], 409);
        }

        $transaction = Transaction::create(array_merge($validated, [
            'status' => Transaction::STATUS_PENDING,
        ]));

        return response()->json([
            'success' => true,
            'transaction_id' => $transaction->id,
            'status' => $transaction->status,
        ], 201);
    }

    /**
     * Get transaction details by external TX ID
     */
    public function getByExternalId($externalTxId)
    {
        $transaction = Transaction::where('external_tx_id', $externalTxId)
            ->with(['shopItem.category'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'transaction' => $transaction,
        ]);
    }
}

