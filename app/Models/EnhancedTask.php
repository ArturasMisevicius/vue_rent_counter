<?php

namespace App\Models;

use Database\Factories\EnhancedTaskFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnhancedTask extends Model
{
    /** @use HasFactory<EnhancedTaskFactory> */
    use HasFactory;

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
}
