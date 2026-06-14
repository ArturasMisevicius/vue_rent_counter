<?php

declare(strict_types=1);

namespace App\Filament\Support\Admin\BillingReview;

use Illuminate\Validation\ValidationException;

final readonly class InvoiceReadinessResult
{
    /**
     * @param  array<int, string>  $blockingErrors
     * @param  array<int, string>  $warnings
     */
    public function __construct(
        public array $blockingErrors,
        public array $warnings,
    ) {}

    public function isReady(bool $acceptWarnings = false): bool
    {
        if ($this->blockingErrors !== []) {
            return false;
        }

        return $acceptWarnings || $this->warnings === [];
    }

    public function throwIfBlocked(bool $acceptWarnings = false): void
    {
        if ($this->isReady($acceptWarnings)) {
            return;
        }

        throw ValidationException::withMessages([
            'invoice' => $this->blockingErrors !== []
                ? $this->blockingErrors
                : $this->warnings,
        ]);
    }
}
