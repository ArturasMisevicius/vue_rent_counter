<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\BillingGenerationLogItemFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingGenerationLogItem extends Model
{
    /** @use HasFactory<BillingGenerationLogItemFactory> */
    use HasFactory;

    protected $fillable = [
        'billing_generation_log_id',
        'organization_id',
        'billing_period_id',
        'invoice_id',
        'property_assignment_id',
        'tenant_user_id',
        'property_id',
        'level',
        'code',
        'message',
        'context',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
        ];
    }

    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    public function log(): BelongsTo
    {
        return $this->belongsTo(BillingGenerationLog::class, 'billing_generation_log_id');
    }

    public function billingPeriod(): BelongsTo
    {
        return $this->belongsTo(BillingPeriod::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function propertyAssignment(): BelongsTo
    {
        return $this->belongsTo(PropertyAssignment::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_user_id');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
