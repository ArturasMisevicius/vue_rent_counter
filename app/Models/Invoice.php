<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'paid_amount',
        'due_date',
        'finalized_at',
        'paid_at',
        'payment_reference',
        'snapshot_data',
        'snapshot_created_at',
        'items',
        'generated_at',
        'generated_by',
        'approval_status',
        'automation_level',
        'approval_deadline',
        'approval_metadata',
        'approved_by',
        'approved_at',
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
            'paid_amount' => 'decimal:2',
            'due_date' => 'date',
            'finalized_at' => 'datetime',
            'paid_at' => 'datetime',
            'snapshot_data' => 'array',
            'snapshot_created_at' => 'datetime',
            'items' => 'array',
            'generated_at' => 'datetime',
            'approval_deadline' => 'datetime',
            'approval_metadata' => 'array',
            'approved_at' => 'datetime',
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

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function generationAudits(): HasMany
    {
        return $this->hasMany(InvoiceGenerationAudit::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class);
    }

    public function emailLogs(): HasMany
    {
        return $this->hasMany(InvoiceEmailLog::class);
    }

    public function reminderLogs(): HasMany
    {
        return $this->hasMany(InvoiceReminderLog::class);
    }

    public function billingRecords(): HasMany
    {
        return $this->hasMany(BillingRecord::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
