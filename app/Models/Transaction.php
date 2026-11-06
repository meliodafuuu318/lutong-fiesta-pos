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
        'note',
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

    /**
     * Why this transaction can't be edited any more, or null when it still can.
     *
     * An order can be changed only while nothing has happened to it yet: no money taken (not even a
     * part payment), not delivered, not cancelled, not returned/refunded.
     */
    public function editBlockedReason(): ?string {
        if ($this->isVoid()) {
            return 'This transaction was cancelled or refunded, so it can\'t be edited.';
        }

        if ($this->delivery && $this->delivery->status !== 'PENDING') {
            return 'This order was already delivered, so it can\'t be edited.';
        }

        if ($this->payment_status === 'PAID' || $this->amountPaid() > 0) {
            return 'This order already has a payment, so it can\'t be edited.';
        }

        if ($this->status !== 'PENDING') {
            return 'This transaction is already completed, so it can\'t be edited.';
        }

        return null;
    }

    public function canBeEdited(): bool {
        return $this->editBlockedReason() === null;
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
        return $query->active()
            ->where('type', 'DELIVERY')
            ->whereHas('delivery', function ($q) {
                $q->where('status', 'PENDING');
            });
    }

    public function scopePendingPayment($query) {
        return $query->active()->where('payment_status', 'PENDING');
    }

    /** Everything except cancelled and refunded transactions. */
    public function scopeActive($query) {
        return $query->whereNotIn('status', ['CANCELLED', 'REFUNDED']);
    }

    public function scopeToday($query) {
        return $query->active()->whereBetween('transaction_date', [now()->startOfDay(), now()->endOfDay()]);
    }

    public function scopeWeekly($query) {
        return $query->active()->whereBetween('transaction_date', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function scopeMonthly($query) {
        return $query->active()->whereBetween('transaction_date', [now()->startOfMonth(), now()->endOfMonth()]);
    }
}
