<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Notifications\VerifyOwnerEmail;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'tenant_id',
        'role_id',
        'branch_id',
        'wage_rate_per_hour',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function hasVerifiedEmail(): bool
    {
        // Karyawan tidak memerlukan verifikasi email.
        if ($this->role?->name !== 'Owner') {
            return true;
        }

        // Owner tetap wajib melakukan verifikasi email.
        return !is_null($this->email_verified_at);
    }


    public function sendEmailVerificationNotification(): void
    {
        if ($this->role?->name !== 'Owner') {
            return;
        }

        $this->notify(new VerifyOwnerEmail());
    }
}
