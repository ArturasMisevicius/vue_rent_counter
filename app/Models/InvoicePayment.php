<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Filament\Support\Localization\LocalizedCodeLabel;
use Database\Factories\InvoicePaymentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoicePayment extends Model
{
    /** @use HasFactory<InvoicePaymentFactory> */
    use HasFactory;

    private const SUMMARY_COLUMNS = [
        'id',
        'invoice_id',
        'organization_id',
        'recorded_by_user_id',
        'amount',
        'method',
        'reference',
        'paid_at',
        'notes',
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'invoice_id',
        'organization_id',
        'recorded_by_user_id',
        'amount',
        'method',
        'reference',
        'paid_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'method' => PaymentMethod::class,
            'paid_at' => 'datetime',
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

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    public function methodLabel(): string
    {
        $method = $this->method;

        if ($method instanceof PaymentMethod) {
            return $method->label();
        }

        return LocalizedCodeLabel::translate('superadmin.relation_resources.invoice_payments.methods', $method);
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query
            ->orderByDesc('paid_at')
            ->orderByDesc('id');
    }

    public function scopeWithIndexRelations(Builder $query): Builder
    {
        return $query->with([
            'invoice:id,organization_id,invoice_number',
            'organization:id,name',
            'recordedBy:id,name,email',
        ]);
    }

    public function scopeForSuperadminIndex(Builder $query): Builder
    {
        return $query
            ->select(self::SUMMARY_COLUMNS)
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
}
