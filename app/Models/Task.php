<?php

namespace App\Models;

use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    private const SUMMARY_COLUMNS = [
        'id',
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

    public function scopeForWorkspaceSummary(Builder $query, int $organizationId): Builder
    {
        return $query
            ->select(self::SUMMARY_COLUMNS)
            ->forOrganization($organizationId)
            ->withPlanningRelations()
            ->ordered();
    }
}
