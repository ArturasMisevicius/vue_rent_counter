<?php

namespace App\Models;

use Database\Factories\SystemConfigurationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemConfiguration extends Model
{
    /** @use HasFactory<SystemConfigurationFactory> */
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
        'category',
        'validation_rules',
        'default_value',
        'is_tenant_configurable',
        'requires_restart',
        'updated_by_admin_id',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
            'validation_rules' => 'array',
            'default_value' => 'array',
            'is_tenant_configurable' => 'boolean',
            'requires_restart' => 'boolean',
        ];
    }

    public function updatedByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_admin_id');
    }
}
