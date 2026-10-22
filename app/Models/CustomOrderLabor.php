<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomOrderLabor extends Model
{
    protected $fillable = [
        'custom_order_id',
        'user_id',
        'job_description',
        'estimated_hours',
        'estimated_rate',
        'estimated_cost',
    ];

    public function customOrder()
    {
        return $this->belongsTo(CustomOrder::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
