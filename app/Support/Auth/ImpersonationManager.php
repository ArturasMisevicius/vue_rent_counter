<?php

namespace App\Support\Auth;

use App\Models\User;
use Illuminate\Contracts\Session\Session;

class ImpersonationManager
{
    public const IMPERSONATOR_ID = 'impersonator_id';

    public const IMPERSONATOR_EMAIL = 'impersonator_email';

    public const IMPERSONATOR_NAME = 'impersonator_name';

    public function __construct(
        private readonly Session $session,
    ) {}

    public function isImpersonating(): bool
    {
        return filled($this->session->get(self::IMPERSONATOR_ID));
    }

    /**
     * @return array{id: int, email: string, name: string}|null
     */
    public function bannerData(): ?array
    {
        if (! $this->isImpersonating()) {
            return null;
        }

        return [
            'id' => (int) $this->session->get(self::IMPERSONATOR_ID),
            'email' => (string) $this->session->get(self::IMPERSONATOR_EMAIL, ''),
            'name' => (string) $this->session->get(self::IMPERSONATOR_NAME, ''),
        ];
    }

    public function stop(): ?User
    {
        $impersonatorId = $this->session->get(self::IMPERSONATOR_ID);

        $this->session->forget([
            self::IMPERSONATOR_ID,
            self::IMPERSONATOR_EMAIL,
            self::IMPERSONATOR_NAME,
        ]);

        if (blank($impersonatorId)) {
            return null;
        }

        return User::query()
            ->select(['id', 'name', 'email', 'role', 'organization_id', 'status', 'locale', 'last_login_at', 'password', 'remember_token'])
            ->find($impersonatorId);
    }
}
