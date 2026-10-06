<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionItem extends Model
{
    protected $fillable = [
        'transaction_id',
        'daily_item_id',
        'unit_price',
        'quantity',
        'subtotal',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function transaction() {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    public function dailyItem() {
        return $this->belongsTo(DailyItem::class, 'daily_item_id');
    }
}
