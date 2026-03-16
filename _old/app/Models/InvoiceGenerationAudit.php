<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * InvoiceGenerationAudit Model
 * 
 * Audit trail for invoice generation operations.
 * 
 * @property int $id
 * @property int $invoice_id
 * @property int $tenant_id
 * @property int $user_id
 * @property string $period_start
 * @property string $period_end
 * @property float $total_amount
 * @property int $items_count
 * @property array $metadata
 * @property float $execution_time_ms
 * @property int $query_count
 * @property \Illuminate\Support\Carbon $created_at
 * 
 * @package App\Models
 */
class InvoiceGenerationAudit extends Model
{
    use HasFactory;

    /**
     * Disable updated_at timestamp.
     */
    public const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'invoice_id',
        'tenant_id',
        'user_id',
        'period_start',
        'period_end',
        'total_amount',
        'items_count',
        'metadata',
        'execution_time_ms',
        'query_count',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'total_amount' => 'decimal:2',
        'items_count' => 'integer',
        'metadata' => 'array',
        'execution_time_ms' => 'float',
        'query_count' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * Get the invoice that was generated.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the user who generated the invoice.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the tenant for whom the invoice was generated.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'tenant_id');
    }
}
