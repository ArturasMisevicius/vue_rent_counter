<?php

namespace App\Filament\Widgets\Superadmin;

use App\Enums\SecurityViolationSeverity;
use App\Models\SecurityViolation;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentSecurityViolationsWidget extends TableWidget
{
    protected static bool $isLazy = false;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Recent Security Violations')
            ->description('Newest platform risk signals from the last seven days.')
            ->poll('60s')
            ->paginated(false)
            ->query(fn (): Builder => SecurityViolation::query()
                ->select([
                    'id',
                    'organization_id',
                    'severity',
                    'description',
                    'occurred_at',
                ])
                ->with([
                    'organization:id,name,slug',
                ])
                ->where('occurred_at', '>=', now()->subDays(7))
                ->orderByDesc('occurred_at')
                ->limit(5))
            ->columns([
                TextColumn::make('description')
                    ->label('Violation')
                    ->wrap()
                    ->weight('medium'),
                TextColumn::make('organization.name')
                    ->label('Organization'),
                TextColumn::make('severity')
                    ->label('Severity')
                    ->badge()
                    ->formatStateUsing(fn (SecurityViolationSeverity $state): string => $state->label()),
            ]);
    }
}
