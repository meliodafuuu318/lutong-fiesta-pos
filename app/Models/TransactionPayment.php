<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionPayment extends Model
{
    protected $fillable = [
        'transaction_id',
        'payment_amount',
        'total_balance',
        'outstanding_balance',
        'payment_method'
    ];

    protected $casts = [
        'payment_amount' => 'decimal:2',
        'total_balance' => 'decimal:2',
        'outstanding_balance' => 'decimal:2',
    ];

    public function transaction() {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }
}
