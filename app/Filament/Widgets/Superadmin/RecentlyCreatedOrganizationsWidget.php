<?php

namespace App\Filament\Widgets\Superadmin;

use App\Enums\OrganizationStatus;
use App\Models\Organization;
use App\Support\Superadmin\Usage\OrganizationUsageReader;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentlyCreatedOrganizationsWidget extends TableWidget
{
    protected static bool $isLazy = false;

    protected function organizationUsageReader(): OrganizationUsageReader
    {
        return app(OrganizationUsageReader::class);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Recently Created Organizations')
            ->description('Newest platform customers and their current domain coverage.')
            ->poll('60s')
            ->paginated(false)
            ->query(fn (): Builder => Organization::query()
                ->select([
                    'id',
                    'name',
                    'slug',
                    'status',
                    'created_at',
                ])
                ->orderByDesc('created_at')
                ->limit(5))
            ->columns([
                TextColumn::make('name')
                    ->label('Organization')
                    ->weight('medium')
                    ->description(fn (Organization $record): string => $this->organizationUsageReader()->forOrganization($record)->summary()),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (OrganizationStatus $state): string => str($state->value)->headline()->toString()),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->formatStateUsing(fn ($state): string => $state?->format('M j, Y') ?? 'Unknown'),
            ]);
    }
}
