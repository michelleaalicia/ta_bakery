<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recipe extends Model
{
    protected $fillable = [
        'product_variant_id',
        'quantity',
        'unit',
        'steps',
    ];
    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function ingredients()
    {
        return $this->belongsToMany(
            Ingredient::class,
            'recipes_has_ingredients',
            'recipe_id',
            'ingredient_id'
        )->withPivot('quantity', 'unit');
    }
}