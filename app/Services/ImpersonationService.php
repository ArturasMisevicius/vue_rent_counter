<?php

declare(strict_types=1);

namespace App\Services;

use App\Filament\Support\Auth\ImpersonationManager;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class ImpersonationService
{
    public function __construct(
        protected ImpersonationManager $impersonationManager,
    ) {}

    /**
     * @return array{id: int, name: string, email: string}|null
     */
    public function current(Request $request): ?array
    {
        return $this->impersonationManager->current($request);
    }

    public function stop(Request $request): ?User
    {
        $impersonator = $this->impersonationManager->resolveImpersonator($request);

        $this->impersonationManager->forget($request);

        if ($impersonator !== null) {
            Auth::guard('web')->login($impersonator);
        }

        return $impersonator;
    }

    public function start(User $impersonator, User $target, ?Request $request = null): void
    {
        $request ??= request();

        $payload = [
            'impersonator_id' => $impersonator->id,
            'impersonator_name' => $impersonator->name,
            'impersonator_email' => $impersonator->email,
        ];

        if ($request->hasSession()) {
            $request->session()->put($payload);
        } else {
            session()->put($payload);
        }

        Auth::guard('web')->login($target);
    }
}
