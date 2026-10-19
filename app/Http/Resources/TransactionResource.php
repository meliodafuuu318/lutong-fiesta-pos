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
        return [
            'id' => $this->id,
            'reference_number' => $this->reference_number,
            'total' => $this->total,
            'type' => $this->type,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'transaction_date' => Carbon::parse($this->transaction_date)->format('Y-m-d'),
        ];
    }
}
