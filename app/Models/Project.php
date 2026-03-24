<?php

namespace App\Models;

use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    private const SUMMARY_COLUMNS = [
        'id',
        'organization_id',
        'property_id',
        'building_id',
        'created_by_user_id',
        'assigned_to_user_id',
        'name',
        'description',
        'type',
        'status',
        'priority',
        'start_date',
        'due_date',
        'completed_at',
        'budget',
        'actual_cost',
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
        'name',
        'description',
        'type',
        'status',
        'priority',
        'start_date',
        'due_date',
        'completed_at',
        'budget',
        'actual_cost',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'due_date' => 'date',
            'completed_at' => 'datetime',
            'budget' => 'decimal:2',
            'actual_cost' => 'decimal:2',
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

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function enhancedTasks(): HasMany
    {
        return $this->hasMany(EnhancedTask::class);
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

    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('completed_at');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderBy('due_date')
            ->orderBy('name')
            ->orderBy('id');
    }

    public function scopeWithPlanningRelations(Builder $query): Builder
    {
        return $query->with([
            'property:id,organization_id,building_id,name,unit_number',
            'building:id,organization_id,name,address_line_1,city',
            'creator:id,name,email',
            'assignedTo:id,name,email',
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
            ->withWorkCounts()
            ->ordered();
    }

    public function scopeForSuperadminIndex(Builder $query): Builder
    {
        return $query
            ->select(self::SUMMARY_COLUMNS)
            ->withIndexRelations()
            ->withWorkCounts()
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
