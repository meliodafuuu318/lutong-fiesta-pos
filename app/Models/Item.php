<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $fillable = [
        'category_id',
        'name', 
        'default_price',
        'is_active' 
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'default_price' => 'decimal:2',
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public function category() {
        return $this->belongsTo(Category::class, 'category_id');
    }
    
    public function dailyItems() {
        return $this->hasMany(DailyItem::class, 'item_id');
    }

    /**
     * Whether any of this item's daily items has transactions. Deleting the item
     * would cascade through its daily items to those transaction lines.
     */
    public function hasTransactionHistory(): bool {
        return $this->dailyItems()->whereHas('transactionItems')->exists();
    }
}
