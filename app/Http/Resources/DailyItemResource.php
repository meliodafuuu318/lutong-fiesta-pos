<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class DailyItemResource extends JsonResource
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
            'item' => [
                'id' => $this->item->id,
                'name' => $this->item->name,
            ],
            'price' => $this->price,
            'status' => $this->status,
            'date' => Carbon::parse($this->date)->format('Y-m-d'),
        ];
    }
}
