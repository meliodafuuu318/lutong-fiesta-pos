<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyItem extends Model
{
    protected $fillable = [
        'item_id',
        'price',
        'date',
        'status'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'date' => 'date|Y-m-d',
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public function item() {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function transactionItems() {
        return $this->hasMany(TransactionItem::class, 'daily_item_id');
    }
}
