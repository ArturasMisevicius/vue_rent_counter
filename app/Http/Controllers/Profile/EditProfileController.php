<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class EditProfileController extends Controller
{
    public function __invoke(): View
    {
        return view('profile.edit');
    }
}
