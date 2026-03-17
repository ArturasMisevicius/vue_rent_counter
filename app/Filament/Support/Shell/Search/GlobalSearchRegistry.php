<?php

namespace App\Filament\Support\Shell\Search;

use App\Filament\Support\Shell\Search\Contracts\GlobalSearchProvider;
use App\Filament\Support\Shell\Search\Data\GlobalSearchResultData;
use App\Models\User;

class GlobalSearchRegistry
{
    /**
     * @param  array<int, GlobalSearchProvider>  $providers
     */
    public function __construct(
        protected array $providers,
    ) {}

    /**
     * @return array<string, string>
     */
    public function groupLabelsFor(?User $user): array
    {
        if ($user === null) {
            return [];
        }

        $groupKeys = config('tenanto.search.role_groups.'.$user->role->value, []);

        return collect($groupKeys)
            ->mapWithKeys(function (string $groupKey): array {
                $translationKey = config('tenanto.search.group_labels.'.$groupKey, $groupKey);

                return [$groupKey => __($translationKey)];
            })
            ->all();
    }

    /**
     * @return array<string, array<int, GlobalSearchResultData>>
     */
    public function search(?User $user, string $query): array
    {
        $groupLabels = $this->groupLabelsFor($user);

        $results = collect(array_keys($groupLabels))
            ->mapWithKeys(fn (string $groupKey): array => [$groupKey => []])
            ->all();

        if ($user === null || mb_strlen(trim($query)) < (int) config('tenanto.search.min_query_length', 2)) {
            return $results;
        }

        foreach ($this->providers as $provider) {
            $groupKey = $provider->group();

            if (! array_key_exists($groupKey, $results)) {
                continue;
            }

            $results[$groupKey] = [
                ...$results[$groupKey],
                ...$provider->search($user, $query),
            ];
        }

        return $results;
    }
}
