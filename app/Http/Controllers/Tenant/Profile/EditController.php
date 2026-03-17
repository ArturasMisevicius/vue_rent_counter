<?php

namespace App\Http\Controllers\Tenant\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EditController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('tenant.profile.edit', [
            'tenant' => $request->user(),
            'supportedLocales' => config('tenanto.locales', []),
        ]);
    }
}
