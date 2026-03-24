<?php

namespace App\Models\Concerns;

use App\Filament\Support\Slugs\SlugGenerator;

trait HasGeneratedSlug
{
    abstract protected function slugSourceColumn(): string;

    /**
     * @return array<string, int|string|null>
     */
    protected function slugScopeColumns(): array
    {
        return [];
    }

    protected static function bootHasGeneratedSlug(): void
    {
        static::saving(function (self $model): void {
            $model->syncGeneratedSlug();
        });
    }

    private function syncGeneratedSlug(): void
    {
        $sourceColumn = $this->slugSourceColumn();
        $source = $this->getAttribute($sourceColumn);

        if (! is_string($source) || blank($source)) {
            return;
        }

        $sourceChanged = $this->isDirty($sourceColumn);
        $slugChanged = $this->isDirty('slug');

        if ($this->exists && (! $sourceChanged) && (! $slugChanged)) {
            return;
        }

        if ((! $this->exists) && filled($this->getAttribute('slug'))) {
            return;
        }

        $this->setAttribute(
            'slug',
            app(SlugGenerator::class)->generate($this, $source, $this->slugScopeColumns()),
        );
    }
}
