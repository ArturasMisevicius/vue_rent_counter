<?php

declare(strict_types=1);

namespace App\Livewire\Profile;

use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ShowProfileAvatarEndpoint
{
    public function show(): Response
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);
        abort_if(blank($user->avatar_disk) || blank($user->avatar_path), 404);

        $disk = (string) $user->avatar_disk;
        $path = (string) $user->avatar_path;

        abort_unless(Storage::disk($disk)->exists($path), 404);

        $contents = Storage::disk($disk)->get($path);

        abort_if($contents === null, 404);

        return response($contents, 200, [
            'Cache-Control' => 'private, max-age=300',
            'Content-Type' => $user->avatar_mime_type ?: (Storage::disk($disk)->mimeType($path) ?: 'image/png'),
        ]);
    }
}
