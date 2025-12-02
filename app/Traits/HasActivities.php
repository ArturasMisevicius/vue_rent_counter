<?php

namespace App\Traits;

use App\Models\Activity;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

/**
 * HasActivities Trait
 * 
 * Add this trait to any model that should track activities
 */
trait HasActivities
{
    /**
     * Get all activities for the model
     */
    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subject')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Log an activity for the model
     */
    public function logActivity(
        string $description,
        ?string $event = null,
        ?array $properties = null,
        ?string $logName = null
    ): Activity {
        return $this->activities()->create([
            'tenant_id' => $this->tenant_id ?? auth()->user()?->tenant_id,
            'log_name' => $logName ?? 'default',
            'description' => $description,
            'causer_type' => auth()->check() ? get_class(auth()->user()) : null,
            'causer_id' => auth()->id(),
            'properties' => $properties,
            'event' => $event,
            'batch_uuid' => request()->header('X-Batch-UUID') ?? Str::uuid()->toString(),
        ]);
    }

    /**
     * Boot the trait and set up model event listeners
     */
    protected static function bootHasActivities(): void
    {
        static::created(function ($model) {
            if ($model->shouldLogActivity('created')) {
                $model->logActivity(
                    description: class_basename($model) . ' created',
                    event: 'created',
                    properties: ['attributes' => $model->getAttributes()]
                );
            }
        });

        static::updated(function ($model) {
            if ($model->shouldLogActivity('updated')) {
                $model->logActivity(
                    description: class_basename($model) . ' updated',
                    event: 'updated',
                    properties: [
                        'attributes' => $model->getChanges(),
                        'old' => $model->getOriginal(),
                    ]
                );
            }
        });

        static::deleted(function ($model) {
            if ($model->shouldLogActivity('deleted')) {
                $model->logActivity(
                    description: class_basename($model) . ' deleted',
                    event: 'deleted',
                    properties: ['attributes' => $model->getAttributes()]
                );
            }
        });
    }

    /**
     * Determine if activity should be logged for the given event
     * Override this method in your model to customize logging behavior
     */
    protected function shouldLogActivity(string $event): bool
    {
        // By default, log all events
        // Override in model to customize: return in_array($event, ['created', 'updated']);
        return true;
    }

    /**
     * Get activities caused by a specific user
     */
    public function activitiesCausedBy(int $userId): MorphMany
    {
        return $this->activities()
            ->where('causer_type', 'App\Models\User')
            ->where('causer_id', $userId);
    }

    /**
     * Get activities for a specific event
     */
    public function activitiesForEvent(string $event): MorphMany
    {
        return $this->activities()->where('event', $event);
    }
}
