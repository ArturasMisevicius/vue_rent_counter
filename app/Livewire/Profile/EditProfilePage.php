<?php

namespace App\Livewire\Profile;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;
use Livewire\Component;

class EditProfilePage extends Component
{
    public function mount(): void
    {
        $user = auth()->user();

        if ($user && Route::has('filament.admin.pages.profile')) {
            $this->redirectRoute('filament.admin.pages.profile');

            return;
        }

        if ($user?->isTenant() && Route::has('tenant.profile.edit')) {
            $this->redirectRoute('tenant.profile.edit');
        }
    }

    public function render(): View
    {
        return view('profile.edit');
    }
}
