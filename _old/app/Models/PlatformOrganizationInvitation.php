<?php

namespace App\Models;

use App\Enums\SubscriptionPlanType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PlatformOrganizationInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_name',
        'admin_email',
        'plan_type',
        'max_properties',
        'max_users',
        'token',
        'status',
        'expires_at',
        'accepted_at',
        'invited_by',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
        'plan_type' => SubscriptionPlanType::class,
    ];

    protected static function booted(): void
    {
        static::creating(function (PlatformOrganizationInvitation $invitation) {
            if (empty($invitation->token)) {
                $invitation->token = Str::random(64);
            }

            if (empty($invitation->expires_at)) {
                $invitation->expires_at = now()->addDays(7);
            }

            if (empty($invitation->status)) {
                $invitation->status = 'pending';
            }
        });
    }

    public function inviter()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending' && !$this->isExpired();
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast() && $this->status === 'pending';
    }

    public function isAccepted(): bool
    {
        return $this->status === 'accepted' && $this->accepted_at !== null;
    }

    public function accept(): Organization
    {
        // Create the organization
        $organization = Organization::create([
            'name' => $this->organization_name,
            'slug' => Str::slug($this->organization_name),
            'email' => $this->admin_email,
            'plan' => $this->plan_type->value,
            'max_properties' => $this->max_properties,
            'max_users' => $this->max_users,
            'is_active' => true,
            'subscription_ends_at' => now()->addYear(),
        ]);

        // Create the admin user
        $adminUser = User::create([
            'name' => explode('@', $this->admin_email)[0],
            'email' => $this->admin_email,
            'password' => bcrypt(Str::random(16)), // Temporary password
            'role' => 'admin',
            'organization_id' => $organization->id,
        ]);

        // Create the subscription
        Subscription::create([
            'user_id' => $adminUser->id,
            'plan_type' => $this->plan_type,
            'status' => 'active',
            'starts_at' => now(),
            'expires_at' => now()->addYear(),
            'max_properties' => $this->max_properties,
            'max_tenants' => $this->max_users,
        ]);

        // Mark invitation as accepted
        $this->update([
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);

        return $organization;
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    public function resend(): void
    {
        $this->update([
            'token' => Str::random(64),
            'expires_at' => now()->addDays(7),
            'status' => 'pending',
        ]);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending')
            ->where('expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'pending')
            ->where('expires_at', '<=', now());
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }
}
