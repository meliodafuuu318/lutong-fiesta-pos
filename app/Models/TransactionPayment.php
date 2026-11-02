<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionPayment extends Model
{
    public const METHODS = ['CASH', 'GCASH', 'BANK TRANSFER'];

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

    /** Change given back on this payment: when more than the balance was handed over, the balance goes negative. */
    public function changeGiven(): float {
        return max(0.0, round(-(float) $this->outstanding_balance, 2));
    }

    /** What actually stayed with the shop: the amount handed over minus the change given back. */
    public function amountReceived(): float {
        return round((float) $this->payment_amount - $this->changeGiven(), 2);
    }
}
