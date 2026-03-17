<?php

namespace App\Http\Controllers\Tenant\Profile;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class EditController extends Controller
{
    public function __invoke(): View
    {
        return view('tenant.profile.edit');
    }
}
