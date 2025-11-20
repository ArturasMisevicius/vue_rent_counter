<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
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
            'role' => UserRole::class,
        ];
    }

    /**
     * Get the meter readings entered by this user.
     */
    public function meterReadings(): HasMany
    {
        return $this->hasMany(MeterReading::class, 'entered_by');
    }

    /**
     * Get the meter reading audits created by this user.
     */
    public function meterReadingAudits(): HasMany
    {
        return $this->hasMany(MeterReadingAudit::class, 'changed_by_user_id');
    }

    /**
     * Get the tenant (renter) associated with this user.
     */
    public function tenant()
    {
        return $this->hasOne(Tenant::class, 'email', 'email');
    }
}
