<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'payment_method' => ['sometimes', 'nullable', 'in:CASH,GCASH,BANK TRANSFER'],
            'payment_amount' => ['sometimes', 'required_with:payment_method', 'numeric', 'min:0'],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'delivery_status' => ['sometimes', 'nullable', 'in:PENDING,DELIVERED,CANCELLED'],
        ];
    }
}
