<?php

namespace App\Filament\Resources\Providers\Schemas;

use App\Enums\ServiceType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProviderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.providers.sections.details'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('admin.providers.fields.name'))
                            ->required()
                            ->maxLength(255),
                        Select::make('service_type')
                            ->label(__('admin.providers.fields.service_type'))
                            ->options(
                                collect(ServiceType::cases())
                                    ->mapWithKeys(fn (ServiceType $serviceType): array => [
                                        $serviceType->value => __('admin.providers.service_types.'.$serviceType->value),
                                    ])
                                    ->all(),
                            )
                            ->required(),
                        TextInput::make('contact_info.phone')
                            ->label(__('admin.providers.fields.phone'))
                            ->maxLength(255),
                        TextInput::make('contact_info.email')
                            ->label(__('admin.providers.fields.email'))
                            ->email()
                            ->maxLength(255),
                        TextInput::make('contact_info.website')
                            ->label(__('admin.providers.fields.website'))
                            ->url()
                            ->maxLength(255),
                    ])
                    ->columns(2),
            ]);
    }
}
