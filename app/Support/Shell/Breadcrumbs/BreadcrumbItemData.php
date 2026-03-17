<?php

namespace App\Support\Shell\Breadcrumbs;

class BreadcrumbItemData
{
    public function __construct(
        public string $label,
        public ?string $url = null,
        public bool $isCurrent = false,
    ) {}

    /**
     * @param  self|array{label?: string, url?: string|null, current?: bool}|string  $item
     */
    public static function from(self|array|string $item): self
    {
        if ($item instanceof self) {
            return $item;
        }

        if (is_string($item)) {
            return new self($item, isCurrent: true);
        }

        return new self(
            label: (string) ($item['label'] ?? ''),
            url: $item['url'] ?? null,
            isCurrent: (bool) ($item['current'] ?? false),
        );
    }

    public function asCurrent(): self
    {
        return new self(
            label: $this->label,
            url: null,
            isCurrent: true,
        );
    }
}
