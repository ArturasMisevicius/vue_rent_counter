<?php

namespace App\Livewire\PublicSite;

use App\Filament\Support\PublicSite\HomepageContent;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class HomepagePage extends Component
{
    public function render(): View
    {
        return view('welcome', [
            'page' => $this->page,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function page(): array
    {
        return app(HomepageContent::class)->toArray();
    }
}
