<?php

namespace App\Http\Controllers;

use App\Models\ShopItem;
use App\Models\ShopCategory;
use App\Models\Account;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ShopController extends Controller
{
    public function index()
    {
        $categories = ShopCategory::where('is_active', true)
            ->with('items')
            ->orderBy('sort_order')
            ->get();
        
        $items = ShopItem::where('is_active', true)
            ->with('category')
            ->orderBy('sort_order')
            ->get();

        return view('shop', [
            'categories' => $categories,
            'items' => $items,
        ]);
    }

    /**
     * API endpoint to get items by category
     */
    public function itemsByCategory($categoryId)
    {
        if ($categoryId == 'all') {
            $items = ShopItem::where('is_active', true)
                ->with('category')
                ->orderBy('sort_order')
                ->get();
        } else {
            $items = ShopItem::where('is_active', true)
                ->where('category_id', $categoryId)
                ->with('category')
                ->orderBy('sort_order')
                ->get();
        }

        return response()->json($items);
    }

    public function validateUsername(Request $request)
    {
        try {
            $username = trim($request->input('username', ''));
            $amount = $request->input('amount');
            $categoryId = $request->input('category_id');
            
            if (empty($username)) {
                AuditLogger::logValidationAttempt([
                    'username' => $username,
                    'result' => 'empty',
                    'message' => 'სახელი აუცილებელია',
                ]);
                
                return response()->json([
                    'valid' => false,
                    'message' => 'სახელი აუცილებელია',
                    'exists' => false
                ], 200);
            }
            
            if (strlen($username) < 1 || strlen($username) > 24) {
                AuditLogger::logValidationAttempt([
                    'username' => $username,
                    'result' => 'invalid_length',
                    'message' => 'გარკვეული სიგრძე',
                ]);
                
                return response()->json([
                    'valid' => false,
                    'message' => 'სახელი უნდა იყოს 1-24 სიმბოლო',
                    'exists' => false
                ], 200);
            }
            
            if (!preg_match('/^[a-zA-Z0-9_-]{1,24}$/', $username)) {
                AuditLogger::logValidationAttempt([
                    'username' => $username,
                    'result' => 'invalid_characters',
                    'message' => 'დაკრძალული სიმბოლოები',
                ]);
                
                return response()->json([
                    'valid' => false,
                    'message' => 'სახელი შეიძლება შეიცავდეს მხოლოდ ასოებს, ციფრებს, _ და -',
                    'exists' => false
                ], 200);
            }
            
            $isCurrency = false;
            if ($categoryId) {
                $currencyCategory = ShopCategory::where('slug', 'valuta')->first();
                if ($currencyCategory && intval($categoryId) === $currencyCategory->id) {
                    $isCurrency = true;
                }
            }
            
            if ($isCurrency) {
                if ($amount === null || $amount === '') {
                    AuditLogger::logValidationAttempt([
                        'username' => $username,
                        'amount' => $amount,
                        'result' => 'empty_amount',
                        'message' => 'ცარიელი თანხა',
                    ]);
                    
                    return response()->json([
                        'valid' => false,
                        'message' => 'თანხა აუცილებელია',
                        'exists' => false
                    ], 200);
                }
                
                $amountFloat = floatval($amount);
                if ($amountFloat < 1 || $amountFloat > 999999) {
                    AuditLogger::logValidationAttempt([
                        'username' => $username,
                        'amount' => $amount,
                        'result' => 'invalid_amount',
                        'message' => 'გარკვეული თანხა',
                    ]);
                    
                    return response()->json([
                        'valid' => false,
                        'message' => 'თანხა უნდა იყოს 1-დან 999999-მდე',
                        'exists' => false
                    ], 200);
                }
            }
            
            $account = Account::byPlayerName($username)
                ->select('Id', 'playerName')
                ->first();
            
            if ($account) {
                AuditLogger::logValidationAttempt([
                    'username' => $username,
                    'amount' => $amount,
                    'result' => 'success',
                    'message' => 'ანგარიში იპოვნა',
                ]);
                
                return response()->json([
                    'valid' => true,
                    'message' => 'ანგარიში წარმატებით მოიძებნა: ' . $account->playerName,
                    'exists' => true,
                    'username' => $account->playerName
                ], 200);
            }
            
            AuditLogger::logValidationAttempt([
                'username' => $username,
                'amount' => $amount,
                'result' => 'not_found',
                'message' => 'ანგარიში ვერ მოიძებნა',
            ]);
            
            return response()->json([
                'valid' => false,
                'message' => 'ანგარიში ვერ მოიძებნა',
                'exists' => false
            ], 200);
            
        } catch (\Exception $e) {
            AuditLogger::logValidationError($e->getMessage(), [
                'username' => $request->query('username'),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'valid' => false,
                'message' => 'შეცდომა სერვერის მხარეს',
                'exists' => false
            ], 500);
        }
    }
}
