<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** The "Add payment" dialog on the dashboard's pending orders. */
class WebAddPaymentRequest extends FormRequest
{
    /** Keeps these errors apart from other forms' errors. */
    protected $errorBag = 'addPayment';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['required', Rule::in(['CASH', 'GCASH', 'BANK TRANSFER'])],
            'payment_amount' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_method.required' => 'Choose a payment method.',
            'payment_method.in' => 'Choose a valid payment method.',
            'payment_amount.required' => 'Enter the amount paid.',
            'payment_amount.numeric' => 'The amount paid must be a number.',
            'payment_amount.gt' => 'The amount paid must be more than zero.',
            'payment_amount.max' => 'That amount is too large.',
        ];
    }
}
