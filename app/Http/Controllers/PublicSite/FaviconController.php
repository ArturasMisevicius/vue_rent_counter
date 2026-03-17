<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FaviconController extends Controller
{
    public function __invoke(): BinaryFileResponse
    {
        return response()->file(resource_path('icons/favicon.ico'), [
            'Cache-Control' => 'public, max-age=604800',
            'Content-Type' => 'image/x-icon',
        ]);
    }
}
