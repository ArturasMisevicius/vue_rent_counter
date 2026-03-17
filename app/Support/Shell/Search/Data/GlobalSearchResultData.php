<?php

namespace App\Support\Shell\Search\Data;

class GlobalSearchResultData
{
    public function __construct(
        public readonly string $group,
        public readonly string $title,
        public readonly ?string $subtitle,
        public readonly ?string $url,
    ) {}

    /**
     * @return array{group: string, title: string, subtitle: ?string, url: ?string}
     */
    public function toArray(): array
    {
        return [
            'group' => $this->group,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'url' => $this->url,
        ];
    }
}
