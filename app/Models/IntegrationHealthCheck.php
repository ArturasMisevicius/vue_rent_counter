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
        'key',
        'label',
        'status',
        'checked_at',
        'response_time_ms',
        'summary',
        'details',
    ];

    protected function casts(): array
    {
        return [
            'status' => IntegrationHealthStatus::class,
            'checked_at' => 'datetime',
            'details' => 'array',
        ];
    }
}
