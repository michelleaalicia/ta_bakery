<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomOrderMaterial extends Model
{
    protected $fillable = [
        'custom_order_id',
        'ingredient_master_id',
        'quantity',
        'unit',
        'estimated_unit_cost',
        'estimated_total_cost',
    ];

    public function customOrder()
    {
        return $this->belongsTo(CustomOrder::class);
    }

    public function ingredientMaster()
    {
        return $this->belongsTo(IngredientMaster::class);
    }
}
