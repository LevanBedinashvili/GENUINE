<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\ShopItem;
use App\Models\Account;
use App\Services\BogPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    private BogPaymentService $bogPaymentService;

    public function __construct(BogPaymentService $bogPaymentService)
    {
        $this->bogPaymentService = $bogPaymentService;
    }

    public function createPayment(Request $request)
    {
        try {

            $recaptchaToken = $request->input('recaptcha_token');
            $recaptchaSecret = config('services.recaptcha.secret_key');
            
            if ($recaptchaSecret) {
                if (!$recaptchaToken) {
                    Log::channel('payments')->warning('Missing reCAPTCHA token', [
                        'username' => $request->string('username'),
                        'ip' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'reCAPTCHA აუცილებელია. გთხოვთ დაადასტუროთ, რომ არ ხართ რობოტი.',
                    ], 422);
                }

                try {
                    $recaptchaResponse = file_get_contents(
                        "https://www.google.com/recaptcha/api/siteverify?secret=" . 
                        urlencode($recaptchaSecret) . 
                        "&response=" . urlencode($recaptchaToken) . 
                        "&remoteip=" . urlencode($request->ip())
                    );
                    
                    if ($recaptchaResponse === false) {
                        throw new \Exception('Failed to verify reCAPTCHA with Google API');
                    }
                    
                    $recaptchaData = json_decode($recaptchaResponse, true);

                    if (!isset($recaptchaData['success']) || !$recaptchaData['success']) {
                        Log::channel('payments')->warning('reCAPTCHA verification failed', [
                            'recaptcha_response' => $recaptchaData,
                            'ip' => $request->ip(),
                            'username' => $request->string('username'),
                        ]);
                        
                        return response()->json([
                            'success' => false,
                            'message' => 'reCAPTCHA ვერიფიკაცია ვერ მოხერხდა. გთხოვთ დაადასტუროთ, რომ არ ხართ რობოტი.',
                        ], 422);
                    }
                    
                    if (isset($recaptchaData['score'])) {
                        $minScore = config('services.recaptcha.min_score', 0.5);
                        if ($recaptchaData['score'] < $minScore) {
                            Log::channel('payments')->warning('reCAPTCHA score too low (suspected bot)', [
                                'score' => $recaptchaData['score'],
                                'min_score' => $minScore,
                                'ip' => $request->ip(),
                                'username' => $request->string('username'),
                            ]);
                            
                            return response()->json([
                                'success' => false,
                                'message' => 'reCAPTCHA ვერიფიკაცია ვერ მოხერხდა. რობოტის სავარაუდო ქცევა აღმოჩნდა.',
                            ], 422);
                        }
                    }
                    
                    Log::channel('payments')->info('reCAPTCHA verification successful', [
                        'username' => $request->string('username'),
                        'score' => $recaptchaData['score'] ?? 'N/A',
                        'action' => $recaptchaData['action'] ?? 'N/A',
                    ]);
                    
                } catch (\Exception $e) {
                    Log::channel('payments')->error('reCAPTCHA verification exception', [
                        'error' => $e->getMessage(),
                        'ip' => $request->ip(),
                        'username' => $request->string('username'),
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'reCAPTCHA სერვერის შეცდომა. სცადეთ ხელახლა.',
                    ], 500);
                }
            } else {
                Log::channel('payments')->critical('reCAPTCHA is NOT CONFIGURED - Bot protection disabled!', [
                    'username' => $request->string('username'),
                    'shop_item_id' => $request->integer('shop_item_id'),
                    'ip' => $request->ip(),
                ]);
            }

            
            $validator = Validator::make($request->all(), [
                'username' => ['required', 'string', 'min:1', 'max:24', 'regex:/^[a-zA-Z0-9_-]+$/'],
                'shop_item_id' => ['required', 'integer', 'exists:shop_items,id'],
                'amount' => ['required', 'numeric', 'min:0.01', 'max:999999'],
                'agree' => ['required', 'accepted'],
                'recaptcha_token' => ['nullable', 'string'],
            ], [
                'username.required' => 'სახელი სერვერზე აუცილებელია',
                'username.regex' => 'სახელი შეიძლება შეიცავდეს მხოლოდ ლათინურ ასოებს, რიცხვებს, _ და -',
                'shop_item_id.exists' => 'პროდუქტი ვერ მოიძებნა',
                'amount.min' => 'თანხა უნდა იყოს მინიმუმ 0.01 ლარი',
                'agree.required' => 'აუცილებელია შეთანხმებაზე კონფირმაცია',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }

            $shopItem = ShopItem::findOrFail($request->integer('shop_item_id'));

            $account = Account::where('playerName', $request->string('username'))->first();
            if (!$account) {
                Log::channel('payments')->warning('Payment attempt with non-existent account', [
                    'username' => $request->string('username'),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'ანგარიში ვერ მოიძებნა',
                ], 422);
            }

            $result = DB::transaction(function () use ($request, $shopItem, $account) {
                $externalOrderId = 'GEN-' . uniqid();
                $transaction = Transaction::create([
                    'account_id' => $account->Id,
                    'shop_item_id' => $shopItem->id,
                    'amount' => $request->float('amount'),
                    'currency_type' => Transaction::CURRENCY_MONEY,
                    'status' => Transaction::STATUS_PENDING,
                    'payment_method' => 'Credit Card',
                    'ip_address' => $request->ip(),
                    'metadata' => [
                        'username' => $request->string('username'),
                        'player_name' => $account->playerName,
                        'external_order_id' => $externalOrderId,
                    ],
                ]);

                Log::channel('payments')->info('Local transaction created', [
                    'transaction_id' => $transaction->id,
                    'account_id' => $account->Id,
                    'amount' => $transaction->amount,
                    'external_order_id' => $externalOrderId,
                ]);

                try {
                    $bogOrder = $this->bogPaymentService->createOrder([
                        'external_order_id' => $externalOrderId,
                        'callback_url' => route('payment.callback'),
                        'purchase_units' => [
                            'currency' => 'GEL',
                            'total_amount' => $transaction->amount,
                            'basket' => [
                                [
                                    'product_id' => (string) $shopItem->id,
                                    'quantity' => 1,
                                    'unit_price' => $transaction->amount,
                                    'description' => $shopItem->name,
                                ],
                            ],
                        ],
                        'redirect_urls' => [
                            'success' => route('payment.success', ['transaction_id' => $transaction->id]),
                            'fail' => route('payment.fail', ['transaction_id' => $transaction->id]),
                        ],
                        'buyer' => [
                            'full_name' => $account->playerName,
                        ],
                        'application_type' => 'web',
                        'capture' => 'automatic',
                    ], [
                        'language' => 'ka',
                        'theme' => 'dark',
                    ]);

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

                } catch (\Exception $e) {
                    Log::channel('payments')->error('BOG order creation failed', [
                        'transaction_id' => $transaction->id,
                        'error' => $e->getMessage(),
                    ]);
                    throw $e;
                }
            });

            return response()->json($result);

        } catch (\Exception $e) {
            Log::channel('payments')->error('Payment creation error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'გადახდის ინიციირება ვერ მოხერხდა',
            ], 500);
        }
    }
    public function handleRedirect(Request $request, $transaction_id)
    {
        try {
            $transaction = Transaction::findOrFail($transaction_id);
            
            // Determine status from route name
            $routeName = $request->route()->getName();
            $redirectStatus = str_ends_with($routeName, '.success') ? 'success' : 'fail';

            Log::channel('payments')->info('Payment redirect received', [
                'transaction_id' => $transaction->id,
                'redirect_status' => $redirectStatus,
                'current_status' => $transaction->status,
                'ip' => $request->ip(),
            ]);

            // Get current transaction status for display
            $message = match ($transaction->status) {
                Transaction::STATUS_COMPLETED => 'გადახდა წარმატებით დასრულდა!',
                Transaction::STATUS_FAILED => 'გადახდა ვერ მოხერხდა. გთხოვთ სცადოთ ხელახლა.',
                Transaction::STATUS_PENDING => $redirectStatus === 'success' 
                    ? 'გადახდა დამუშავდება. გთხოვთ მოითმინოთ...' 
                    : 'გადახდა ვერ მოხერხდა.',
                default => 'გადახდის სტატუსი უცნობია.',
            };

            return view('payment-status', [
                'transaction' => $transaction,
                'success' => $transaction->status === Transaction::STATUS_COMPLETED,
                'message' => $message,
            ]);

        } catch (\Exception $e) {
            Log::channel('payments')->error('Redirect handling error', [
                'transaction_id' => $transaction_id,
                'error' => $e->getMessage(),
            ]);

            return view('payment-status', [
                'success' => false,
                'message' => 'შეცდომა გადახდის სტატუსის ჩვენებისას.',
            ]);
        }
    }

    public function handleCallback(Request $request)
    {
        $startTime = microtime(true);
        
        try {
            $rawPayload = $request->getContent();
            $callbackData = $request->json()->all();
            $signature = $request->header('Callback-Signature');

            Log::channel('payments')->info('Webhook callback received', [
                'event' => $callbackData['event'] ?? 'unknown',
                'ip' => $request->ip(),
                'has_signature' => !empty($signature),
                'payload_size' => strlen($rawPayload),
            ]);

            if (!empty($signature)) {
                $isValid = $this->bogPaymentService->verifyCallbackSignature($signature, $rawPayload);
                if (!$isValid) {
                    Log::channel('payments')->warning('⚠️ Invalid callback signature', [
                        'event' => $callbackData['event'] ?? null,
                    ]);
                }
            }

            $parsedData = $this->bogPaymentService->parseCallback($callbackData);
            
            Log::channel('payments')->debug('Callback parsed', [
                'event' => $parsedData['event'],
                'order_id' => $parsedData['order_id'],
                'external_order_id' => $parsedData['external_order_id'],
                'status' => $parsedData['status'],
                'amount' => $parsedData['amount'],
            ]);

            $transaction = Transaction::whereJsonContains('metadata->external_order_id', $parsedData['external_order_id'])
                ->first();

            if (!$transaction) {
                // Fallback: try to find by BOG order ID
                $transaction = Transaction::where('external_tx_id', $parsedData['order_id'])->first();
            }

            if (!$transaction) {
                Log::channel('payments')->error('Transaction not found for callback', [
                    'external_order_id' => $parsedData['external_order_id'],
                    'bog_order_id' => $parsedData['order_id'],
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction not found',
                ], 404);
            }

            DB::transaction(function () use ($transaction, $parsedData, $callbackData) {
                Log::channel('payments')->info('Processing callback for transaction', [
                    'transaction_id' => $transaction->id,
                    'current_status' => $transaction->status,
                    'new_status' => $parsedData['status'],
                ]);

                $status = strtolower($parsedData['status'] ?? 'unknown');
                $paymentMethod = $parsedData['payment_method'] ?? 'Credit Card';

                $transaction->update([
                    'payment_method' => $paymentMethod,
                ]);

                if (in_array($status, ['completed', 'success'])) {
                    if ($transaction->status === Transaction::STATUS_FAILED) {
                        Log::channel('payments')->warning('Transaction already failed, cannot complete', [
                            'transaction_id' => $transaction->id,
                            'callback_status' => $status,
                        ]);
                    } else {
                        $transaction->update(['status' => Transaction::STATUS_COMPLETED]);
                        Log::channel('payments')->info('Transaction COMPLETED via callback', [
                            'transaction_id' => $transaction->id,
                            'amount' => $parsedData['amount'],
                            'method' => $paymentMethod,
                        ]);
                    }
                } elseif (in_array($status, ['failed', 'declined', 'rejected', 'cancelled'])) {
                    $transaction->update(['status' => Transaction::STATUS_FAILED]);
                    Log::channel('payments')->warning('Transaction FAILED via callback', [
                        'transaction_id' => $transaction->id,
                        'reason' => $status,
                    ]);
                } else {
                    Log::channel('payments')->info('Transaction status: ' . $status, [
                        'transaction_id' => $transaction->id,
                    ]);
                }

                $paymentResponse = $transaction->payment_response ?? [];
                $paymentResponse['last_webhook'] = [
                    'event' => $parsedData['event'],
                    'status' => $status,
                    'amount' => $parsedData['amount'],
                    'payment_method' => $paymentMethod,
                    'received_at' => now()->toIso8601String(),
                    'raw_data' => $callbackData,
                ];
                
                $transaction->update(['payment_response' => $paymentResponse]);
            });

            $duration = round((microtime(true) - $startTime) * 1000, 2);
            Log::channel('payments')->info('✓ Callback processed successfully', [
                'transaction_id' => $transaction->id,
                'duration_ms' => $duration,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Callback processed',
                'transaction_id' => $transaction->id,
            ]);

        } catch (\Throwable $e) {
            Log::channel('payments')->error('Callback processing error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Processing error',
            ], 500);
        }
    }
    public function checkStatus(Request $request, $transaction_id)
    {
        try {
            $transaction = Transaction::findOrFail($transaction_id);

            if ($transaction->isCompleted()) {
                return response()->json([
                    'success' => true,
                    'status' => Transaction::STATUS_COMPLETED,
                    'message' => 'გადახდა წარმატებით დასრულდა',
                ]);
            }

            if ($transaction->isFailed()) {
                return response()->json([
                    'success' => false,
                    'status' => Transaction::STATUS_FAILED,
                    'message' => 'გადახდა ვერ მოხერხდა',
                ]);
            }

            Log::channel('payments')->debug('Manual status check', [
                'transaction_id' => $transaction->id,
                'external_tx_id' => $transaction->external_tx_id,
            ]);

            try {
                $orderDetails = $this->bogPaymentService->getOrderDetails($transaction->external_tx_id);
                $status = $orderDetails['order_status']['key'] ?? 'unknown';

                Log::channel('payments')->info('Status check result', [
                    'transaction_id' => $transaction->id,
                    'bog_status' => $status,
                ]);

                if (in_array($status, ['completed', 'success'])) {
                    DB::transaction(function () use ($transaction, $orderDetails) {
                        $transaction->update([
                            'status' => Transaction::STATUS_COMPLETED,
                            'payment_response' => array_merge(
                                $transaction->payment_response ?? [],
                                ['manual_check' => $orderDetails]
                            ),
                        ]);
                    });
                    return response()->json([
                        'success' => true,
                        'status' => Transaction::STATUS_COMPLETED,
                        'message' => 'გადახდა წარმატებით დასრულდა',
                    ]);
                } elseif (in_array($status, ['failed', 'declined', 'rejected'])) {
                    DB::transaction(function () use ($transaction) {
                        $transaction->update(['status' => Transaction::STATUS_FAILED]);
                    });
                    return response()->json([
                        'success' => false,
                        'status' => Transaction::STATUS_FAILED,
                        'message' => 'გადახდა ვერ მოხერხდა',
                    ]);
                } else {
                    return response()->json([
                        'success' => null,
                        'status' => Transaction::STATUS_PENDING,
                        'message' => 'გადახდა მუშავდება, გთხოვთ მოითმინოთ...',
                    ]);
                }
            } catch (\Exception $e) {
                Log::channel('payments')->warning('BOG status check failed', [
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage(),
                ]);
                
                return response()->json([
                    'success' => null,
                    'status' => Transaction::STATUS_PENDING,
                    'message' => 'სტატუসი დადგენილი ვერ იქნა, ცდილობს ხელახლა...',
                ]);
            }

        } catch (\Exception $e) {
            Log::channel('payments')->error('Status check error', [
                'transaction_id' => $transaction_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'სტატუსის შემოწმება ვერ მოხერხდა',
            ], 500);
        }
    }
}
