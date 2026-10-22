<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IngredientMaster extends Model
{
    protected $fillable = ['tenant_id', 'name', 'unit'];

    public function ingredients()
    {
        return $this->hasMany(Ingredient::class);
    }
}
