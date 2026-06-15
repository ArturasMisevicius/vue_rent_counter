<?php

declare(strict_types=1);

namespace App\View\Components\Shell;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Vite;
use Illuminate\View\Component;

class SessionExpiryMonitor extends Component
{
    public int $sessionLifetimeMs;

    public ?string $cspNonce;

    public function __construct()
    {
        $this->sessionLifetimeMs = max((int) config('session.lifetime', 120), 1) * 60 * 1000;
        $this->cspNonce = Vite::cspNonce();
    }

    public function render(): View
    {
        return view('components.shell.session-expiry-monitor');
    }
}
