<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;

final class Tag extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'color',
        'description',
        'type',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    // ==================== RELATIONSHIPS ====================

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    // Polymorphic relationships to all taggable models
    public function properties(): MorphToMany
    {
        return $this->morphedByMany(Property::class, 'taggable')
            ->withPivot(['tagged_by'])
            ->withTimestamps();
    }

    public function projects(): MorphToMany
    {
        return $this->morphedByMany(Project::class, 'taggable')
            ->withPivot(['tagged_by'])
            ->withTimestamps();
    }

    public function tasks(): MorphToMany
    {
        return $this->morphedByMany(EnhancedTask::class, 'taggable')
            ->withPivot(['tagged_by'])
            ->withTimestamps();
    }

    public function meters(): MorphToMany
    {
        return $this->morphedByMany(Meter::class, 'taggable')
            ->withPivot(['tagged_by'])
            ->withTimestamps();
    }

    public function invoices(): MorphToMany
    {
        return $this->morphedByMany(Invoice::class, 'taggable')
            ->withPivot(['tagged_by'])
            ->withTimestamps();
    }

    public function buildings(): MorphToMany
    {
        return $this->morphedByMany(Building::class, 'taggable')
            ->withPivot(['tagged_by'])
            ->withTimestamps();
    }

    // ==================== QUERY SCOPES ====================

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    public function scopeUserCreated($query)
    {
        return $query->where('is_system', false);
    }

    public function scopePopular($query, int $limit = 10)
    {
        return $query->withCount('taggables')
                    ->orderByDesc('taggables_count')
                    ->limit($limit);
    }

    // ==================== HELPER METHODS ====================

    public function getUsageCount(): int
    {
        return $this->properties()->count() +
               $this->projects()->count() +
               $this->tasks()->count() +
               $this->meters()->count() +
               $this->invoices()->count() +
               $this->buildings()->count();
    }

    public function canBeDeleted(): bool
    {
        return !$this->is_system && $this->getUsageCount() === 0;
    }

    // ==================== EVENTS ====================

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Tag $tag) {
            if (empty($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
        });

        static::updating(function (Tag $tag) {
            if ($tag->isDirty('name')) {
                $tag->slug = Str::slug($tag->name);
            }
        });
    }
}