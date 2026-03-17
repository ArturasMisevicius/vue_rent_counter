<?php

namespace App\Models;

use Database\Factories\EnhancedTaskFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EnhancedTask extends Model
{
    /** @use HasFactory<EnhancedTaskFactory> */
    use HasFactory;

    private const SUMMARY_COLUMNS = [
        'id',
        'organization_id',
        'project_id',
        'property_id',
        'meter_id',
        'created_by_user_id',
        'parent_enhanced_task_id',
        'title',
        'description',
        'type',
        'status',
        'priority',
        'estimated_hours',
        'actual_hours',
        'estimated_cost',
        'actual_cost',
        'due_date',
        'started_at',
        'completed_at',
        'metadata',
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'organization_id',
        'project_id',
        'property_id',
        'meter_id',
        'created_by_user_id',
        'parent_enhanced_task_id',
        'title',
        'description',
        'type',
        'status',
        'priority',
        'estimated_hours',
        'actual_hours',
        'estimated_cost',
        'actual_cost',
        'due_date',
        'started_at',
        'completed_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'estimated_hours' => 'decimal:2',
            'actual_hours' => 'decimal:2',
            'estimated_cost' => 'decimal:2',
            'actual_cost' => 'decimal:2',
            'due_date' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'metadata' => 'array',
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

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function meter(): BelongsTo
    {
        return $this->belongsTo(Meter::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function parentTask(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_enhanced_task_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_enhanced_task_id');
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
            ->where('due_date', '<', now());
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
            'property:id,organization_id,building_id,name,unit_number',
            'meter:id,organization_id,property_id,label,type,status',
            'creator:id,name,email',
            'parentTask:id,title,status,priority',
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
