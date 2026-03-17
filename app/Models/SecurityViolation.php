<?php

namespace App\Models;

use App\Enums\SecurityViolationSeverity;
use App\Enums\SecurityViolationType;
use Database\Factories\SecurityViolationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityViolation extends Model
{
    /** @use HasFactory<SecurityViolationFactory> */
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'user_id',
        'type',
        'severity',
        'ip_address',
        'summary',
        'metadata',
        'occurred_at',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => SecurityViolationType::class,
            'severity' => SecurityViolationSeverity::class,
            'metadata' => 'array',
            'occurred_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
