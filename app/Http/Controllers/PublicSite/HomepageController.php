<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Support\PublicSite\HomepageContent;
use Illuminate\View\View;

class HomepageController extends Controller
{
    public function __invoke(HomepageContent $homepageContent): View
    {
        return view('welcome', [
            'page' => $homepageContent->toArray(),
        ]);
    }
}
