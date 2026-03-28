<?php

declare(strict_types=1);

namespace App\Filament\Support\Audit;

use App\Filament\Support\Auth\ImpersonationManager;
use Illuminate\Http\Request;

final class ImpersonationAuditContext
{
    public function __construct(
        private readonly ImpersonationManager $impersonationManager,
    ) {}

    /**
     * @return array{
     *     impersonation: array{
     *         session_id: string|null,
     *         started_at: string|null,
     *         expires_at: string|null,
     *         impersonator: array{id: int, name: string, email: string},
     *         impersonated_user: array{id: int|null, name: string|null, email: string|null}
     *     }
     * }|array{}
     */
    public function metadata(?Request $request = null): array
    {
        $current = $this->current($request);

        if ($current === null) {
            return [];
        }

        return [
            'impersonation' => $current,
        ];
    }

    /**
     * @return array{
     *     session_id: string|null,
     *     started_at: string|null,
     *     expires_at: string|null,
     *     impersonator: array{id: int, name: string, email: string},
     *     impersonated_user: array{id: int|null, name: string|null, email: string|null}
     * }|null
     */
    public function current(?Request $request = null): ?array
    {
        $request ??= request();

        if (! $request instanceof Request) {
            return null;
        }

        $current = $this->impersonationManager->current($request);

        if ($current === null) {
            return null;
        }

        return [
            'session_id' => is_string($current['impersonation_session_id'] ?? null) ? $current['impersonation_session_id'] : null,
            'started_at' => is_string($current['impersonation_started_at'] ?? null) ? $current['impersonation_started_at'] : null,
            'expires_at' => is_string($current['impersonation_expires_at'] ?? null) ? $current['impersonation_expires_at'] : null,
            'impersonator' => [
                'id' => (int) $current['id'],
                'name' => (string) $current['name'],
                'email' => (string) $current['email'],
            ],
            'impersonated_user' => [
                'id' => is_int($current['impersonated_user_id'] ?? null) ? $current['impersonated_user_id'] : null,
                'name' => is_string($current['impersonated_user_name'] ?? null) ? $current['impersonated_user_name'] : null,
                'email' => is_string($current['impersonated_user_email'] ?? null) ? $current['impersonated_user_email'] : null,
            ],
        ];
    }
}
