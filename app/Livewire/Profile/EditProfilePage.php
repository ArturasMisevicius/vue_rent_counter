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

        if ($user?->isTenant() && Route::has('tenant.profile.edit')) {
            $this->redirectRoute('tenant.profile.edit');

            return;
        }

        if ($user?->isAdminLike() && Route::has('filament.admin.pages.profile')) {
            $this->redirectRoute('filament.admin.pages.profile');
        }
    }

    public function render(): View
    {
        return view('profile.edit');
    }
}
