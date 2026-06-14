<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ExtraChargeStatus;
use App\Enums\ExtraChargeTypeCode;
use Carbon\CarbonInterface;
use Database\Factories\ExtraChargeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ExtraCharge extends Model
{
    /** @use HasFactory<ExtraChargeFactory> */
    use HasFactory;

    private const ADMIN_INDEX_COLUMNS = [
        'id',
        'organization_id',
        'tenant_id',
        'property_id',
        'billing_period_id',
        'invoice_id',
        'extra_charge_type_id',
        'title',
        'description_for_tenant',
        'internal_note',
        'amount',
        'currency',
        'quantity',
        'unit_price',
        'tax_amount',
        'total_amount',
        'status',
        'is_recurring',
        'starts_at',
        'ends_at',
        'created_by_user_id',
        'approved_by_user_id',
        'approved_at',
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'organization_id',
        'tenant_id',
        'property_id',
        'billing_period_id',
        'invoice_id',
        'extra_charge_type_id',
        'title',
        'description_for_tenant',
        'internal_note',
        'amount',
        'currency',
        'quantity',
        'unit_price',
        'tax_amount',
        'total_amount',
        'status',
        'is_recurring',
        'starts_at',
        'ends_at',
        'created_by_user_id',
        'approved_by_user_id',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'quantity' => 'decimal:3',
            'unit_price' => 'decimal:4',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'status' => ExtraChargeStatus::class,
            'is_recurring' => 'boolean',
            'starts_at' => 'date',
            'ends_at' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForProperty(Builder $query, int $propertyId): Builder
    {
        return $query->where('property_id', $propertyId);
    }

    public function scopePendingReview(Builder $query): Builder
    {
        return $query->where('status', ExtraChargeStatus::PENDING_REVIEW);
    }

    public function scopeAffectingInvoices(Builder $query): Builder
    {
        return $query->whereIn('status', ExtraChargeStatus::invoiceableValues());
    }

    public function scopeForWorkspaceIndex(Builder $query, bool $isSuperadmin, ?int $organizationId): Builder
    {
        $query
            ->select(self::ADMIN_INDEX_COLUMNS)
            ->withIndexRelations()
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($isSuperadmin) {
            return $query;
        }

        return $organizationId === null
            ? $query->whereKey(-1)
            : $query->forOrganization($organizationId);
    }

    public function scopeWithIndexRelations(Builder $query): Builder
    {
        return $query->with([
            'tenant:id,organization_id,name,email',
            'property:id,organization_id,building_id,name,unit_number,type,floor_area_sqm',
            'property.building:id,organization_id,name',
            'type:id,organization_id,name,type,tenant_visible_by_default',
            'invoice:id,organization_id,invoice_number,status,total_amount',
            'billingPeriod:id,organization_id,name,starts_at,ends_at',
            'createdBy:id,name,email',
            'approvedBy:id,name,email',
        ]);
    }

    public function scopeInvoiceableForAssignment(
        Builder $query,
        int $organizationId,
        int $tenantId,
        int $propertyId,
        CarbonInterface $periodStart,
        CarbonInterface $periodEnd,
        ?int $billingPeriodId = null,
    ): Builder {
        return $query
            ->select(self::ADMIN_INDEX_COLUMNS)
            ->forOrganization($organizationId)
            ->forTenant($tenantId)
            ->forProperty($propertyId)
            ->where(function (Builder $invoiceableQuery) use ($periodStart, $periodEnd, $billingPeriodId): void {
                $invoiceableQuery
                    ->where(function (Builder $recurringQuery) use ($periodStart, $periodEnd): void {
                        $recurringQuery
                            ->where('is_recurring', true)
                            ->whereIn('status', ExtraChargeStatus::invoiceableValues())
                            ->where(function (Builder $startsQuery) use ($periodEnd): void {
                                $startsQuery
                                    ->whereNull('starts_at')
                                    ->orWhereDate('starts_at', '<=', $periodEnd->toDateString());
                            })
                            ->where(function (Builder $endsQuery) use ($periodStart): void {
                                $endsQuery
                                    ->whereNull('ends_at')
                                    ->orWhereDate('ends_at', '>=', $periodStart->toDateString());
                            });
                    })
                    ->orWhere(function (Builder $oneTimeQuery) use ($periodStart, $periodEnd, $billingPeriodId): void {
                        $oneTimeQuery
                            ->where('is_recurring', false)
                            ->where('status', ExtraChargeStatus::APPROVED)
                            ->whereNull('invoice_id')
                            ->where(function (Builder $periodQuery) use ($periodStart, $periodEnd, $billingPeriodId): void {
                                if ($billingPeriodId !== null) {
                                    $periodQuery->where('billing_period_id', $billingPeriodId);
                                }

                                $periodQuery->orWhere(function (Builder $dateQuery) use ($periodStart, $periodEnd): void {
                                    $dateQuery
                                        ->where(function (Builder $startsQuery) use ($periodEnd): void {
                                            $startsQuery
                                                ->whereNull('starts_at')
                                                ->orWhereDate('starts_at', '<=', $periodEnd->toDateString());
                                        })
                                        ->where(function (Builder $endsQuery) use ($periodStart): void {
                                            $endsQuery
                                                ->whereNull('ends_at')
                                                ->orWhereDate('ends_at', '>=', $periodStart->toDateString());
                                        });
                                });
                            });
                    });
            })
            ->with([
                'type:id,organization_id,name,type,tenant_visible_by_default',
            ])
            ->orderBy('id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function billingPeriod(): BelongsTo
    {
        return $this->belongsTo(BillingPeriod::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(ExtraChargeType::class, 'extra_charge_type_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function tenantVisibleAttachments(): MorphMany
    {
        return $this->attachments()
            ->where('attachments.tenant_visible', true)
            ->latestFirst();
    }

    public function isTenantVisible(): bool
    {
        return (bool) ($this->type?->tenant_visible_by_default ?? true);
    }

    public function canBeSilentlyChanged(): bool
    {
        if (! $this->invoice instanceof Invoice) {
            return true;
        }

        return $this->invoice->canEditFromAdminWorkspace();
    }

    public function statusLabel(): string
    {
        return $this->status instanceof ExtraChargeStatus
            ? $this->status->label()
            : (string) $this->status;
    }

    public function typeCode(): ?ExtraChargeTypeCode
    {
        return $this->type?->type instanceof ExtraChargeTypeCode
            ? $this->type->type
            : null;
    }
}
