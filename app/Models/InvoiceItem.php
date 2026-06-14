<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\InvoiceItemFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    /** @use HasFactory<InvoiceItemFactory> */
    use HasFactory;

    private const SUPERADMIN_INDEX_COLUMNS = [
        'id',
        'invoice_id',
        'project_id',
        'description',
        'quantity',
        'unit',
        'unit_price',
        'total',
        'meter_reading_snapshot',
        'metadata',
        'voided_at',
        'void_reason',
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'invoice_id',
        'project_id',
        'description',
        'quantity',
        'unit',
        'unit_price',
        'total',
        'meter_reading_snapshot',
        'metadata',
        'voided_at',
        'void_reason',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:4',
            'total' => 'decimal:2',
            'meter_reading_snapshot' => 'array',
            'metadata' => 'array',
            'voided_at' => 'datetime',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function scopeWithIndexRelations(Builder $query): Builder
    {
        return $query->with([
            'invoice:id,invoice_number',
        ]);
    }

    public function scopeForSuperadminIndex(Builder $query): Builder
    {
        return $query
            ->select(self::SUPERADMIN_INDEX_COLUMNS)
            ->withIndexRelations()
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }
}
