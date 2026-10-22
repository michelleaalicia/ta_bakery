<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'name',
        'price',
        'status',
        'image',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function recipe()
    {
        return $this->hasOne(Recipe::class);
    }

    public function stocks()
    {
        return $this->hasMany(ProductVariantStock::class);
    }


    public function sales()
    {
        return $this->belongsToMany(
            Sale::class,
            'sales_has_product_variants',
            'product_variant_id',
            'sale_id'
        )->withPivot('quantity', 'unit_price');
    }

}