<?php

declare(strict_types=1);

namespace App\Filament\Resources\Projects\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProjectInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Project')
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('reference_number')->placeholder('—'),
                        TextEntry::make('organization.name')->label(__('superadmin.organizations.singular')),
                        TextEntry::make('building.name')->placeholder('—'),
                        TextEntry::make('property.name')->placeholder('—'),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('priority')->badge(),
                        TextEntry::make('type')->badge(),
                        TextEntry::make('manager.name')->label('Manager')->placeholder('Unassigned'),
                    ])->columns(3),
                Section::make('Schedule')
                    ->schema([
                        TextEntry::make('estimated_start_date')->date()->placeholder('—'),
                        TextEntry::make('actual_start_date')->date()->placeholder('—'),
                        TextEntry::make('estimated_end_date')->date()->placeholder('—'),
                        TextEntry::make('actual_end_date')->date()->placeholder('—'),
                        TextEntry::make('schedule_variance_days')->label('Schedule variance')->placeholder('—'),
                        TextEntry::make('completion_percentage')->suffix('%'),
                    ])->columns(3),
                Section::make('Budget')
                    ->schema([
                        TextEntry::make('budget_amount')->money('EUR')->placeholder('—'),
                        TextEntry::make('actual_cost')->money('EUR'),
                        TextEntry::make('budget_variance_amount')->label('Budget variance')->placeholder('—'),
                    ])->columns(3),
                Section::make('Details')
                    ->schema([
                        TextEntry::make('description')->html()->placeholder('—')->columnSpanFull(),
                        TextEntry::make('notes')->html()->placeholder('—')->columnSpanFull(),
                        TextEntry::make('metadata')->placeholder('—')->columnSpanFull(),
                    ]),
            ]);
    }
}
