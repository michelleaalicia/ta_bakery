<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleProductVariant extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'sale_id',
        'product_variant_id',
        'quantity',
        'unit_price',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }
}