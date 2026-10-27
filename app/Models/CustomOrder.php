<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomOrder extends Model
{
    protected $fillable = [
        'tenant_id',
        'branch_id',
        'production_order_id',
        'order_number',
        'customer_name',
        'customer_contact',
        'order_date',
        'required_date',
        'product_name',
        'specification',
        'quantity',
        'selling_price',
        'status',
        'estimated_labor_hours',
        'estimated_labor_rate',
        'estimated_overhead_rate',
        'estimated_bop_cost',
        'bop_description',
        'delivered_at',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function productionOrder()
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function materials()
    {
        return $this->hasMany(CustomOrderMaterial::class);
    }

    public function labors()
    {
        return $this->hasMany(CustomOrderLabor::class);
    }

    public function payments()
    {
        return $this->hasMany(CustomOrderPayment::class);
    }

    protected function casts(): array
    {
        return [
            'delivered_at' => 'datetime',
        ];
    }
}
