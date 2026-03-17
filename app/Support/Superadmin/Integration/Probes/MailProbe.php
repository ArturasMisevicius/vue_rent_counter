<?php

namespace App\Support\Superadmin\Integration\Probes;

use App\Enums\IntegrationHealthStatus;
use App\Support\Superadmin\Integration\Contracts\IntegrationProbe;

class MailProbe implements IntegrationProbe
{
    public function key(): string
    {
        return 'mail';
    }

    public function label(): string
    {
        return 'Mail';
    }

    public function check(): array
    {
        $startedAt = hrtime(true);
        $mailer = (string) config('mail.default', '');
        $configured = filled($mailer) && filled(config("mail.mailers.{$mailer}.transport"));

        return [
            'status' => $configured ? IntegrationHealthStatus::HEALTHY : IntegrationHealthStatus::FAILED,
            'response_time_ms' => (int) ((hrtime(true) - $startedAt) / 1_000_000),
            'summary' => $configured ? 'Mail transport is configured.' : 'Mail transport is not configured.',
            'details' => [
                'mailer' => $mailer,
            ],
        ];
    }
}
