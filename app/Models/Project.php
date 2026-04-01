<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Exceptions\InvalidProjectTransitionException;
use App\Exceptions\ProjectApprovalRequiredException;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    use SoftDeletes;

    private const SUMMARY_COLUMNS = [
        'id',
        'organization_id',
        'property_id',
        'building_id',
        'created_by_user_id',
        'assigned_to_user_id',
        'manager_id',
        'approved_by',
        'name',
        'reference_number',
        'description',
        'type',
        'status',
        'priority',
        'start_date',
        'estimated_start_date',
        'actual_start_date',
        'due_date',
        'estimated_end_date',
        'actual_end_date',
        'completed_at',
        'cancelled_at',
        'cancellation_reason',
        'budget',
        'budget_amount',
        'actual_cost',
        'completion_percentage',
        'requires_approval',
        'approved_at',
        'cost_passed_to_tenant',
        'external_contractor',
        'contractor_contact',
        'contractor_reference',
        'notes',
        'metadata',
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'organization_id',
        'property_id',
        'building_id',
        'created_by_user_id',
        'assigned_to_user_id',
        'manager_id',
        'approved_by',
        'name',
        'reference_number',
        'description',
        'type',
        'status',
        'priority',
        'start_date',
        'estimated_start_date',
        'actual_start_date',
        'due_date',
        'estimated_end_date',
        'actual_end_date',
        'completed_at',
        'cancelled_at',
        'cancellation_reason',
        'budget',
        'budget_amount',
        'actual_cost',
        'completion_percentage',
        'requires_approval',
        'approved_at',
        'cost_passed_to_tenant',
        'external_contractor',
        'contractor_contact',
        'contractor_reference',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'type' => ProjectType::class,
            'status' => ProjectStatus::class,
            'priority' => ProjectPriority::class,
            'start_date' => 'date',
            'estimated_start_date' => 'date',
            'actual_start_date' => 'date',
            'due_date' => 'date',
            'estimated_end_date' => 'date',
            'actual_end_date' => 'date',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'approved_at' => 'datetime',
            'budget' => 'decimal:2',
            'budget_amount' => 'decimal:2',
            'actual_cost' => 'decimal:2',
            'completion_percentage' => 'integer',
            'requires_approval' => 'boolean',
            'cost_passed_to_tenant' => 'boolean',
            'metadata' => 'array',
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

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function enhancedTasks(): HasMany
    {
        return $this->hasMany(EnhancedTask::class);
    }

    public function taskAssignments(): HasManyThrough
    {
        return $this->hasManyThrough(
            TaskAssignment::class,
            Task::class,
            'project_id',
            'task_id',
            'id',
            'id',
        );
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function costRecords(): HasMany
    {
        return $this->hasMany(CostRecord::class);
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable')
            ->withPivot(['tagged_by_user_id'])
            ->withTimestamps();
    }

    public function teamMembers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_users')
            ->withPivot(['role', 'invited_at', 'invited_by'])
            ->withTimestamps();
    }

    public function projectMemberships(): HasMany
    {
        return $this->hasMany(ProjectUser::class);
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'subject');
    }

    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotIn('status', [
            ProjectStatus::COMPLETED->value,
            ProjectStatus::CANCELLED->value,
        ]);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderBy('estimated_end_date')
            ->orderBy('name')
            ->orderBy('id');
    }

    public function scopeWithPlanningRelations(Builder $query): Builder
    {
        return $query->with([
            'property:id,organization_id,building_id,name,unit_number',
            'building:id,organization_id,name,address_line_1,city',
            'manager:id,organization_id,name,email,role',
            'creator:id,name,email',
            'assignedTo:id,name,email',
            'approver:id,organization_id,name,email,role',
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

    public function scopeWithOperationalCounts(Builder $query): Builder
    {
        return $query->withCount([
            'tasks',
            'tasks as completed_tasks_count' => fn (Builder $taskQuery): Builder => $taskQuery->where('status', 'completed'),
            'tasks as overdue_tasks_count' => fn (Builder $taskQuery): Builder => $taskQuery
                ->where('status', '!=', 'completed')
                ->whereDate('due_date', '<', today()),
            'teamMembers',
            'attachments',
            'comments',
        ]);
    }

    public function scopeWithWorkCounts(Builder $query): Builder
    {
        return $query->withCount([
            'tasks',
            'enhancedTasks',
            'comments',
            'attachments',
        ]);
    }

    public function scopeForWorkspaceSummary(Builder $query, int $organizationId): Builder
    {
        return $query
            ->select(self::SUMMARY_COLUMNS)
            ->forOrganization($organizationId)
            ->withPlanningRelations()
            ->withOperationalCounts()
            ->withWorkCounts()
            ->ordered();
    }

    public function scopeForSuperadminIndex(Builder $query): Builder
    {
        return $query
            ->select(self::SUMMARY_COLUMNS)
            ->withIndexRelations()
            ->withOperationalCounts()
            ->withWorkCounts()
            ->ordered();
    }

    public function scopeForOrganizationWorkspace(Builder $query, int $organizationId): Builder
    {
        return $query
            ->select(self::SUMMARY_COLUMNS)
            ->forOrganization($organizationId)
            ->withPlanningRelations()
            ->withOperationalCounts()
            ->ordered();
    }

    public function scopeForOrganizationValue(Builder $query, int|string|null $organizationId): Builder
    {
        if (blank($organizationId)) {
            return $query;
        }

        return $query->forOrganization((int) $organizationId);
    }

    public function scopeForManagerValue(Builder $query, int|string|null $managerId): Builder
    {
        if (blank($managerId)) {
            return $query;
        }

        return $query->where('manager_id', (int) $managerId);
    }

    public function scopeNeedsAttention(Builder $query): Builder
    {
        return $query
            ->whereIn('status', [ProjectStatus::IN_PROGRESS->value, ProjectStatus::ON_HOLD->value])
            ->where(function (Builder $attentionQuery): void {
                $attentionQuery
                    ->whereColumn('actual_cost', '>', 'budget_amount')
                    ->orWhere(function (Builder $scheduleQuery): void {
                        $scheduleQuery
                            ->whereNotNull('estimated_end_date')
                            ->whereDate('estimated_end_date', '<', today());
                    })
                    ->orWhereHas('tasks', function (Builder $taskQuery): void {
                        $taskQuery
                            ->where('priority', 'urgent')
                            ->doesntHave('assignments');
                    });
            });
    }

    public function transitionTo(
        ProjectStatus $nextStatus,
        ?string $reason = null,
        bool $force = false,
        bool $acknowledgeIncompleteWork = false,
    ): self {
        $currentStatus = $this->status instanceof ProjectStatus
            ? $this->status
            : ProjectStatus::from((string) $this->status);

        if ((! $force || $currentStatus->isTerminal() || $nextStatus->isTerminal()) && ! $currentStatus->canTransitionTo($nextStatus)) {
            throw InvalidProjectTransitionException::between($currentStatus, $nextStatus);
        }

        if (
            $nextStatus === ProjectStatus::IN_PROGRESS
            && $this->requires_approval
            && $this->approved_at === null
            && ! $force
        ) {
            throw ProjectApprovalRequiredException::forStart();
        }

        if ($nextStatus === ProjectStatus::ON_HOLD && blank($reason)) {
            throw ValidationException::withMessages([
                'reason' => __('validation.required', ['attribute' => 'hold reason']),
            ]);
        }

        if ($nextStatus === ProjectStatus::CANCELLED && blank($reason) && blank($this->cancellation_reason)) {
            throw ValidationException::withMessages([
                'cancellation_reason' => __('validation.required', ['attribute' => 'cancellation reason']),
            ]);
        }

        $metadata = $this->metadata ?? [];

        if ($nextStatus === ProjectStatus::COMPLETED && $this->hasIncompleteCriticalTasks() && ! $acknowledgeIncompleteWork) {
            throw ValidationException::withMessages([
                'acknowledge_incomplete_work' => 'Critical open tasks must be explicitly acknowledged before completing this project.',
            ]);
        }

        if ($nextStatus === ProjectStatus::IN_PROGRESS && $this->actual_start_date === null) {
            $this->actual_start_date = today();

            if ($this->estimated_start_date?->lt(today()->subDays(7))) {
                Arr::set($metadata, 'late_start', true);
                Arr::set($metadata, 'late_start_days', $this->estimated_start_date->diffInDays(today()));
            }
        }

        if ($nextStatus === ProjectStatus::ON_HOLD) {
            Arr::set($metadata, 'on_hold_reason', $reason);
            Arr::set($metadata, 'on_hold_reason_updated_at', now()->toDateTimeString());

            if ($currentStatus !== ProjectStatus::ON_HOLD || blank(Arr::get($metadata, 'on_hold_started_at'))) {
                Arr::set($metadata, 'on_hold_started_at', now()->toDateTimeString());
            }
        }

        if ($nextStatus === ProjectStatus::COMPLETED) {
            $this->actual_end_date = $this->actual_end_date ?? today();
            $this->completed_at = $this->completed_at ?? now();
            $this->completion_percentage = 100;

            if ($acknowledgeIncompleteWork && $this->hasIncompleteCriticalTasks()) {
                Arr::set($metadata, 'completion_acknowledged_at', now()->toDateTimeString());
            }

            if ($this->estimated_end_date !== null && $this->actual_end_date !== null) {
                Arr::set($metadata, 'schedule_variance_days', $this->estimated_end_date->diffInDays($this->actual_end_date, false));
            }

            if ($this->budget_amount !== null) {
                Arr::set($metadata, 'budget_variance_amount', $this->budgetVarianceAmount());
            }
        }

        if ($nextStatus === ProjectStatus::CANCELLED) {
            $this->cancelled_at = $this->cancelled_at ?? now();
            $this->cancellation_reason = $reason ?? $this->cancellation_reason;
        }

        $this->status = $nextStatus;
        $this->metadata = $metadata;

        return $this;
    }

    public function hasIncompleteCriticalTasks(): bool
    {
        if (! $this->exists) {
            return false;
        }

        return $this->tasks()
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->where('priority', 'critical')
            ->exists();
    }

    public function availableTransitionTargets(bool $force = false): array
    {
        $currentStatus = $this->status instanceof ProjectStatus
            ? $this->status
            : ProjectStatus::from((string) $this->status);

        if ($currentStatus->isTerminal()) {
            return [];
        }

        if (! $force) {
            return $this->validNextStatuses();
        }

        return array_values(array_filter(
            ProjectStatus::cases(),
            function (ProjectStatus $candidate) use ($currentStatus): bool {
                if ($candidate === $currentStatus) {
                    return false;
                }

                if ($candidate->isTerminal()) {
                    return $currentStatus->canTransitionTo($candidate);
                }

                return true;
            },
        ));
    }

    public function isReadOnly(): bool
    {
        $status = $this->status instanceof ProjectStatus
            ? $this->status
            : ProjectStatus::from((string) $this->status);

        return $status->isTerminal();
    }

    public function budgetVarianceAmount(): ?float
    {
        if ($this->budget_amount === null) {
            return null;
        }

        return round((float) $this->actual_cost - (float) $this->budget_amount, 2);
    }

    public function scheduleVarianceDays(): ?int
    {
        if ($this->estimated_end_date === null) {
            return null;
        }

        $comparisonDate = $this->actual_end_date ?? today();

        return (int) $this->estimated_end_date->diffInDays($comparisonDate, false);
    }

    public function isOverBudget(): bool
    {
        return $this->budget_amount !== null && (float) $this->actual_cost > (float) $this->budget_amount;
    }

    public function isBehindSchedule(): bool
    {
        return $this->scheduleVarianceDays() !== null && $this->scheduleVarianceDays() > 0;
    }

    public function validNextStatuses(): array
    {
        $currentStatus = $this->status instanceof ProjectStatus
            ? $this->status
            : ProjectStatus::from((string) $this->status);

        return array_values(array_filter(
            ProjectStatus::cases(),
            fn (ProjectStatus $candidate): bool => $currentStatus->canTransitionTo($candidate),
        ));
    }
}
