<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ingredient extends Model
{
    protected $fillable = [
        'tenant_id',
        'ingredient_master_id',
        'branch_id',
        'name',
        'unit',
        'unit_cost',
        'stock',
        'min_stock',
    ];

    public function master()
    {
        return $this->belongsTo(IngredientMaster::class, 'ingredient_master_id');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function restocks()
    {
        return $this->hasMany(IngredientRestock::class);
    }

    public function recipes()
    {
        return $this->belongsToMany(
            Recipe::class,
            'recipes_has_ingredients',
            'ingredient_id',
            'recipe_id'
        );
    }

    public function productionOrders()
    {
        return $this->belongsToMany(
            ProductionOrder::class,
            'production_orders_has_ingredients',
            'ingredient_id',
            'production_order_id'
        );
    }
}