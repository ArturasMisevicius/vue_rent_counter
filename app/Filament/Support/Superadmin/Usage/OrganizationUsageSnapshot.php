<?php

namespace App\Filament\Support\Superadmin\Usage;

final readonly class OrganizationUsageSnapshot
{
    public function __construct(
        public int $propertiesUsed,
        public int $propertiesLimit,
        public int $tenantsUsed,
        public int $tenantsLimit,
        public int $metersUsed,
        public int $metersLimit,
        public int $invoicesUsed,
        public int $invoicesLimit,
    ) {}

    public static function zero(): self
    {
        return new self(
            propertiesUsed: 0,
            propertiesLimit: 0,
            tenantsUsed: 0,
            tenantsLimit: 0,
            metersUsed: 0,
            metersLimit: 0,
            invoicesUsed: 0,
            invoicesLimit: 0,
        );
    }

    /**
     * @return list<array{key: string, current: int, limit: int, tone: string, percentage: int}>
     */
    public function rows(): array
    {
        return [
            $this->row('properties', $this->propertiesUsed, $this->propertiesLimit),
            $this->row('tenants', $this->tenantsUsed, $this->tenantsLimit),
            $this->row('meters', $this->metersUsed, $this->metersLimit),
            $this->row('invoices', $this->invoicesUsed, $this->invoicesLimit),
        ];
    }

    /**
     * @return array{key: string, current: int, limit: int, tone: string, percentage: int}
     */
    private function row(string $key, int $current, int $limit): array
    {
        $percentage = $limit > 0 ? (int) min(100, round(($current / $limit) * 100)) : 0;

        return [
            'key' => $key,
            'current' => $current,
            'limit' => $limit,
            'tone' => match (true) {
                $percentage >= 95 => 'danger',
                $percentage >= 80 => 'warning',
                default => 'default',
            },
            'percentage' => $percentage,
        ];
    }
}
