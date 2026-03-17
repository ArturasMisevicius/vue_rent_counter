<?php

namespace App\Filament\Support\Superadmin\Exports;

use App\Models\Organization;
use ZipArchive;

class OrganizationDataExportBuilder
{
    public function build(Organization $organization): string
    {
        $path = storage_path('app/exports/organization-'.$organization->id.'-'.now()->timestamp.'.zip');

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        $archive = new ZipArchive;
        $archive->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $archive->addFromString('organization.json', json_encode([
            'id' => $organization->id,
            'name' => $organization->name,
            'slug' => $organization->slug,
            'status' => $organization->status?->value,
        ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

        $archive->addFromString(
            'users.csv',
            $this->csv(
                ['id', 'name', 'email', 'role', 'status'],
                $organization->users()
                    ->select(['id', 'name', 'email', 'role', 'status'])
                    ->orderBy('name')
                    ->get()
                    ->map(fn ($user): array => [
                        $user->id,
                        $user->name,
                        $user->email,
                        $user->role?->value ?? $user->role,
                        $user->status?->value ?? $user->status,
                    ])
                    ->all(),
            ),
        );

        $archive->addFromString(
            'subscriptions.csv',
            $this->csv(
                ['id', 'plan', 'status', 'starts_at', 'expires_at'],
                $organization->subscriptions()
                    ->select(['id', 'plan', 'status', 'starts_at', 'expires_at'])
                    ->orderByDesc('expires_at')
                    ->get()
                    ->map(fn ($subscription): array => [
                        $subscription->id,
                        $subscription->plan?->value ?? $subscription->plan,
                        $subscription->status?->value ?? $subscription->status,
                        optional($subscription->starts_at)->toDateString(),
                        optional($subscription->expires_at)->toDateString(),
                    ])
                    ->all(),
            ),
        );

        $archive->close();

        return $path;
    }

    /**
     * @param  list<string>  $headers
     * @param  list<array<int, mixed>>  $rows
     */
    protected function csv(array $headers, array $rows): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $headers);

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $contents = stream_get_contents($handle) ?: '';
        fclose($handle);

        return $contents;
    }
}
