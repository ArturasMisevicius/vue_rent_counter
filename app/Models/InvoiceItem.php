<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvoiceItemSourceType;
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
        'source_type',
        'source_id',
        'service_configuration_id',
        'utility_service_id',
        'tariff_id',
        'provider_id',
        'project_id',
        'title',
        'description',
        'description_for_tenant',
        'internal_note',
        'quantity',
        'unit',
        'unit_price',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total',
        'currency',
        'formula_label',
        'calculation_snapshot',
        'tenant_visible',
        'sort_order',
        'meter_reading_snapshot',
        'service_snapshot',
        'tariff_snapshot',
        'provider_snapshot',
        'metadata',
        'voided_at',
        'void_reason',
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'invoice_id',
        'source_type',
        'source_id',
        'service_configuration_id',
        'utility_service_id',
        'tariff_id',
        'provider_id',
        'project_id',
        'title',
        'description',
        'description_for_tenant',
        'internal_note',
        'quantity',
        'unit',
        'unit_price',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total',
        'currency',
        'formula_label',
        'calculation_snapshot',
        'tenant_visible',
        'sort_order',
        'meter_reading_snapshot',
        'service_snapshot',
        'tariff_snapshot',
        'provider_snapshot',
        'metadata',
        'voided_at',
        'void_reason',
    ];

    protected function casts(): array
    {
        return [
            'source_type' => InvoiceItemSourceType::class,
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:4',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'calculation_snapshot' => 'array',
            'tenant_visible' => 'boolean',
            'sort_order' => 'integer',
            'meter_reading_snapshot' => 'array',
            'service_snapshot' => 'array',
            'tariff_snapshot' => 'array',
            'provider_snapshot' => 'array',
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

    public function serviceConfiguration(): BelongsTo
    {
        return $this->belongsTo(ServiceConfiguration::class);
    }

    public function utilityService(): BelongsTo
    {
        return $this->belongsTo(UtilityService::class);
    }

    public function tariff(): BelongsTo
    {
        return $this->belongsTo(Tariff::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
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
