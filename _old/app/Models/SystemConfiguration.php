<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

enum ConfigurationType: string
{
    case STRING = 'string';
    case INTEGER = 'integer';
    case FLOAT = 'float';
    case BOOLEAN = 'boolean';
    case ARRAY = 'array';
    case JSON = 'json';
}

/**
 * System Configuration Model
 * 
 * Manages global system configuration settings that can be controlled by super admins.
 * Supports different data types and tenant-level overrides.
 */
class SystemConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
        'is_tenant_configurable',
        'requires_restart',
        'updated_by_admin_id',
        'category',
        'validation_rules',
        'default_value',
    ];

    protected $casts = [
        'value' => 'array',
        'type' => ConfigurationType::class,
        'is_tenant_configurable' => 'boolean',
        'requires_restart' => 'boolean',
        'validation_rules' => 'array',
        'default_value' => 'array',
    ];

    /**
     * Get the admin user who last updated this configuration.
     */
    public function updatedByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_admin_id');
    }

    /**
     * Get the typed value based on the configuration type.
     */
    public function getTypedValue(): mixed
    {
        if ($this->value === null) {
            return $this->getTypedDefaultValue();
        }

        return match($this->type) {
            ConfigurationType::STRING => (string) ($this->value['value'] ?? ''),
            ConfigurationType::INTEGER => (int) ($this->value['value'] ?? 0),
            ConfigurationType::FLOAT => (float) ($this->value['value'] ?? 0.0),
            ConfigurationType::BOOLEAN => (bool) ($this->value['value'] ?? false),
            ConfigurationType::ARRAY, ConfigurationType::JSON => $this->value['value'] ?? [],
        };
    }

    /**
     * Get the typed default value.
     */
    public function getTypedDefaultValue(): mixed
    {
        if ($this->default_value === null) {
            return match($this->type) {
                ConfigurationType::STRING => '',
                ConfigurationType::INTEGER => 0,
                ConfigurationType::FLOAT => 0.0,
                ConfigurationType::BOOLEAN => false,
                ConfigurationType::ARRAY, ConfigurationType::JSON => [],
            };
        }

        return match($this->type) {
            ConfigurationType::STRING => (string) ($this->default_value['value'] ?? ''),
            ConfigurationType::INTEGER => (int) ($this->default_value['value'] ?? 0),
            ConfigurationType::FLOAT => (float) ($this->default_value['value'] ?? 0.0),
            ConfigurationType::BOOLEAN => (bool) ($this->default_value['value'] ?? false),
            ConfigurationType::ARRAY, ConfigurationType::JSON => $this->default_value['value'] ?? [],
        };
    }

    /**
     * Set a typed value for this configuration.
     */
    public function setTypedValue(mixed $value, ?int $adminId = null): void
    {
        $this->value = ['value' => $value];
        $this->updated_by_admin_id = $adminId ?? auth()->id();
        $this->save();
    }

    /**
     * Validate the given value against the configuration rules.
     */
    public function validateValue(mixed $value): bool
    {
        if (empty($this->validation_rules)) {
            return true;
        }

        $validator = validator(['value' => $value], ['value' => $this->validation_rules]);
        
        return !$validator->fails();
    }

    /**
     * Get configuration by key with caching.
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $config = cache()->remember(
            "system_config.{$key}",
            now()->addMinutes(30),
            fn() => static::where('key', $key)->first()
        );

        return $config?->getTypedValue() ?? $default;
    }

    /**
     * Set configuration value with cache invalidation.
     */
    public static function setValue(string $key, mixed $value, ?int $adminId = null): void
    {
        $config = static::firstOrCreate(['key' => $key]);
        $config->setTypedValue($value, $adminId);
        
        cache()->forget("system_config.{$key}");
    }

    /**
     * Scope to configurations that require system restart.
     */
    public function scopeRequiresRestart($query)
    {
        return $query->where('requires_restart', true);
    }

    /**
     * Scope to tenant-configurable settings.
     */
    public function scopeTenantConfigurable($query)
    {
        return $query->where('is_tenant_configurable', true);
    }

    /**
     * Scope by category.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}