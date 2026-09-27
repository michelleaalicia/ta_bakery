<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserProductionOrder extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'production_order_id',
        'job_description',
        'status',
        'labor_hours',
        'labor_rate_per_hour',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function productionOrder()
    {
        return $this->belongsTo(ProductionOrder::class);
    }
}