<?php

declare(strict_types=1);

namespace App\View\Components\Shell;

use App\Filament\Support\Shell\UserAvatarColor;
use App\Filament\Support\View\BladeViewData;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Component;

class UserAvatar extends Component
{
    /**
     * @var array{background: string, text: string}
     */
    public array $palette;

    public string $initials;

    public ?string $avatarUrl;

    public function __construct(public User $user, public bool $compact = false)
    {
        $this->initials = BladeViewData::initials($user->name);
        $this->palette = app(UserAvatarColor::class)->for($user->name);
        $this->avatarUrl = filled($user->avatar_path) && Route::has('profile.avatar.show')
            ? route('profile.avatar.show', ['v' => $user->avatar_updated_at?->getTimestamp() ?? $user->updated_at?->getTimestamp()])
            : null;
    }

    public function render(): View
    {
        return view('components.shell.user-avatar');
    }
}
