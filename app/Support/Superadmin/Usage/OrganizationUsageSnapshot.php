<?php

namespace App\Support\Superadmin\Usage;

final readonly class OrganizationUsageSnapshot
{
    public function __construct(
        public int $buildings,
        public int $properties,
        public int $meters,
        public int $invoices,
    ) {}

    public static function empty(): self
    {
        return new self(
            buildings: 0,
            properties: 0,
            meters: 0,
            invoices: 0,
        );
    }

    public function summary(): string
    {
        return "{$this->buildings} buildings, {$this->properties} properties, {$this->meters} meters, {$this->invoices} invoices";
    }
}
