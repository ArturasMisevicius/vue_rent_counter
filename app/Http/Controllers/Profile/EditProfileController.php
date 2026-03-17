<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class EditProfileController extends Controller
{
    public function __invoke(): View|RedirectResponse
    {
        $user = request()->user();

        if ($user?->isTenant() && Route::has('tenant.profile.edit')) {
            return redirect()->route('tenant.profile.edit');
        }

        if ($user?->isAdminLike() && Route::has('filament.admin.pages.profile')) {
            return redirect()->route('filament.admin.pages.profile');
        }

        return view('profile.edit');
    }
}
