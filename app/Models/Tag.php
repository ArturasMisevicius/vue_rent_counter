<?php

namespace App\Models;

use App\Filament\Support\Localization\LocalizedCodeLabel;
use App\Models\Concerns\HasGeneratedSlug;
use Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Tag extends Model
{
    /** @use HasFactory<TagFactory> */
    use HasFactory;

    use HasGeneratedSlug;

    private const SUMMARY_COLUMNS = [
        'id',
        'organization_id',
        'name',
        'slug',
        'color',
        'description',
        'type',
        'is_system',
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'organization_id',
        'name',
        'slug',
        'color',
        'description',
        'type',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    protected function slugSourceColumn(): string
    {
        return 'name';
    }

    /**
     * @return array<string, int|string|null>
     */
    protected function slugScopeColumns(): array
    {
        return [
            'organization_id' => $this->organization_id,
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function projects(): MorphToMany
    {
        return $this->morphedByMany(Project::class, 'taggable')
            ->withPivot(['tagged_by_user_id'])
            ->withTimestamps();
    }

    public function displayName(): string
    {
        if (! $this->is_system) {
            return $this->name;
        }

        $translationKey = 'superadmin.relation_resources.tags.system_names.'.
            LocalizedCodeLabel::segment((string) ($this->slug ?: $this->name));

        if (trans()->has($translationKey)) {
            return __($translationKey);
        }

        return $this->name;
    }

    public function typeLabel(): string
    {
        return LocalizedCodeLabel::translate('superadmin.relation_resources.tags.types', $this->type);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderBy('type')
            ->orderBy('name')
            ->orderBy('id');
    }

    public function scopeWithIndexRelations(Builder $query): Builder
    {
        return $query->with([
            'organization:id,name',
        ]);
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

        return $query->where('organization_id', (int) $organizationId);
    }
}
