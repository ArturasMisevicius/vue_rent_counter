<?php

namespace App\Support\Superadmin\Exports;

use App\Models\Organization;
use Illuminate\Support\Facades\File;
use ZipArchive;

class NullOrganizationDataExportBuilder implements OrganizationDataExportBuilder
{
    public function build(Organization $organization): array
    {
        $directory = storage_path('app/exports');
        File::ensureDirectoryExists($directory);

        $path = "{$directory}/{$organization->slug}-export.zip";
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $zip->addFromString('organization.json', (string) json_encode([
            'id' => $organization->id,
            'name' => $organization->name,
            'slug' => $organization->slug,
            'status' => $organization->status?->value,
        ], JSON_PRETTY_PRINT));
        $zip->addFromString('users.json', (string) json_encode(
            $organization->users()
                ->select(['id', 'name', 'email', 'role', 'status'])
                ->get()
                ->map(fn ($user): array => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role?->value,
                    'status' => $user->status?->value,
                ])
                ->all(),
            JSON_PRETTY_PRINT,
        ));
        $zip->addFromString('subscriptions.json', (string) json_encode(
            $organization->subscriptions()
                ->select(['id', 'plan', 'plan_name_snapshot', 'status', 'starts_at', 'expires_at'])
                ->get()
                ->map(fn ($subscription): array => [
                    'id' => $subscription->id,
                    'plan' => $subscription->plan?->value,
                    'plan_name_snapshot' => $subscription->plan_name_snapshot,
                    'status' => $subscription->status?->value,
                    'starts_at' => $subscription->starts_at?->toIso8601String(),
                    'expires_at' => $subscription->expires_at?->toIso8601String(),
                ])
                ->all(),
            JSON_PRETTY_PRINT,
        ));
        $zip->addFromString('buildings.csv', "id,name\n");
        $zip->addFromString('properties.csv', "id,name\n");
        $zip->addFromString('meters.csv', "id,name\n");
        $zip->addFromString('invoices.csv', "id,name\n");
        $zip->close();

        return [
            'path' => $path,
            'download_name' => "{$organization->slug}-export.zip",
        ];
    }
}
