<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LeadOutreachChannel;
use App\Enums\LeadOutreachDirection;
use App\Enums\LeadOutreachStatus;
use Database\Factories\LeadOutreachActivityFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadOutreachActivity extends Model
{
    /** @use HasFactory<LeadOutreachActivityFactory> */
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'listing_lead_id',
        'lead_contact_id',
        'user_id',
        'channel',
        'direction',
        'subject',
        'message_summary',
        'status',
        'sent_at',
        'received_at',
        'next_follow_up_at',
        'completed_at',
        'internal_correction_reason',
    ];

    protected function casts(): array
    {
        return [
            'channel' => LeadOutreachChannel::class,
            'direction' => LeadOutreachDirection::class,
            'status' => LeadOutreachStatus::class,
            'sent_at' => 'datetime',
            'received_at' => 'datetime',
            'next_follow_up_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(ListingLead::class, 'listing_lead_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(LeadContact::class, 'lead_contact_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
