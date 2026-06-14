<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Filament\Support\Localization\LocalizedCodeLabel;
use Database\Factories\InvoicePaymentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoicePayment extends Model
{
    /** @use HasFactory<InvoicePaymentFactory> */
    use HasFactory;

    use SoftDeletes;

    private const SUMMARY_COLUMNS = [
        'id',
        'invoice_id',
        'organization_id',
        'tenant_id',
        'property_id',
        'recorded_by_user_id',
        'submitted_by_user_id',
        'confirmed_by_user_id',
        'rejected_by_user_id',
        'voided_by_user_id',
        'amount',
        'currency',
        'method',
        'payment_method',
        'status',
        'payment_date',
        'reference',
        'transaction_id',
        'paid_at',
        'confirmed_at',
        'rejected_at',
        'rejection_reason',
        'voided_at',
        'void_reason',
        'notes',
        'internal_note',
        'tenant_comment',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'invoice_id',
        'organization_id',
        'tenant_id',
        'property_id',
        'recorded_by_user_id',
        'submitted_by_user_id',
        'confirmed_by_user_id',
        'confirmed_at',
        'rejected_by_user_id',
        'rejected_at',
        'rejection_reason',
        'voided_by_user_id',
        'voided_at',
        'void_reason',
        'amount',
        'currency',
        'method',
        'payment_method',
        'status',
        'payment_date',
        'reference',
        'transaction_id',
        'paid_at',
        'notes',
        'internal_note',
        'tenant_comment',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'method' => PaymentMethod::class,
            'payment_method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'payment_date' => 'date',
            'paid_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'rejected_at' => 'datetime',
            'voided_at' => 'datetime',
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
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by_user_id');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by_user_id');
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by_user_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function methodLabel(): string
    {
        $method = $this->resolvedPaymentMethod();

        if ($method instanceof PaymentMethod) {
            return $method->label();
        }

        return LocalizedCodeLabel::translate('superadmin.relation_resources.invoice_payments.methods', $method);
    }

    public function statusLabel(): string
    {
        $status = $this->status;

        if ($status instanceof PaymentStatus) {
            return $status->label();
        }

        return LocalizedCodeLabel::translate('enums.payment_status', $status);
    }

    public function resolvedPaymentMethod(): PaymentMethod|string|null
    {
        return $this->payment_method ?? $this->method;
    }

    public function isConfirmed(): bool
    {
        return $this->status === PaymentStatus::CONFIRMED;
    }

    public function canBeConfirmed(): bool
    {
        return $this->status === PaymentStatus::PENDING;
    }

    public function canBeRejected(): bool
    {
        return $this->status === PaymentStatus::PENDING;
    }

    public function canBeVoided(): bool
    {
        return in_array($this->status, [
            PaymentStatus::PENDING,
            PaymentStatus::CONFIRMED,
        ], true);
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query
            ->orderByDesc('confirmed_at')
            ->orderByDesc('paid_at')
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    public function scopeWithIndexRelations(Builder $query): Builder
    {
        return $query->with([
            'invoice:id,organization_id,property_id,tenant_user_id,invoice_number,currency,total_amount,amount_paid,paid_amount,balance_amount,payment_status,due_date',
            'organization:id,name',
            'tenant:id,organization_id,name,email',
            'property:id,organization_id,building_id,name,unit_number',
            'property.building:id,organization_id,name',
            'recordedBy:id,name,email',
            'submittedBy:id,name,email',
            'confirmedBy:id,name,email',
            'rejectedBy:id,name,email',
            'voidedBy:id,name,email',
            'attachments:id,organization_id,attachable_type,attachable_id,uploaded_by_user_id,filename,original_filename,mime_type,size,disk,path,document_type,tenant_visible,created_at',
        ]);
    }

    public function scopeForSuperadminIndex(Builder $query): Builder
    {
        return $query
            ->select(self::SUMMARY_COLUMNS)
            ->withIndexRelations()
            ->latestFirst();
    }

    public function scopeForAdminPaymentIndex(Builder $query, int $organizationId): Builder
    {
        return $query
            ->select(self::SUMMARY_COLUMNS)
            ->forOrganizationValue($organizationId)
            ->withIndexRelations()
            ->latestFirst();
    }

    public function scopeForOrganizationValue(Builder $query, int|string|null $organizationId): Builder
    {
        if (blank($organizationId)) {
            return $query;
        }

        return $query->where('organization_id', (int) $organizationId);
    }

    public function scopeForStatusValue(Builder $query, int|string|null $status): Builder
    {
        if (blank($status)) {
            return $query;
        }

        return $query->where('status', (string) $status);
    }

    public function scopeForInvoiceValue(Builder $query, int|string|null $invoiceId): Builder
    {
        if (blank($invoiceId)) {
            return $query;
        }

        return $query->where('invoice_id', (int) $invoiceId);
    }

    public function scopeForTenantValue(Builder $query, int|string|null $tenantId): Builder
    {
        if (blank($tenantId)) {
            return $query;
        }

        return $query->where('tenant_id', (int) $tenantId);
    }
}
