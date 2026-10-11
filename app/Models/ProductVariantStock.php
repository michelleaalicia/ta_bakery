<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariantStock extends Model
{
    protected $fillable = [
        'product_variant_id',
        'branch_id',
        'stock',
    ];

    protected function casts(): array
    {
        return [
            'stock' => 'decimal:2',
        ];
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}