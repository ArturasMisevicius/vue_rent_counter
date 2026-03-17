<?php

namespace App\Filament\Resources\Organizations\Schemas;

use App\Enums\OrganizationStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrganizationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('superadmin.organizations.sections.profile'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('superadmin.organizations.columns.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->label(__('superadmin.organizations.columns.slug'))
                            ->required()
                            ->alphaDash()
                            ->maxLength(255),
                        Select::make('status')
                            ->label(__('superadmin.organizations.columns.status'))
                            ->options([
                                OrganizationStatus::ACTIVE->value => __('superadmin.organizations.status.active'),
                                OrganizationStatus::SUSPENDED->value => __('superadmin.organizations.status.suspended'),
                            ])
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }
}
