<?php

declare(strict_types=1);

namespace App\View\Components\Shell;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ErrorState extends Component
{
    public function __construct(
        public int|string $status,
        public string $title,
        public string $description,
        public string $actionUrl,
    ) {
        $user = auth()->user();

        if ($user && filled($user->locale)) {
            app()->setLocale($user->locale);
        }
    }

    public function render(): View
    {
        return view('components.shell.error-state');
    }
}
