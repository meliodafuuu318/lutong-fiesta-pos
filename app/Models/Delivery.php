<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    protected $fillable = [
        'transaction_id',
        'address',
        'delivery_fee',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'delivery_fee' => 'decimal:2',
        ];
    }

    public function transaction() {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }
}
