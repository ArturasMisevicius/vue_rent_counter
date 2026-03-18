<?php

declare(strict_types=1);

namespace App\Services\Security;

use Illuminate\Support\Facades\Vite;

final class ViteCspIntegration
{
    public function apply(string $nonce): string
    {
        Vite::useCspNonce($nonce);

        config()->set('livewire.csp_safe', true);

        return Vite::cspNonce() ?? $nonce;
    }
}
