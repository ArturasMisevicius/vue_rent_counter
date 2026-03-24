<?php

namespace App\Models;

use App\Enums\MeterStatus;
use App\Enums\TariffType;
use Database\Factories\TariffFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tariff extends Model
{
    /** @use HasFactory<TariffFactory> */
    use HasFactory;

    private const SUMMARY_COLUMNS = [
        'id',
        'provider_id',
        'remote_id',
        'name',
        'configuration',
        'active_from',
        'active_until',
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'provider_id',
        'remote_id',
        'name',
        'configuration',
        'active_from',
        'active_until',
    ];

    protected function casts(): array
    {
        return [
            'configuration' => 'array',
            'active_from' => 'datetime',
            'active_until' => 'datetime',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function serviceConfigurations(): HasMany
    {
        return $this->hasMany(ServiceConfiguration::class);
    }

    public function scopeActive(Builder $query, ?\DateTimeInterface $date = null): Builder
    {
        $activeOn = $date ?? now();

        return $query
            ->select(self::SUMMARY_COLUMNS)
            ->where('active_from', '<=', $activeOn)
            ->where(function (Builder $builder) use ($activeOn): void {
                $builder
                    ->whereNull('active_until')
                    ->orWhere('active_until', '>=', $activeOn);
            });
    }

    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->whereHas('provider', fn (Builder $providerQuery): Builder => $providerQuery->forOrganization($organizationId));
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderBy('name')
            ->orderBy('id');
    }

    public function scopeWithProviderSummary(Builder $query): Builder
    {
        return $query->with([
            'provider:id,organization_id,name,service_type',
        ]);
    }

    public function scopeWithIndexRelations(Builder $query, bool $includeOrganization = false): Builder
    {
        $query->withProviderSummary();

        if (! $includeOrganization) {
            return $query;
        }

        return $query->with([
            'provider.organization:id,name',
        ]);
    }

    public function scopeForWorkspaceIndex(Builder $query, bool $isSuperadmin, ?int $organizationId): Builder
    {
        $query = $query
            ->select(self::SUMMARY_COLUMNS)
            ->withIndexRelations($isSuperadmin)
            ->withExists([
                'serviceConfigurations as admin_delete_has_service_configurations',
                'serviceConfigurations as admin_delete_has_active_meter_assignments' => fn (Builder $serviceConfigurationQuery): Builder => $serviceConfigurationQuery
                    ->where('is_active', true)
                    ->whereHas(
                        'property.meters',
                        fn (Builder $meterQuery): Builder => $meterQuery->where('status', MeterStatus::ACTIVE),
                    ),
            ]);

        if ($isSuperadmin) {
            return $query;
        }

        if ($organizationId === null) {
            return $query->whereKey(-1);
        }

        return $query->whereHas(
            'provider',
            fn (Builder $providerQuery): Builder => $providerQuery->where('organization_id', $organizationId),
        );
    }

    public function scopeForOrganizationValue(Builder $query, int|string|null $organizationId): Builder
    {
        if (blank($organizationId)) {
            return $query;
        }

        return $query->whereHas(
            'provider',
            fn (Builder $providerQuery): Builder => $providerQuery->where('organization_id', $organizationId),
        );
    }

    public function scopeForProviderValue(Builder $query, int|string|null $providerId): Builder
    {
        if (blank($providerId)) {
            return $query;
        }

        return $query->where('provider_id', $providerId);
    }

    public function scopeForConfigurationTypeValue(Builder $query, ?string $configurationType): Builder
    {
        if (blank($configurationType)) {
            return $query;
        }

        return $query->where('configuration->type', $configurationType);
    }

    public function scopeForStatusValue(Builder $query, ?string $status): Builder
    {
        if (blank($status)) {
            return $query;
        }

        $moment = now();

        return match ($status) {
            'active' => $query
                ->where('active_from', '<=', $moment)
                ->where(function (Builder $builder) use ($moment): void {
                    $builder
                        ->whereNull('active_until')
                        ->orWhere('active_until', '>=', $moment);
                }),
            'inactive' => $query->where(function (Builder $builder) use ($moment): void {
                $builder
                    ->where('active_from', '>', $moment)
                    ->orWhere('active_until', '<', $moment);
            }),
            default => $query,
        };
    }

    public function isCurrentlyActive(?\DateTimeInterface $moment = null): bool
    {
        $activeOn = $moment ?? now();

        if ($this->active_from === null || $this->active_from->gt($activeOn)) {
            return false;
        }

        return $this->active_until === null || $this->active_until->gte($activeOn);
    }

    public function canBeDeletedFromAdminWorkspace(): bool
    {
        $hasServiceConfigurations = $this->getAttribute('admin_delete_has_service_configurations');

        if ($hasServiceConfigurations !== null && (bool) $hasServiceConfigurations) {
            return false;
        }

        $hasActiveMeterAssignments = $this->getAttribute('admin_delete_has_active_meter_assignments');

        if ($hasActiveMeterAssignments !== null && (bool) $hasActiveMeterAssignments) {
            return false;
        }

        return ! (
            $this->serviceConfigurations()
                ->select(['id', 'tariff_id'])
                ->exists()
            || $this->serviceConfigurations()
                ->select(['id', 'tariff_id', 'property_id', 'is_active'])
                ->where('is_active', true)
                ->whereHas(
                    'property.meters',
                    fn (Builder $meterQuery): Builder => $meterQuery->where('status', MeterStatus::ACTIVE),
                )
                ->exists()
        );
    }

    public function adminDeletionBlockedReason(): ?string
    {
        return $this->canBeDeletedFromAdminWorkspace()
            ? null
            : __('admin.tariffs.messages.delete_blocked');
    }

    protected function tariffType(): Attribute
    {
        return Attribute::make(
            get: fn (): string => TariffType::tryFrom((string) data_get($this->configuration, 'type'))?->getLabel()
                ?? (string) data_get($this->configuration, 'type', '—'),
        );
    }

    protected function rateDisplay(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $rate = data_get($this->configuration, 'rate');

                if (! is_numeric($rate)) {
                    return '—';
                }

                $currency = (string) data_get($this->configuration, 'currency', 'EUR');
                $unit = $this->provider?->service_type?->defaultUnit()?->value;

                return trim(sprintf(
                    '%s %s%s',
                    $currency,
                    number_format((float) $rate, 4),
                    $unit !== null ? sprintf(' / %s', $unit) : '',
                ));
            },
        );
    }

    protected function statusDisplay(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->isCurrentlyActive()
                ? __('admin.tariffs.statuses.active')
                : __('admin.tariffs.statuses.inactive'),
        );
    }
}
