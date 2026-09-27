<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomOrder extends Model
{
    protected $fillable = [
        'production_order_id',
        'customer_name',
        'customer_contact',
        'order_date',
        'specification',
    ];

    public function productionOrder()
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function payments()
    {
        return $this->hasMany(CustomOrderPayment::class);
    }
}