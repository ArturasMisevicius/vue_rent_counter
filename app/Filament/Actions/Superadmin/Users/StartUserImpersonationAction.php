<?php

namespace App\Filament\Actions\Superadmin\Users;

use App\Models\User;
use App\Services\ImpersonationService;

class StartUserImpersonationAction
{
    public function __construct(
        private readonly ImpersonationService $impersonationService,
    ) {}

    public function handle(User $impersonator, User $target): void
    {
        $this->impersonationService->start($impersonator, $target);
    }
}
