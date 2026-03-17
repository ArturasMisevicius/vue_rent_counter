<?php

namespace App\Support\Shell\Search\Contracts;

use App\Models\User;
use App\Support\Shell\Search\Data\GlobalSearchResultData;

interface GlobalSearchProvider
{
    public function key(): string;

    /**
     * @return list<GlobalSearchResultData>
     */
    public function search(User $user, string $query): array;
}
