<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    protected $fillable = [
        'transaction_id',
        'address',
        'status',
    ];

    public function transaction() {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }
}
