<?php

namespace App\Filament\Resources\Providers\Schemas;

use App\Enums\ServiceType;
use App\Filament\Support\Admin\OrganizationContext;
use App\Models\Organization;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class ProviderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.providers.sections.details'))
                    ->schema([
                        Select::make('organization_id')
                            ->label(__('superadmin.organizations.singular'))
                            ->default(fn (): ?int => app(OrganizationContext::class)->currentOrganizationId())
                            ->options(fn (): array => Organization::query()
                                ->forSuperadminControlPlane()
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->preload()
                            ->dehydratedWhenHidden()
                            ->required(function (): bool {
                                $user = Auth::user();

                                if (! $user instanceof User) {
                                    return false;
                                }

                                return $user->isSuperadmin();
                            })
                            ->visible(function (): bool {
                                $user = Auth::user();

                                if (! $user instanceof User) {
                                    return false;
                                }

                                return $user->isSuperadmin()
                                    && app(OrganizationContext::class)->currentOrganizationId() === null;
                            }),
                        TextInput::make('name')
                            ->label(__('admin.providers.fields.name'))
                            ->required()
                            ->maxLength(255),
                        Select::make('service_type')
                            ->label(__('admin.providers.fields.service_type'))
                            ->options(ServiceType::options())
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
