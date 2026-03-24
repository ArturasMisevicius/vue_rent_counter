<?php

namespace App\Models;

use Database\Factories\InvoiceReminderLogFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceReminderLog extends Model
{
    /** @use HasFactory<InvoiceReminderLogFactory> */
    use HasFactory;

    private const SUMMARY_COLUMNS = [
        'id',
        'invoice_id',
        'organization_id',
        'sent_by_user_id',
        'recipient_email',
        'channel',
        'sent_at',
        'notes',
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'invoice_id',
        'organization_id',
        'sent_by_user_id',
        'recipient_email',
        'channel',
        'sent_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
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

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by_user_id');
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query
            ->orderByDesc('sent_at')
            ->orderByDesc('id');
    }

    public function scopeWithIndexRelations(Builder $query): Builder
    {
        return $query->with([
            'invoice:id,organization_id,invoice_number',
            'organization:id,name',
            'sentBy:id,name,email',
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
