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

    /** Only DELIVERY transactions have one. */
    public function delivery() {
        return $this->hasOne(Delivery::class, 'transaction_id');
    }

    /**
     * Everything paid so far, across all payments.
     *
     * Uses a withSum('transactionPayments', 'payment_amount') or an already loaded
     * relation when there is one, so lists don't run a query per row.
     */
    public function amountPaid(): float {
        if (array_key_exists('transaction_payments_sum_payment_amount', $this->attributes)) {
            return (float) $this->attributes['transaction_payments_sum_payment_amount'];
        }

        if ($this->relationLoaded('transactionPayments')) {
            return (float) $this->transactionPayments->sum('payment_amount');
        }

        return (float) $this->transactionPayments()->sum('payment_amount');
    }

    /** Cancelled and refunded transactions don't owe anything. */
    public function isVoid(): bool {
        return in_array($this->status, ['CANCELLED', 'REFUNDED'], true);
    }

    /** What is still owed: the total minus what has been paid, never below zero. */
    public function outstandingBalance(): float {
        if ($this->isVoid()) {
            return 0.0;
        }

        return max(0.0, round((float) $this->total - $this->amountPaid(), 2));
    }

    public function hasOutstandingBalance(): bool {
        return $this->outstandingBalance() > 0;
    }

    /** Change to give back when more than the total was handed over. */
    public function change(): float {
        if ($this->isVoid()) {
            return 0.0;
        }

        return max(0.0, round($this->amountPaid() - (float) $this->total, 2));
    }

    public function scopePendingDelivery($query) {
        return $query->where('type', 'DELIVERY')
            ->whereHas('delivery', function ($q) {
                $q->where('status', 'PENDING');
            });
    }

    public function scopePendingPayment($query) {
        return $query->where('payment_status', 'PENDING');
    }

    /** Everything except cancelled and refunded transactions. */
    public function scopeActive($query) {
        return $query->whereNotIn('status', ['CANCELLED', 'REFUNDED']);
    }

    public function scopeToday($query) {
        return $query->whereDate('transaction_date', now()->toDateString());
    }

    public function scopeWeekly($query) {
        return $query->whereBetween('transaction_date', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function scopeMonthly($query) {
        return $query->whereBetween('transaction_date', [now()->startOfMonth(), now()->endOfMonth()]);
    }
}
