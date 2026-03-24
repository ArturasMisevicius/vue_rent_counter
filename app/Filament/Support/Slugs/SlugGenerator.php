<?php

namespace App\Filament\Support\Slugs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SlugGenerator
{
    /**
     * @param  array<string, int|string|null>  $scope
     */
    public function generate(Model $model, string $source, array $scope = []): string
    {
        $baseSlug = Str::slug($source);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'record';

        $slug = $baseSlug;
        $suffix = 2;

        while ($this->slugExists($model, $slug, $scope)) {
            $slug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    /**
     * @param  array<string, int|string|null>  $scope
     */
    private function slugExists(Model $model, string $slug, array $scope): bool
    {
        $query = $model->newQueryWithoutScopes()
            ->select([$model->getKeyName()])
            ->where('slug', $slug);

        foreach ($scope as $column => $value) {
            if ($value === null) {
                $query->whereNull($column);

                continue;
            }

            $query->where($column, $value);
        }

        if ($model->exists) {
            $query->where($model->getKeyName(), '!=', $model->getKey());
        }

        return $query->exists();
    }
}
