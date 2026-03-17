<?php

namespace App\Filament\Resources\Providers\Schemas;

use App\Enums\ServiceType;
use App\Support\Admin\OrganizationContext;
use Filament\Forms\Components\Hidden;
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
                        Hidden::make('organization_id')
                            ->default(fn (): ?int => app(OrganizationContext::class)->currentOrganizationId()),
                        TextInput::make('name')
                            ->label(__('admin.providers.fields.name'))
                            ->required()
                            ->maxLength(255),
                        Select::make('service_type')
                            ->label(__('admin.providers.fields.service_type'))
                            ->options(
                                collect(ServiceType::cases())
                                    ->mapWithKeys(fn (ServiceType $type): array => [
                                        $type->value => __('admin.providers.types.'.$type->value),
                                    ])
                                    ->all(),
                            )
                            ->required(),
                        TextInput::make('contact_info.email')
                            ->label(__('admin.providers.fields.contact_email'))
                            ->email()
                            ->maxLength(255),
                        TextInput::make('contact_info.phone')
                            ->label(__('admin.providers.fields.contact_phone'))
                            ->maxLength(255),
                        TextInput::make('contact_info.website')
                            ->label(__('admin.providers.fields.contact_website'))
                            ->maxLength(255),
                    ])
                    ->columns(2),
            ]);
    }
}
