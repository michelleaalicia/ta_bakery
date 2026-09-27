<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionOrder extends Model
{
    protected $fillable = [
        'product_variant_id',
        'branch_id',
        'order_type',
        'quantity',
        'production_date',
        'status',
        'bop_cost',
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
}