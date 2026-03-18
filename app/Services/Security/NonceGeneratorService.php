<?php

declare(strict_types=1);

namespace App\Services\Security;

final class NonceGeneratorService
{
    public function generate(): string
    {
        return base64_encode(random_bytes(18));
    }
}
