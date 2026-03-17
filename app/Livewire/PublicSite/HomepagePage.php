<?php

namespace App\Livewire\PublicSite;

use App\Filament\Support\PublicSite\HomepageContent;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class HomepagePage extends Component
{
    public function render(HomepageContent $homepageContent): View
    {
        return view('welcome', [
            'page' => $homepageContent->toArray(),
        ]);
    }
}
