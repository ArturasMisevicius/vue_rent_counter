<?php

namespace App\Filament\Support\Superadmin\Usage;

final readonly class OrganizationUsageSnapshot
{
    public function __construct(
        public int $propertiesUsed,
        public int $tenantsUsed,
        public int $metersUsed,
        public int $invoicesUsed,
    ) {}

    public static function zero(): self
    {
        return new self(
            propertiesUsed: 0,
            tenantsUsed: 0,
            metersUsed: 0,
            invoicesUsed: 0,
        );
    }
}
