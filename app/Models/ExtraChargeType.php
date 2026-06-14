<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ExtraChargeTypeCode;
use Database\Factories\ExtraChargeTypeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExtraChargeType extends Model
{
    /** @use HasFactory<ExtraChargeTypeFactory> */
    use HasFactory;

    private const ADMIN_INDEX_COLUMNS = [
        'id',
        'organization_id',
        'name',
        'type',
        'default_amount',
        'currency',
        'is_recurring',
        'is_taxable',
        'tenant_visible_by_default',
        'requires_comment',
        'requires_attachment',
        'is_active',
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'organization_id',
        'name',
        'type',
        'default_amount',
        'currency',
        'is_recurring',
        'is_taxable',
        'tenant_visible_by_default',
        'requires_comment',
        'requires_attachment',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => ExtraChargeTypeCode::class,
            'default_amount' => 'decimal:2',
            'is_recurring' => 'boolean',
            'is_taxable' => 'boolean',
            'tenant_visible_by_default' => 'boolean',
            'requires_comment' => 'boolean',
            'requires_attachment' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderBy('name')
            ->orderBy('id');
    }

    public function scopeForWorkspaceIndex(Builder $query, bool $isSuperadmin, ?int $organizationId): Builder
    {
        $query
            ->select(self::ADMIN_INDEX_COLUMNS)
            ->with([
                'organization:id,name',
            ])
            ->ordered();

        if ($isSuperadmin) {
            return $query;
        }

        return $organizationId === null
            ? $query->whereKey(-1)
            : $query->forOrganization($organizationId);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function extraCharges(): HasMany
    {
        return $this->hasMany(ExtraCharge::class);
    }

    public function typeLabel(): string
    {
        return $this->type instanceof ExtraChargeTypeCode
            ? $this->type->label()
            : (string) $this->type;
    }
}
