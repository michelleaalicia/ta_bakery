<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomOrderPayment extends Model
{
    protected $fillable = [
        'custom_order_id',
        'payment_type',
        'amount',
        'payment_date',
        'payment_method',
    ];

    public function customOrder()
    {
        return $this->belongsTo(CustomOrder::class);
    }
}