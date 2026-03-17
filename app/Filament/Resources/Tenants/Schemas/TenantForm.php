<?php

namespace App\Filament\Resources\Tenants\Schemas;

use App\Enums\UserStatus;
use App\Models\Property;
use App\Models\User;
use App\Support\Admin\OrganizationContext;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TenantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.tenants.sections.details'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('admin.tenants.fields.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label(__('admin.tenants.fields.email'))
                            ->email()
                            ->required()
                            ->maxLength(255),
                        Select::make('locale')
                            ->label(__('admin.tenants.fields.locale'))
                            ->options(config('tenanto.locales', []))
                            ->required(),
                        Select::make('status')
                            ->label(__('admin.tenants.fields.status'))
                            ->options([
                                UserStatus::ACTIVE->value => 'Active',
                                UserStatus::INACTIVE->value => 'Inactive',
                            ])
                            ->required(),
                        Select::make('property_id')
                            ->label(__('admin.tenants.fields.property'))
                            ->options(function (): array {
                                $organizationId = app(OrganizationContext::class)->currentOrganizationId();

                                if ($organizationId === null) {
                                    return [];
                                }

                                return Property::query()
                                    ->select(['id', 'organization_id', 'building_id', 'name', 'unit_number'])
                                    ->where('organization_id', $organizationId)
                                    ->with(['building:id,name'])
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(fn (Property $property): array => [
                                        $property->id => trim($property->name.' '.$property->unit_number.' '.$property->building?->name),
                                    ])
                                    ->all();
                            })
                            ->searchable()
                            ->preload()
                            ->default(fn (?User $record): ?int => $record?->currentPropertyAssignment?->property_id),
                        TextInput::make('unit_area_sqm')
                            ->label(__('admin.tenants.fields.unit_area_sqm'))
                            ->numeric()
                            ->minValue(0)
                            ->default(fn (?User $record): mixed => $record?->currentPropertyAssignment?->unit_area_sqm),
                    ])
                    ->columns(2),
            ]);
    }
}
