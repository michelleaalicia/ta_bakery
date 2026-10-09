<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionOrder extends Model
{
    protected $fillable = [
        'product_variant_id',
        'branch_id',
        'batch_count',
        'order_type',
        'quantity',
        'production_date',
        'status',
        'bop_cost',
        'hpp_per_unit',
        'profit_percentage',
        'selling_price',
        'notes',
    ];

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function users()
    {
        return $this->belongsToMany(
            User::class,
            'users_has_production_orders',
            'production_order_id',
            'user_id'
        )->withPivot('job_description', 'status', 'labor_hours');
    }

    public function ingredients()
    {
        return $this->belongsToMany(
            Ingredient::class,
            'production_orders_has_ingredients',
            'production_order_id',
            'ingredient_id'
        )->withPivot('quantity_used', 'unit_cost_used');
    }
}