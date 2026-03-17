<?php

namespace App\Filament\Support\Shell\Search\Contracts;

use App\Filament\Support\Shell\Search\Data\GlobalSearchResultData;
use App\Models\User;

interface GlobalSearchProvider
{
    public function group(): string;

    /**
     * @return array<int, GlobalSearchResultData>
     */
    public function search(User $user, string $query): array;
}
