<?php

declare(strict_types=1);

namespace App\View\Components\Shell;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Brand extends Component
{
    public string $badgeClass;

    public string $titleClass;

    public string $taglineClass;

    public function __construct(public bool $light = false)
    {
        $this->badgeClass = $light
            ? 'border-white/20 bg-white/10 text-white'
            : 'border-slate-200 bg-slate-100 text-slate-900';
        $this->titleClass = $light ? 'text-white' : 'text-slate-950';
        $this->taglineClass = $light ? 'text-white/65' : 'text-slate-500';
    }

    public function render(): View
    {
        return view('components.shell.brand');
    }
}
