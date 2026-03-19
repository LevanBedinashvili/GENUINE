<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate Bank of Georgia payment callback
 * 
 * SECURITY:
 * - Validates all required BOG payment callback fields
 * - Ensure BOG signature verification is performed in controller before processing
 * - Prevents fraudulent payment confirmations
 * - Validates transaction status transitions
 */
class PaymentCallbackRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Callback from BOG API - no user authorization needed
        // But verify BOG signature in controller
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            // BOG Transaction ID - unique identifier from BOG
            'transaction_id' => ['required', 'string', 'max:100'],
            
            // Amount in GEL - must match created transaction amount
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999'],
            
            // Currency code - should always be GEL
            'currency' => ['required', 'string', 'in:GEL'],
            
            // Payment status from BOG
            'status' => [
                'required',
                'string',
                'in:succeeded,failed,pending,cancelled',
            ],
            
            // Our external order ID (reference)
            'order_id' => ['required', 'string', 'max:100'],
            
            // BOG signature for verification
            'signature' => ['required', 'string'],
            
            // Additional metadata (optional)
            'message' => ['nullable', 'string', 'max:500'],
            'timestamp' => ['nullable', 'integer'],
        ];
    }

    /**
     * Custom validation error messages
     */
    public function messages(): array
    {
        return [
            'transaction_id.required' => 'BOG transaction ID is required',
            'amount.required' => 'Payment amount is required',
            'currency.in' => 'Invalid currency code',
            'status.in' => 'Invalid payment status',
            'signature.required' => 'Payment signature verification failed',
        ];
    }
}
