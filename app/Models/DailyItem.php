<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

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
        'date' => 'date',
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

    public function scopeAvailable($query) {
        return $query->where('status', 'AVAILABLE');
    }
    public function scopeTodayMenu($query) {
        return $query->onDate(today());
    }

    /**
     * Rows for one calendar day. A range instead of whereDate() so the index on `date` can be used
     * (DATE(date) would force a full scan). Works whether the value was stored as "Y-m-d" or "Y-m-d 00:00:00".
     */
    public function scopeOnDate($query, $date) {
        $day = Carbon::parse($date);

        return $query->where('date', '>=', $day->toDateString())
            ->where('date', '<', $day->copy()->addDay()->toDateString());
    }
}
