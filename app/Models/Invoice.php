<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'property_id',
        'tenant_user_id',
        'invoice_number',
        'billing_period_start',
        'billing_period_end',
        'status',
        'currency',
        'total_amount',
        'amount_paid',
        'due_date',
        'finalized_at',
        'paid_at',
        'document_path',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'billing_period_start' => 'date',
            'billing_period_end' => 'date',
            'status' => InvoiceStatus::class,
            'total_amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'due_date' => 'date',
            'finalized_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_user_id');
    }

    public function currencyDefinition(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency', 'code');
    }
}
