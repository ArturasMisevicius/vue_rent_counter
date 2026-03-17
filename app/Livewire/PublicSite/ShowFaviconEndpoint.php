<?php

namespace App\Livewire\PublicSite;

use Livewire\Component;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ShowFaviconEndpoint extends Component
{
    public function show(): BinaryFileResponse
    {
        return response()->file(resource_path('icons/favicon.ico'), [
            'Cache-Control' => 'public, max-age=604800',
            'Content-Type' => 'image/x-icon',
        ]);
    }
}
