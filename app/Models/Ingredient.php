<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ingredient extends Model
{
    protected $fillable = [
        'branch_id',
        'name',
        'unit',
        'unit_cost',
        'stock',
        'min_stock',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function restocks()
    {
        return $this->hasMany(IngredientRestock::class);
    }
}