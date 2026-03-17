<?php

namespace App\Models;

use Database\Factories\InvoiceGenerationAuditFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceGenerationAudit extends Model
{
    /** @use HasFactory<InvoiceGenerationAuditFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'invoice_id',
        'organization_id',
        'tenant_user_id',
        'user_id',
        'period_start',
        'period_end',
        'total_amount',
        'items_count',
        'metadata',
        'execution_time_ms',
        'query_count',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'total_amount' => 'decimal:2',
            'items_count' => 'integer',
            'metadata' => 'array',
            'execution_time_ms' => 'float',
            'query_count' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_user_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
