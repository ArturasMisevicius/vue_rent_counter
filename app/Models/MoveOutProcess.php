<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MoveOutProcessStatus;
use App\Enums\PortalAccessAfterMoveOut;
use Database\Factories\MoveOutProcessFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MoveOutProcess extends Model
{
    /** @use HasFactory<MoveOutProcessFactory> */
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'tenant_id',
        'property_id',
        'property_assignment_id',
        'status',
        'move_out_date',
        'final_readings_required',
        'final_readings_completed_at',
        'final_invoice_id',
        'contract_id',
        'portal_access_after_move_out',
        'reason',
        'internal_note',
        'started_by_user_id',
        'completed_by_user_id',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => MoveOutProcessStatus::class,
            'move_out_date' => 'date',
            'final_readings_required' => 'boolean',
            'final_readings_completed_at' => 'datetime',
            'portal_access_after_move_out' => PortalAccessAfterMoveOut::class,
            'completed_at' => 'datetime',
        ];
    }

    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', MoveOutProcessStatus::openValues());
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

    public function propertyAssignment(): BelongsTo
    {
        return $this->belongsTo(PropertyAssignment::class);
    }

    public function finalInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'final_invoice_id');
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(RentalContract::class, 'contract_id');
    }

    public function finalReadings(): HasMany
    {
        return $this->hasMany(MeterReading::class);
    }

    public function startedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by_user_id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by_user_id');
    }
}
