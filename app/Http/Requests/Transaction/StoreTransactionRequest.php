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
            'transaction_items.*.quantity' => ['nullable', 'integer', 'min:1'],
            'transaction_items.*.unit_price' => ['required_without:transaction_items.*.quantity', 'nullable', 'numeric', 'min:0'],
            'type' => ['required', 'in:WALK IN,DELIVERY'],
            // Walk ins always pay up front. Deliveries may be pre-paid, so for them
            // the payment is optional, but a method and an amount go together.
            'payment_method' => ['required_if:type,WALK IN', 'required_with:payment_amount', 'nullable', 'in:CASH,GCASH,BANK TRANSFER'],
            'payment_amount' => ['required_if:type,WALK IN', 'required_with:payment_method', 'nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'address' => ['required_if:type,DELIVERY', 'nullable', 'string', 'max:500'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
