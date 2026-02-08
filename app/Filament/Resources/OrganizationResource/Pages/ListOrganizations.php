<?php

namespace App\Filament\Resources\OrganizationResource\Pages;

use App\Filament\Resources\OrganizationResource;
use App\Models\Organization;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;

class ListOrganizations extends ListRecords
{
    protected static string $resource = OrganizationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('export')
                ->label('Export')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->form([
                    Forms\Components\Select::make('format')
                        ->options([
                            'csv' => 'CSV',
                        ])
                        ->required()
                        ->default('csv')
                        ->native(false),
                ])
                ->action(function (array $data) {
                    $format = $data['format'] ?? 'csv';

                    if ($format !== 'csv') {
                        $format = 'csv';
                    }

                    $rows = Organization::query()->orderBy('name')->get([
                        'name',
                        'email',
                        'plan',
                        'created_at',
                    ]);

                    $csv = "Name,Email,Plan,Created At\n";

                    foreach ($rows as $row) {
                        $csv .= implode(',', [
                            '"' . str_replace('"', '""', (string) $row->name) . '"',
                            '"' . str_replace('"', '""', (string) $row->email) . '"',
                            '"' . str_replace('"', '""', (string) ($row->plan?->value ?? $row->plan)) . '"',
                            '"' . str_replace('"', '""', (string) $row->created_at) . '"',
                        ]) . "\n";
                    }

                    $filename = 'organizations-' . now()->format('Y-m-d-His') . '.csv';

                    return response()->streamDownload(function () use ($csv) {
                        echo $csv;
                    }, $filename, [
                        'Content-Type' => 'text/csv',
                    ]);
                }),
        ];
    }
}
