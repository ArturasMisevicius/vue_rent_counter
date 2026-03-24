<?php

namespace App\Models;

use App\Enums\ServiceType;
use Database\Factories\ProviderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Provider extends Model
{
    /** @use HasFactory<ProviderFactory> */
    use HasFactory;

    private const SUMMARY_COLUMNS = [
        'id',
        'organization_id',
        'name',
        'service_type',
        'contact_info',
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'organization_id',
        'name',
        'service_type',
        'contact_info',
    ];

    protected function casts(): array
    {
        return [
            'service_type' => ServiceType::class,
            'contact_info' => 'array',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function tariffs(): HasMany
    {
        return $this->hasMany(Tariff::class);
    }

    public function serviceConfigurations(): HasMany
    {
        return $this->hasMany(ServiceConfiguration::class);
    }

    public function scopeForOrganization(Builder $query, ?int $organizationId): Builder
    {
        return $query
            ->select(self::SUMMARY_COLUMNS)
            ->where('organization_id', $organizationId)
            ->ordered();
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderBy('name')
            ->orderBy('id');
    }

    public function scopeWithOrganizationSummary(Builder $query): Builder
    {
        return $query->with([
            'organization:id,name',
        ]);
    }

    public function scopeWithIndexRelations(Builder $query, bool $includeOrganization = false): Builder
    {
        if (! $includeOrganization) {
            return $query;
        }

        return $query->withOrganizationSummary();
    }

    public function scopeForWorkspaceIndex(Builder $query, bool $isSuperadmin, ?int $organizationId): Builder
    {
        $query = $query
            ->select(self::SUMMARY_COLUMNS)
            ->withIndexRelations($isSuperadmin)
            ->withCount(['tariffs'])
            ->withExists([
                'tariffs as admin_delete_has_tariffs',
                'serviceConfigurations as admin_delete_has_service_configurations',
            ])
            ->ordered();

        if ($isSuperadmin) {
            return $query;
        }

        if ($organizationId === null) {
            return $query->whereKey(-1);
        }

        return $query->where('organization_id', $organizationId);
    }

    public function scopeForOrganizationValue(Builder $query, int|string|null $organizationId): Builder
    {
        if (blank($organizationId)) {
            return $query;
        }

        return $query->where('organization_id', $organizationId);
    }

    public function scopeForServiceTypeValue(Builder $query, ?string $serviceType): Builder
    {
        if (blank($serviceType)) {
            return $query;
        }

        return $query->where('service_type', $serviceType);
    }

    public function canBeDeletedFromAdminWorkspace(): bool
    {
        $hasTariffs = $this->getAttribute('admin_delete_has_tariffs');

        if ($hasTariffs !== null && (bool) $hasTariffs) {
            return false;
        }

        $hasServiceConfigurations = $this->getAttribute('admin_delete_has_service_configurations');

        if ($hasServiceConfigurations !== null && (bool) $hasServiceConfigurations) {
            return false;
        }

        return ! (
            $this->tariffs()
                ->select(['id', 'provider_id'])
                ->exists()
            || $this->serviceConfigurations()
                ->select(['id', 'provider_id'])
                ->exists()
        );
    }

    public function adminDeletionBlockedReason(): ?string
    {
        return $this->canBeDeletedFromAdminWorkspace()
            ? null
            : __('admin.providers.messages.delete_blocked');
    }

    protected function providerCode(): Attribute
    {
        return Attribute::make(
            get: fn (): string => sprintf('PRV-%05d', (int) $this->getKey()),
        );
    }
}
