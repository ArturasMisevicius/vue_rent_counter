<?php

declare(strict_types=1);

namespace App\Services\Operations;

use JsonException;

final class PhaseOneGuardrailsBranchProtectionService
{
    private const REPOSITORY = 'ArturasMisevicius/vue_rent_counter';

    private const BRANCH = 'main';

    private const REQUIRED_CHECK = 'Phase 1 Guardrails';

    /**
     * @return array{
     *     repository: string,
     *     branch: string,
     *     required_check: string,
     *     endpoint: string,
     *     payload: array{strict: bool, checks: list<array{context: string, app_id: null}>},
     *     payload_json: string,
     *     apply_command: string,
     *     verify_command: string,
     *     token_configured: bool
     * }
     *
     * @throws JsonException
     */
    public function report(): array
    {
        $endpoint = sprintf(
            'https://api.github.com/repos/%s/branches/%s/protection/required_status_checks',
            self::REPOSITORY,
            self::BRANCH,
        );

        $payload = [
            'strict' => true,
            'checks' => [
                [
                    'context' => self::REQUIRED_CHECK,
                    'app_id' => null,
                ],
            ],
        ];

        $compactPayload = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $payloadJson = json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return [
            'repository' => self::REPOSITORY,
            'branch' => self::BRANCH,
            'required_check' => self::REQUIRED_CHECK,
            'endpoint' => $endpoint,
            'payload' => $payload,
            'payload_json' => $payloadJson,
            'apply_command' => sprintf(
                'curl -fsSL -X PATCH -H "Accept: application/vnd.github+json" -H "Authorization: Bearer ${GITHUB_TOKEN:?GITHUB_TOKEN is required}" %s -d \'%s\'',
                $endpoint,
                $compactPayload,
            ),
            'verify_command' => sprintf(
                'curl -fsSL -H "Accept: application/vnd.github+json" -H "Authorization: Bearer ${GITHUB_TOKEN:?GITHUB_TOKEN is required}" %s | rg \'%s\'',
                $endpoint,
                self::REQUIRED_CHECK,
            ),
            'token_configured' => filled((string) config('services.github.token')),
        ];
    }
}
