<?php

namespace App\Http\Requests\Transaction;

/**
 * The create transaction form sends the same fields as the API, so it follows
 * the API's rules; only the error bag and the messages are different.
 */
class WebStoreTransactionRequest extends StoreTransactionRequest
{
    /** Keeps these errors apart from other forms' errors. */
    protected $errorBag = 'createTransaction';

    public function messages(): array
    {
        return [
            'transaction_items.required' => 'Add at least one item to the transaction.',
            'transaction_items.array' => 'Add at least one item to the transaction.',
            'transaction_items.*.daily_item_id.required' => 'One of the items is no longer on the menu.',
            'transaction_items.*.daily_item_id.exists' => 'One of the items is no longer on the menu.',
            'transaction_items.*.quantity.integer' => 'Each quantity must be a whole number.',
            'transaction_items.*.quantity.min' => 'Each item needs a quantity of at least 1.',
            'transaction_items.*.unit_price.required_without' => 'Enter a price when no quantity is provided.',
            'transaction_items.*.unit_price.numeric' => 'Each custom price must be a number.',
            'transaction_items.*.unit_price.min' => 'Each custom price cannot be negative.',
            'type.required' => 'Choose a transaction type.',
            'type.in' => 'Choose a valid transaction type.',
            'payment_method.required_if' => 'Choose a payment method.',
            'payment_method.required_with' => 'Choose a payment method.',
            'payment_method.in' => 'Choose a valid payment method.',
            'payment_amount.required_if' => 'Enter the amount paid.',
            'payment_amount.required_with' => 'Enter the amount paid.',
            'payment_amount.numeric' => 'The amount paid must be a number.',
            'payment_amount.min' => 'The amount paid can\'t be negative.',
            'payment_amount.max' => 'That amount is too large.',
            'note.max' => 'The note is too long (500 characters at most).',
            'address.required_if' => 'Enter the delivery address.',
            'address.max' => 'The address is too long.',
        ];
    }
}
