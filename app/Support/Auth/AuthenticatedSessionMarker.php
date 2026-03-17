<?php

namespace App\Support\Auth;

use App\Enums\OrganizationStatus;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;

class AuthenticatedSessionMarker
{
    private const COOKIE_NAME = 'tenanto_auth_session';

    public function make(User $user): Cookie
    {
        return cookie(
            self::COOKIE_NAME,
            (string) $user->getKey(),
            (int) config('session.lifetime')
        );
    }

    public function forget(): Cookie
    {
        return cookie()->forget(self::COOKIE_NAME);
    }

    public function shouldFlashSessionExpired(Request $request): bool
    {
        $userId = $this->userId($request);

        if ($userId === null) {
            return false;
        }

        $user = User::query()
            ->select(['id', 'status', 'organization_id'])
            ->with(['organization:id,status'])
            ->find($userId);

        if ($user === null) {
            return false;
        }

        if (
            $user->status === UserStatus::SUSPENDED ||
            $user->organization?->status === OrganizationStatus::SUSPENDED
        ) {
            return false;
        }

        return true;
    }

    private function userId(Request $request): ?int
    {
        $value = $request->cookie(self::COOKIE_NAME);

        if (! is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }
}
