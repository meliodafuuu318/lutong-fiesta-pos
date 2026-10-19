<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'reference_number',
        'total',
        'type',
        'status',
        'payment_status',
        'transaction_date',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'transaction_date' => 'datetime',
    ];

    public function transactionItems() {
        return $this->hasMany(TransactionItem::class, 'transaction_id');
    }

    public function transactionPayments() {
        return $this->hasMany(TransactionPayment::class, 'transaction_id');
    }
}
