<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IngredientRestock extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'ingredient_id',
        'quantity',
        'unit_price',
        'total_cost',
        'restock_date',
    ];

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }
}