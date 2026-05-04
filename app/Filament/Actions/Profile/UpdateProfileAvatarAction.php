<?php

declare(strict_types=1);

namespace App\Filament\Actions\Profile;

use App\Filament\Support\Profile\CroppedAvatarImage;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UpdateProfileAvatarAction
{
    /**
     * @param  array{avatar: string}  $attributes
     */
    public function handle(User $user, array $attributes): User
    {
        $avatar = CroppedAvatarImage::fromDataUrl($attributes['avatar']);

        if (filled($user->avatar_disk) && filled($user->avatar_path)) {
            Storage::disk((string) $user->avatar_disk)->delete((string) $user->avatar_path);
        }

        $filename = Str::uuid()->toString().'.'.$avatar->extension;
        $path = 'avatars/users/'.$user->getKey().'/'.$filename;

        Storage::disk('local')->put($path, $avatar->contents);

        $user->forceFill([
            'avatar_disk' => 'local',
            'avatar_path' => $path,
            'avatar_mime_type' => $avatar->mimeType,
            'avatar_updated_at' => now(),
        ])->save();

        return $user->refresh();
    }
}
