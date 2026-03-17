<?php

namespace App\Http\Controllers\Tenant\Readings;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class CreateController extends Controller
{
    public function __invoke(): View
    {
        return view('tenant.readings.create');
    }
}
