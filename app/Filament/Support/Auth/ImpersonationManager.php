<?php

declare(strict_types=1);

namespace App\Filament\Support\Auth;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

final class ImpersonationManager
{
    private const SESSION_KEYS = [
        'impersonator_id',
        'impersonator_name',
        'impersonator_email',
        'impersonated_user_id',
        'impersonated_user_name',
        'impersonated_user_email',
        'impersonation_session_id',
        'impersonation_started_at',
        'impersonation_expires_at',
    ];

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     email: string,
     *     impersonated_user_id: int|null,
     *     impersonated_user_name: string|null,
     *     impersonated_user_email: string|null,
     *     impersonation_session_id: string|null,
     *     impersonation_started_at: string|null,
     *     impersonation_expires_at: string|null
     * }|null
     */
    public function current(Request $request): ?array
    {
        $payload = $this->payload($request);

        if ($payload === null) {
            return null;
        }

        if ($this->isExpiredPayload($payload)) {
            $this->forget($request);

            return null;
        }

        return $payload;
    }

    /**
     * @return array<string, int|string|null>
     */
    public function start(Request $request, User $impersonator, User $target): array
    {
        $payload = [
            'impersonator_id' => $impersonator->id,
            'impersonator_name' => $impersonator->name,
            'impersonator_email' => $impersonator->email,
            'impersonated_user_id' => $target->id,
            'impersonated_user_name' => $target->name,
            'impersonated_user_email' => $target->email,
            'impersonation_session_id' => (string) Str::uuid(),
            'impersonation_started_at' => now()->toIso8601String(),
            'impersonation_expires_at' => now()->addHour()->toIso8601String(),
        ];

        if ($request->hasSession()) {
            $request->session()->put($payload);
        } else {
            session()->put($payload);
        }

        return $payload;
    }

    public function isImpersonating(Request $request): bool
    {
        return $this->current($request) !== null;
    }

    public function hasExpired(Request $request): bool
    {
        $payload = $this->payload($request);

        return $payload !== null && $this->isExpiredPayload($payload);
    }

    public function forget(Request $request): void
    {
        if ($request->hasSession()) {
            $request->session()->forget(self::SESSION_KEYS);

            return;
        }

        session()->forget(self::SESSION_KEYS);
    }

    public function resolveImpersonator(Request $request): ?User
    {
        $payload = $this->payload($request);

        if ($payload === null) {
            return null;
        }

        return User::query()
            ->select(['id', 'name', 'email', 'role', 'status', 'locale', 'organization_id', 'last_login_at', 'password', 'remember_token'])
            ->find($payload['id']);
    }

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     email: string,
     *     impersonated_user_id: int|null,
     *     impersonated_user_name: string|null,
     *     impersonated_user_email: string|null,
     *     impersonation_session_id: string|null,
     *     impersonation_started_at: string|null,
     *     impersonation_expires_at: string|null
     * }|null
     */
    private function payload(Request $request): ?array
    {
        $session = $request->hasSession()
            ? $request->session()
            : session();

        $id = $session->get('impersonator_id');
        $name = $session->get('impersonator_name');
        $email = $session->get('impersonator_email');

        if (! is_int($id) || ! is_string($name) || ! is_string($email)) {
            return null;
        }

        $impersonatedUserId = $session->get('impersonated_user_id');
        $impersonatedUserName = $session->get('impersonated_user_name');
        $impersonatedUserEmail = $session->get('impersonated_user_email');
        $sessionId = $session->get('impersonation_session_id');
        $startedAt = $session->get('impersonation_started_at');
        $expiresAt = $session->get('impersonation_expires_at');

        return [
            'id' => $id,
            'name' => $name,
            'email' => $email,
            'impersonated_user_id' => is_int($impersonatedUserId) ? $impersonatedUserId : null,
            'impersonated_user_name' => is_string($impersonatedUserName) ? $impersonatedUserName : null,
            'impersonated_user_email' => is_string($impersonatedUserEmail) ? $impersonatedUserEmail : null,
            'impersonation_session_id' => is_string($sessionId) ? $sessionId : null,
            'impersonation_started_at' => is_string($startedAt) ? $startedAt : null,
            'impersonation_expires_at' => is_string($expiresAt) ? $expiresAt : null,
        ];
    }

    /**
     * @param  array{impersonation_expires_at: string|null}  $payload
     */
    private function isExpiredPayload(array $payload): bool
    {
        $expiresAt = $this->parseTimestamp($payload['impersonation_expires_at']);

        return $expiresAt !== null && now()->greaterThanOrEqualTo($expiresAt);
    }

    private function parseTimestamp(?string $value): ?CarbonImmutable
    {
        if (! is_string($value) || blank($value)) {
            return null;
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (Throwable) {
            return null;
        }
    }
}
