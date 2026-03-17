<?php

namespace App\Http\Controllers\Filament;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class RedirectToPublicLoginController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        return redirect()->route('login');
    }
}
