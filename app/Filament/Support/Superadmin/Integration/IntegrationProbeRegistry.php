<?php

namespace App\Filament\Support\Superadmin\Integration;

use App\Filament\Support\Superadmin\Integration\Contracts\IntegrationProbe;
use InvalidArgumentException;

class IntegrationProbeRegistry
{
    /**
     * @param  iterable<IntegrationProbe>  $probes
     */
    public function __construct(
        private readonly iterable $probes,
    ) {}

    /**
     * @return list<IntegrationProbe>
     */
    public function all(): array
    {
        return is_array($this->probes) ? $this->probes : iterator_to_array($this->probes, false);
    }

    public function for(string $key): IntegrationProbe
    {
        foreach ($this->all() as $probe) {
            if ($probe->key() === $key) {
                return $probe;
            }
        }

        throw new InvalidArgumentException("Integration probe [{$key}] is not registered.");
    }
}
