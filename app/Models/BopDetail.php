<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BopDetail extends Model
{
    protected $fillable = ['custom_order_id', 'production_order_id', 'category', 'amount'];
}
