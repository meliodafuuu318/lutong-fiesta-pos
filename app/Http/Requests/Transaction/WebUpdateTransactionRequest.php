<?php

namespace App\Http\Requests\Transaction;

/**
 * The edit transaction form: the same items, address and note as when it was created. The type
 * and the payment can't be changed here, so they aren't accepted.
 */
class WebUpdateTransactionRequest extends WebStoreTransactionRequest
{
    /** Keeps these errors apart from other forms' errors. */
    protected $errorBag = 'editTransaction';

    public function rules(): array
    {
        $isDelivery = $this->route('transaction')?->type === 'DELIVERY';

        return [
            'transaction_items' => ['required', 'array'],
            'transaction_items.*.daily_item_id' => ['required', 'exists:daily_items,id'],
            'transaction_items.*.quantity' => ['nullable', 'integer', 'min:1'],
            'transaction_items.*.unit_price' => ['required_without:transaction_items.*.quantity', 'nullable', 'numeric', 'min:0'],
            'address' => [$isDelivery ? 'required' : 'nullable', 'string', 'max:500'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
