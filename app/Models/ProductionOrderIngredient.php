<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionOrderIngredient extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'production_order_id',
        'ingredient_id',
        'quantity_used',
        'unit_cost_used',
    ];

    public function productionOrder()
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }
}