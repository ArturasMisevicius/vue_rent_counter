<?php

namespace App\Models;

use App\Enums\IntegrationHealthStatus;
use Database\Factories\IntegrationHealthCheckFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntegrationHealthCheck extends Model
{
    /** @use HasFactory<IntegrationHealthCheckFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'label',
        'status',
        'summary',
        'checked_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => IntegrationHealthStatus::class,
            'checked_at' => 'datetime',
            'metadata' => 'array',
        ];
    }
}
