<?php

declare(strict_types=1);

namespace App\Models;

use App\Filament\Support\Localization\LocalizedCodeLabel;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    use SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (Task $task): void {
            $project = Project::query()
                ->select(['id', 'status'])
                ->find($task->project_id);

            if ($project?->isReadOnly()) {
                throw ValidationException::withMessages([
                    'project_id' => 'Tasks cannot be added to completed or cancelled projects.',
                ]);
            }
        });
    }

    private const SUMMARY_COLUMNS = [
        'id',
        'organization_id',
        'project_id',
        'title',
        'description',
        'hold_reason',
        'cancellation_note',
        'status',
        'priority',
        'created_by_user_id',
        'due_date',
        'completed_at',
        'estimated_hours',
        'actual_hours',
        'checklist',
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'organization_id',
        'project_id',
        'title',
        'description',
        'status',
        'priority',
        'created_by_user_id',
        'due_date',
        'completed_at',
        'estimated_hours',
        'actual_hours',
        'checklist',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'completed_at' => 'datetime',
            'estimated_hours' => 'decimal:2',
            'actual_hours' => 'decimal:2',
            'checklist' => 'array',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TaskAssignment::class);
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function closeAsCancelled(string $reason): void
    {
        $this->forceFill([
            'status' => 'cancelled',
            'cancellation_note' => $reason,
            'completed_at' => $this->completed_at ?? now(),
        ])->save();
    }

    public function statusLabel(): string
    {
        return LocalizedCodeLabel::translate('superadmin.relation_resources.tasks.statuses', $this->status);
    }

    public function priorityLabel(): string
    {
        return LocalizedCodeLabel::translate('superadmin.relation_resources.tasks.priorities', $this->priority);
    }

    public function statusBadgeColor(): string
    {
        return match ($this->status) {
            'completed' => 'success',
            'in_progress' => 'info',
            'review' => 'warning',
            'cancelled' => 'danger',
            default => 'gray',
        };
    }

    public function priorityBadgeColor(): string
    {
        return match ($this->priority) {
            'urgent' => 'danger',
            'high' => 'warning',
            'medium' => 'info',
            default => 'gray',
        };
    }

    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('completed_at');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query
            ->open()
            ->whereDate('due_date', '<', today());
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderBy('due_date')
            ->orderBy('id');
    }

    public function scopeWithPlanningRelations(Builder $query): Builder
    {
        return $query->with([
            'project:id,organization_id,name,status,priority',
            'creator:id,name,email',
        ]);
    }

    public function scopeWithIndexRelations(Builder $query): Builder
    {
        return $query
            ->withPlanningRelations()
            ->with([
                'organization:id,name',
            ]);
    }

    public function scopeForWorkspaceSummary(Builder $query, int $organizationId): Builder
    {
        return $query
            ->select(self::SUMMARY_COLUMNS)
            ->forOrganization($organizationId)
            ->withPlanningRelations()
            ->ordered();
    }

    public function scopeForSuperadminIndex(Builder $query): Builder
    {
        return $query
            ->select(self::SUMMARY_COLUMNS)
            ->withIndexRelations()
            ->ordered();
    }

    public function scopeForOrganizationValue(Builder $query, int|string|null $organizationId): Builder
    {
        if (blank($organizationId)) {
            return $query;
        }

        return $query->forOrganization((int) $organizationId);
    }
}
