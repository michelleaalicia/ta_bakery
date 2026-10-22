<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{


    protected $fillable = [
        'tenant_id',
        'branch_id',
        'sale_date',
        'total_amount',
        'payment_method',
        'payment_status',
        'cash_received',
        'change_amount',
    ];

    protected $casts = [
        'sale_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function details()
    {
        return $this->hasMany(SaleHasProductVariant::class, 'sale_id');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

}
