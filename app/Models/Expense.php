<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = [
        'amount',
        'description',
        'expense_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
    ];

    protected $hidden = [
        'id',
        'created_at',
        'updated_at',
    ];

    public function scopeToday($query) {
        return $query->whereDate('expense_date', now()->toDateString());
    }

    public function scopeWeekly($query) {
        return $query->whereBetween('expense_date', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function scopeMonthly($query) {
        return $query->whereBetween('expense_date', [now()->startOfMonth(), now()->endOfMonth()]);
    }
}
