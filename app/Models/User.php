<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Scopes\HierarchicalScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new HierarchicalScope);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'property_id',
        'parent_user_id',
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'organization_name',
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
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the property assigned to this user (for tenant role).
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Get the parent user (admin) who created this user.
     */
    public function parentUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_user_id');
    }

    /**
     * Get the child users (tenants) created by this user.
     */
    public function childUsers(): HasMany
    {
        return $this->hasMany(User::class, 'parent_user_id');
    }

    /**
     * Get the subscription associated with this user (for admin role).
     */
    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }

    /**
     * Get the properties managed by this user (for admin role).
     */
    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'tenant_id', 'tenant_id');
    }

    /**
     * Get the buildings managed by this user (for admin role).
     */
    public function buildings(): HasMany
    {
        return $this->hasMany(Building::class, 'tenant_id', 'tenant_id');
    }

    /**
     * Get the invoices for this user's organization (for admin role).
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'tenant_id', 'tenant_id');
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
