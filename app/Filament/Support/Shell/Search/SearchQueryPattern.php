<?php

declare(strict_types=1);

namespace App\Filament\Support\Shell\Search;

final class SearchQueryPattern
{
    public function __construct(
        public readonly string $normalized,
    ) {}

    public static function from(string $query): self
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($query)) ?? '';

        return new self($normalized);
    }

    public function likePattern(): string
    {
        if (mb_strlen($this->normalized) <= 3) {
            return '%'.$this->normalized.'%';
        }

        return $this->normalized.'%';
    }
}
