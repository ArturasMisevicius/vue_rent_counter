<?php

namespace App\Models;

use App\Filament\Support\Localization\LocalizedCodeLabel;
use Database\Factories\TaskAssignmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskAssignment extends Model
{
    /** @use HasFactory<TaskAssignmentFactory> */
    use HasFactory;

    protected $fillable = [
        'task_id',
        'user_id',
        'role',
        'assigned_at',
        'completed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class, 'assignment_id');
    }

    public function roleLabel(): string
    {
        return LocalizedCodeLabel::translate('superadmin.relation_resources.task_assignments.roles', $this->role);
    }

    public function roleBadgeColor(): string
    {
        return match ($this->role) {
            'reviewer' => 'warning',
            'observer' => 'gray',
            default => 'info',
        };
    }
}
