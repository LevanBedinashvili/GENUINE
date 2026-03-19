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
            // Get IP address
            $ip = $request->ip();
            
            $banKey = "shop_banned_ip_{$ip}";
            if (Cache::has($banKey)) {
                Log::channel('payments')->alert('Banned IP attempted access', [
                    'ip' => $ip,
                    'user_agent' => $request->userAgent(),
                ]);
                
                return response()->json([
                    'valid' => false,
                    'message' => 'თქვენი IP მოთხოვნა დაბლოკირებულია 24 საათის განმავლობაში',
                    'exists' => false,
                    'banned' => true
                ], 403); // Forbidden
            }
            
            $strictKey = "shop_validation_once_{$ip}";
            
            if (Cache::has($strictKey)) {
                $remainingSeconds = 60;
                
                Log::channel('payments')->warning('IP rate limited - only one validation per minute', [
                    'ip' => $ip,
                    'remaining_seconds' => $remainingSeconds,
                    'user_agent' => $request->userAgent(),
                ]);
                
                return response()->json([
                    'valid' => false,
                    'message' => 'სახელის შემოწმება ერთხელ შედეგია დაშვებული ერთი წუთში. გთხოვთ ' . $remainingSeconds . ' წამის შემდეგ სცადოთ ხელახლა.',
                    'exists' => false,
                    'retry_after' => $remainingSeconds
                ], 429);
            }
            
            $username = trim($request->input('username', ''));
            $amount = $request->input('amount');
            $categoryId = $request->input('category_id');
            
            if (in_array(strtolower($username), ['username', 'test', 'admin', 'user', 'player', 'name'])) {
                $banKey = "shop_banned_ip_{$ip}";
                Cache::put($banKey, true, 86400);
                
                Log::channel('payments')->alert('IP banned for 24 hours - suspicious username detected', [
                    'username' => $username,
                    'ip' => $ip,
                    'user_agent' => $request->userAgent(),
                ]);
                
                return response()->json([
                    'valid' => false,
                    'message' => 'თქვენი IP დაბლოკირებულია 24 საათის განმავლობაში ეჭვიანი აქტივობის გამო',
                    'exists' => false,
                    'banned' => true
                ], 403); // Forbidden
            }
            
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
            
            $account = Account::where('playerName', $username)
                ->select('id', 'playerName')
                ->first();
            
            if ($account) {
                $strictKey = "shop_validation_once_{$ip}";
                Cache::put($strictKey, true, 60);
                
                AuditLogger::logValidationAttempt([
                    'username' => $username,
                    'amount' => $amount,
                    'result' => 'success',
                    'message' => 'ანგარიში იპოვნა',
                ]);
                
                Log::channel('payments')->info('Username validation successful - rate limit set for 1 minute', [
                    'ip' => $ip,
                    'username' => $username,
                ]);
                
                return response()->json([
                    'valid' => true,
                    'message' => 'ანგარიში წარმატებით მოიძებნა: ' . $account->playerName,
                    'exists' => true,
                    'username' => $account->playerName
                ], 200);
            }
            
            $strictKey = "shop_validation_once_{$ip}";
            Cache::put($strictKey, true, 60);
            
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
