<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $items = $this->whenLoaded('transactionItems', function () {
            return $this->transactionItems->map(function ($item) {
                return [
                    'id' => $item->id,
                    'item_id' => $item->daily_item_id,
                    'item_name' => $item->dailyItem->item->name,
                    'quantity' => $item->quantity,
                    'price' => number_format($item->unit_price, 2, '.', ''),
                    'total' => number_format($item->subtotal, 2, '.', ''),
                ];
            });
        });

        return [
            'id' => $this->id,
            'reference_number' => $this->reference_number,
            'total' => $this->total,
            'amount_paid' => number_format($this->amountPaid(), 2, '.', ''),
            'outstanding_balance' => number_format($this->outstandingBalance(), 2, '.', ''),
            'type' => $this->type,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'note' => $this->note,
            'transaction_date' => Carbon::parse($this->transaction_date)->format('Y-m-d'),
            'delivery' => $this->whenLoaded('delivery', fn () => $this->delivery ? new DeliveryResource($this->delivery) : null),
            'items' => $items,
        ];
    }
}
