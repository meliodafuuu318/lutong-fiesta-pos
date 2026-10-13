<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
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
            'transaction_items' => ['required', 'array'],
            'transaction_items.*.daily_item_id' => ['required', 'exists:daily_items,id'],
            'transaction_items.*.quantity' => ['required', 'integer', 'min:1'],
            'type' => ['required', 'in:WALK IN,DELIVERY'],
            'payment_method' => ['required_if:type,WALK IN', 'in:CASH,GCASH,BANK TRANSFER'],
            'payment_amount' => ['required_with:payment_method', 'numeric', 'min:0'],
        ];
    }
}
