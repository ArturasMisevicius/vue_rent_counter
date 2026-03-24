<?php

namespace App\Filament\Support\Superadmin\Integration\Probes;

use App\Enums\IntegrationHealthStatus;
use App\Filament\Support\Superadmin\Integration\Contracts\IntegrationProbe;
use Illuminate\Support\Facades\Mail;
use Throwable;

class MailProbe implements IntegrationProbe
{
    public function key(): string
    {
        return 'mail';
    }

    public function label(): string
    {
        return __('superadmin.integration_health.probes.mail.label');
    }

    public function check(): array
    {
        $startedAt = hrtime(true);
        $mailer = (string) config('mail.default', '');
        $transport = (string) config("mail.mailers.{$mailer}.transport", '');

        try {
            if (blank($mailer) || blank($transport)) {
                return [
                    'status' => IntegrationHealthStatus::FAILED,
                    'response_time_ms' => (int) ((hrtime(true) - $startedAt) / 1_000_000),
                    'summary' => __('superadmin.integration_health.probes.mail.summary_missing_configuration'),
                    'details' => [
                        'mailer' => $mailer,
                        'transport' => $transport,
                    ],
                ];
            }

            Mail::mailer($mailer);

            $status = in_array($transport, ['array', 'log'], true)
                ? IntegrationHealthStatus::DEGRADED
                : IntegrationHealthStatus::HEALTHY;
            $summary = $status === IntegrationHealthStatus::DEGRADED
                ? __('superadmin.integration_health.probes.mail.summary_degraded', ['mailer' => $mailer, 'transport' => $transport])
                : __('superadmin.integration_health.probes.mail.summary_healthy', ['mailer' => $mailer]);

            return [
                'status' => $status,
                'response_time_ms' => (int) ((hrtime(true) - $startedAt) / 1_000_000),
                'summary' => $summary,
                'details' => [
                    'mailer' => $mailer,
                    'transport' => $transport,
                ],
            ];
        } catch (Throwable $exception) {
            return [
                'status' => IntegrationHealthStatus::FAILED,
                'response_time_ms' => (int) ((hrtime(true) - $startedAt) / 1_000_000),
                'summary' => __('superadmin.integration_health.probes.mail.summary_failed'),
                'details' => [
                    'mailer' => $mailer,
                    'transport' => $transport,
                    'error' => $exception->getMessage(),
                ],
            ];
        }
    }
}
