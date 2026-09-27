<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $fillable = [
        'name',
    ];

    public function roles()
    {
        return $this->belongsToMany(
            Role::class,
            'roles_has_modules',
            'module_id',
            'role_id'
        );
    }
}