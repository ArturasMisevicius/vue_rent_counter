<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LeadOutreachChannel;
use Database\Factories\LeadContactFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeadContact extends Model
{
    /** @use HasFactory<LeadContactFactory> */
    use HasFactory;

    private const WORKSPACE_COLUMNS = [
        'id',
        'organization_id',
        'name',
        'phone',
        'email',
        'normalized_phone',
        'normalized_email',
        'preferred_channel',
        'do_not_contact',
        'do_not_contact_reason',
        'do_not_contact_at',
        'last_contacted_at',
        'marked_do_not_contact_by_user_id',
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'organization_id',
        'name',
        'phone',
        'email',
        'normalized_phone',
        'normalized_email',
        'preferred_channel',
        'do_not_contact',
        'do_not_contact_reason',
        'do_not_contact_at',
        'last_contacted_at',
        'marked_do_not_contact_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'preferred_channel' => LeadOutreachChannel::class,
            'do_not_contact' => 'boolean',
            'do_not_contact_at' => 'datetime',
            'last_contacted_at' => 'datetime',
        ];
    }

    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeForWorkspaceIndex(Builder $query, bool $isSuperadmin, ?int $organizationId): Builder
    {
        return $query
            ->select(self::WORKSPACE_COLUMNS)
            ->when(
                ! $isSuperadmin,
                fn (Builder $workspaceQuery): Builder => $organizationId === null
                    ? $workspaceQuery->whereKey(-1)
                    : $workspaceQuery->forOrganization($organizationId),
            )
            ->with([
                'organization:id,name',
                'doNotContactMarker:id,name,email',
            ])
            ->withCount('listingLeads')
            ->ordered();
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderBy('name')
            ->orderBy('id');
    }

    public function displayName(): string
    {
        return filled($this->name)
            ? (string) $this->name
            : (string) ($this->email ?? $this->phone ?? __('admin.leads.labels.unknown_contact'));
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function doNotContactMarker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_do_not_contact_by_user_id');
    }

    public function listingLeads(): HasMany
    {
        return $this->hasMany(ListingLead::class);
    }

    public function outreachActivities(): HasMany
    {
        return $this->hasMany(LeadOutreachActivity::class);
    }
}
