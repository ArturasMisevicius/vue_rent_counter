<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Activity Model - Activity log with polymorphic relationships
 * 
 * Tracks all important actions across the system
 * 
 * @property int $id
 * @property int $tenant_id
 * @property string|null $log_name
 * @property string $description
 * @property int $subject_id
 * @property string $subject_type
 * @property int|null $causer_id
 * @property string|null $causer_type
 * @property array|null $properties
 * @property string|null $event
 * @property string|null $batch_uuid
 */
class Activity extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'log_name',
        'description',
        'subject_id',
        'subject_type',
        'causer_id',
        'causer_type',
        'properties',
        'event',
        'batch_uuid',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    /**
     * Get the model that the activity was performed on
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user/model that caused the activity
     */
    public function causer(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get changes from properties
     */
    public function getChanges(): array
    {
        return $this->properties['attributes'] ?? [];
    }

    /**
     * Get old values from properties
     */
    public function getOldValues(): array
    {
        return $this->properties['old'] ?? [];
    }

    /**
     * Scope: Filter by log name
     */
    public function scopeInLog($query, string $logName)
    {
        return $query->where('log_name', $logName);
    }

    /**
     * Scope: Filter by event type
     */
    public function scopeForEvent($query, string $event)
    {
        return $query->where('event', $event);
    }

    /**
     * Scope: Filter by batch
     */
    public function scopeInBatch($query, string $batchUuid)
    {
        return $query->where('batch_uuid', $batchUuid);
    }

    /**
     * Scope: Caused by specific user
     */
    public function scopeCausedBy($query, User $user)
    {
        return $query->where('causer_type', User::class)
            ->where('causer_id', $user->id);
    }

    /**
     * Scope: For specific subject
     */
    public function scopeForSubject($query, Model $subject)
    {
        return $query->where('subject_type', get_class($subject))
            ->where('subject_id', $subject->id);
    }
}
