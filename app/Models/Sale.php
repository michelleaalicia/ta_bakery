<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = [
        'branch_id',
        'sale_date',
        'total_amount',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}