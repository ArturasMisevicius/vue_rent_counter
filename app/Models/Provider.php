<?php

namespace App\Models;

use App\Enums\ServiceType;
use Database\Factories\ProviderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Provider extends Model
{
    /** @use HasFactory<ProviderFactory> */
    use HasFactory;

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
            ->select([
                'id',
                'organization_id',
                'name',
                'service_type',
                'contact_info',
                'created_at',
                'updated_at',
            ])
            ->where('organization_id', $organizationId);
    }
}
