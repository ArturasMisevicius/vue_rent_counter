<?php

namespace App\Support\Shell\Search;

use App\Models\User;
use App\Support\Shell\Search\Contracts\GlobalSearchProvider;
use App\Support\Shell\Search\Data\GlobalSearchResultData;

class GlobalSearchRegistry
{
    /**
     * @var array<string, GlobalSearchProvider>
     */
    private array $providers;

    /**
     * @param  iterable<GlobalSearchProvider>  $providers
     */
    public function __construct(iterable $providers)
    {
        $this->providers = collect($providers)
            ->keyBy(fn (GlobalSearchProvider $provider): string => $provider->key())
            ->all();
    }

    /**
     * @return list<string>
     */
    public function groupsFor(User $user): array
    {
        return config('tenanto.search.groups.'.$user->role->value, []);
    }

    /**
     * @return array<string, string>
     */
    public function labelsFor(User $user): array
    {
        $labels = config('tenanto.search.labels', []);

        return collect($this->groupsFor($user))
            ->mapWithKeys(function (string $group) use ($labels): array {
                $labelKey = data_get($labels, $group);

                if (is_string($labelKey) && $labelKey !== '') {
                    return [$group => __($labelKey)];
                }

                return [$group => str($group)->replace('_', ' ')->title()->toString()];
            })
            ->all();
    }

    /**
     * @return array<string, list<GlobalSearchResultData>>
     */
    public function search(User $user, string $query): array
    {
        return collect($this->groupsFor($user))
            ->mapWithKeys(function (string $group) use ($query, $user): array {
                $provider = $this->providers[$group] ?? null;

                if ((! $provider instanceof GlobalSearchProvider) || blank(trim($query))) {
                    return [$group => []];
                }

                return [$group => $provider->search($user, $query)];
            })
            ->all();
    }
}
