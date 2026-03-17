<?php

namespace App\Support\Auth;

use App\Models\User;
use Illuminate\Http\Request;

class ImpersonationManager
{
    /**
     * @return array{id: int, name: string, email: string}|null
     */
    public function current(Request $request): ?array
    {
        $id = $request->session()->get('impersonator_id');
        $name = $request->session()->get('impersonator_name');
        $email = $request->session()->get('impersonator_email');

        if (! is_int($id) || ! is_string($name) || ! is_string($email)) {
            return null;
        }

        return [
            'id' => $id,
            'name' => $name,
            'email' => $email,
        ];
    }

    public function isImpersonating(Request $request): bool
    {
        return $this->current($request) !== null;
    }

    public function forget(Request $request): void
    {
        $request->session()->forget([
            'impersonator_id',
            'impersonator_name',
            'impersonator_email',
        ]);
    }

    public function resolveImpersonator(Request $request): ?User
    {
        $current = $this->current($request);

        if ($current === null) {
            return null;
        }

        return User::query()
            ->select(['id', 'name', 'email', 'role', 'status', 'locale', 'organization_id', 'last_login_at', 'password', 'remember_token'])
            ->find($current['id']);
    }
}
