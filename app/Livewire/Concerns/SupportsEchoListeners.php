<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

trait SupportsEchoListeners
{
    protected function shouldUseEchoListeners(): bool
    {
        $connection = (string) config('broadcasting.default', 'null');
        $driver = (string) data_get(config('broadcasting.connections'), $connection.'.driver', $connection);

        return ! in_array($driver, ['null', 'log'], true);
    }
}
