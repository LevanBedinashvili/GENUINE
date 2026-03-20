<?php

namespace App\Http\Controllers;

use App\Contracts\PaymentGatewayContract;
use App\Services\PaymentService;
use App\Services\BogPaymentSignatureValidator;
use App\Services\DashboardStatsService;
use App\Models\Transaction;
use App\Services\MoneyValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /**
     * Payment Service (handles business logic)
     * PaymentGatewayContract (implements payment gateway)
     */
    public function __construct(
        private PaymentService $paymentService,
        private PaymentGatewayContract $gateway,
        private DashboardStatsService $statsService
    ) {
    }

    /**
     * Create a new payment order
     * 
     * Input validation:
     * - Username: 1-24 chars, alphanumeric + _ -
     * - Shop item: must exist
     * - Amount: 0.01 to 999999
     * - Agreement: must be accepted
     * 
     * Process:
     * 1. Validate reCAPTCHA
     * 2. Validate input
     * 3. Create transaction via PaymentService
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createPayment(Request $request)
    {
        try {
            // Validate input
            $validator = Validator::make($request->all(), [
                'username' => ['required', 'string', 'min:1', 'max:24', 'regex:/^[a-zA-Z0-9_-]+$/'],
                'shop_item_id' => ['required', 'integer', 'exists:shop_items,id'],
                'amount' => ['required', 'numeric', 'min:0.01', 'max:999999'],
                'agree' => ['required', 'accepted'],
                'recaptcha_token' => ['required_if:env,production', 'nullable', 'string'],
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

            // Use PaymentService to handle business logic
            $result = $this->paymentService->createPayment($validator->validated());

            return response()->json($result, 200);

        } catch (\Exception $e) {
            Log::channel('payments')->error('Payment creation error', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'გადახდის ინიციირება ვერ მოხერხდა',
            ], 500);
        }
    }

    /**
     * Handle payment redirect after returning from BOG payment page
     * 
     * Display payment status based on transaction status
     * 
     * @param Request $request
     * @param string $transaction_id
     * @return \Illuminate\Contracts\View\View
     */
    public function handleRedirect(Request $request, $transaction_id)
    {
        try {
            // Load transaction with relationships
            $transaction = $this->paymentService->getTransaction($transaction_id);
            
            // Determine status message from transaction
            $message = match ($transaction->status) {
                Transaction::STATUS_COMPLETED => 'გადახდა წარმატებით დასრულდა!',
                Transaction::STATUS_FAILED => 'გადახდა ვერ მოხერხდა. გთხოვთ სცადოთ ხელახლა.',
                Transaction::STATUS_PENDING => 'გადახდა დამუშავდება. გთხოვთ მოითმინოთ...',
                default => 'გადახდის სტატუსი უცნობია.',
            };

            Log::channel('payments')->info('Payment redirect received', [
                'transaction_id' => $transaction->id,
                'status' => $transaction->status,
                'ip' => $request->ip(),
            ]);

            return view('payment-status', [
                'transaction' => $transaction,
                'success' => $transaction->status === Transaction::STATUS_COMPLETED,
                'message' => $message,
            ]);

        } catch (\Throwable $e) {
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

    /**
     * Handle BOG payment callback
     * 
     * Validates BOG callback signature and updates transaction status
     * All business logic delegated to PaymentService
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleCallback(Request $request)
    {
        $startTime = microtime(true);
        $signatureValidator = new BogPaymentSignatureValidator();
        
        try {
            $callbackData = $request->json()->all();
            $signature = $request->header('X-BOG-Signature') ?? $request->header('Callback-Signature');

            Log::channel('payments')->info('Payment callback received', [
                'ip' => $request->ip(),
                'has_signature' => !empty($signature),
                'transaction_id' => $callbackData['transaction_id'] ?? 'unknown',
            ]);

            // SECURITY: Verify callback signature
            if (empty($signature) || !$signatureValidator->verify($callbackData, $signature)) {
                Log::warning('⚠️ SECURITY: Invalid BOG callback signature!', [
                    'ip' => $request->ip(),
                    'transaction_id' => $callbackData['transaction_id'] ?? 'unknown',
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Signature verification failed',
                ], 401);
            }

            // Validate required fields
            if (empty($callbackData['transaction_id']) || empty($callbackData['amount']) || empty($callbackData['status'])) {
                Log::error('Invalid callback data structure', [
                    'data_keys' => array_keys($callbackData),
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid callback data',
                ], 400);
            }

            // Find transaction
            $transaction = Transaction::where('external_tx_id', $callbackData['transaction_id'])->first();
            if (!$transaction) {
                Log::error('Transaction not found for callback', [
                    'external_tx_id' => $callbackData['transaction_id'],
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction not found',
                ], 404);
            }

            // Validate amount match using bcmath for precise decimal comparison
            if (!MoneyValidator::amountsEqual($transaction->amount, $callbackData['amount'])) {
                Log::warning('⚠️ SECURITY: Payment amount mismatch!', [
                    'transaction_id' => $transaction->id,
                    'expected_amount' => $transaction->amount,
                    'callback_amount' => $callbackData['amount'],
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Amount mismatch',
                ], 400);
            }

            // Map status and update transaction
            $newStatus = $this->mapBogStatusToTransactionStatus($callbackData['status']);
            
            if (!$signatureValidator->isValidStatusTransition($transaction->status, $newStatus)) {
                Log::warning('Invalid payment status transition attempted', [
                    'transaction_id' => $transaction->id,
                    'current' => $transaction->status,
                    'requested' => $newStatus,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid status transition',
                ], 400);
            }

            // Update transaction and invalidate cache
            DB::transaction(function () use ($transaction, $callbackData, $newStatus) {
                $transaction->update([
                    'status' => $newStatus,
                    'payment_method' => $callbackData['payment_method'] ?? 'Bank Transfer',
                    'payment_response' => array_merge(
                        $transaction->payment_response ?? [],
                        ['last_callback' => $callbackData + ['received_at' => now()->toIso8601String()]]
                    ),
                ]);
                $this->statsService->invalidate();
            });

            Log::channel('payments')->info('✓ Callback processed', [
                'transaction_id' => $transaction->id,
                'new_status' => $newStatus,
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
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
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Processing error',
            ], 500);
        }
    }

    /**
     * Check payment status
     * 
     * Query BOG API for current transaction status (cached for 10 seconds)
     * 
     * @param Request $request
     * @param string $transaction_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkStatus(Request $request, $transaction_id)
    {
        try {
            // Load transaction using PaymentService
            $transaction = $this->paymentService->getTransaction($transaction_id);

            // Verify ownership: only the original requester can check status
            if ($transaction->ip_address !== $request->ip()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

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

            // Check status with BOG (cached for 10 seconds)
            $this->paymentService->checkTransactionStatus($transaction_id);
            
            // Reload transaction to get updated status
            $transaction = $this->paymentService->getTransaction($transaction_id);

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

            return response()->json([
                'success' => null,
                'status' => Transaction::STATUS_PENDING,
                'message' => 'გადახდა მუშავდება, გთხოვთ მოითმინოთ...',
            ]);

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

    /**
     * Map BOG payment status to internal Transaction status
     * 
     * @param string $bogStatus Status from BOG API
     * @return string Internal transaction status constant
     */
    private function mapBogStatusToTransactionStatus(string $bogStatus): string
    {
        $statusMap = [
            'succeeded' => Transaction::STATUS_COMPLETED,
            'success' => Transaction::STATUS_COMPLETED,
            'completed' => Transaction::STATUS_COMPLETED,
            'captured' => Transaction::STATUS_COMPLETED,
            
            'failed' => Transaction::STATUS_FAILED,
            'declined' => Transaction::STATUS_FAILED,
            'rejected' => Transaction::STATUS_FAILED,
            'cancelled' => Transaction::STATUS_FAILED,
            'error' => Transaction::STATUS_FAILED,
            
            'pending' => Transaction::STATUS_PENDING,
            'processing' => Transaction::STATUS_PENDING,
        ];

        $mappedStatus = $statusMap[strtolower($bogStatus)] ?? Transaction::STATUS_PENDING;

        Log::debug('BOG status mapped', [
            'bog_status' => $bogStatus,
            'transaction_status' => $mappedStatus,
        ]);

        return $mappedStatus;
    }
}
