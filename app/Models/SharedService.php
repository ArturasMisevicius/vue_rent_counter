<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Shared service model for services distributed across multiple properties.
 * 
 * @property int $id
 * @property string $name
 * @property string $service_type
 * @property array $distribution_rules
 * @property bool $is_active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
final class SharedService extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'name',
        'service_type',
        'distribution_rules',
        'is_active',
    ];

    protected $casts = [
        'distribution_rules' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the service configurations for this shared service.
     */
    public function serviceConfigurations(): HasMany
    {
        return $this->hasMany(ServiceConfiguration::class);
    }
}