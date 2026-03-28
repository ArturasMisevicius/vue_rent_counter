<?php

declare(strict_types=1);

namespace App\Filament\Resources\Projects\Schemas;

use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Filament\Support\Admin\OrganizationContext;
use App\Models\Building;
use App\Models\Organization;
use App\Models\Property;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identity')
                    ->schema([
                        Select::make('organization_id')
                            ->options(fn (): array => Organization::query()
                                ->select(['id', 'name'])
                                ->ordered()
                                ->pluck('name', 'id')
                                ->all())
                            ->default(fn (): ?int => app(OrganizationContext::class)->currentOrganizationId())
                            ->searchable()
                            ->required()
                            ->live(),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('reference_number')
                            ->maxLength(50),
                        Select::make('building_id')
                            ->options(fn (callable $get): array => Building::query()
                                ->select(['id', 'name', 'organization_id'])
                                ->when(
                                    filled($get('organization_id')),
                                    fn (Builder $query): Builder => $query->where('organization_id', (int) $get('organization_id')),
                                )
                                ->ordered()
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->live(),
                        Select::make('property_id')
                            ->options(fn (callable $get): array => Property::query()
                                ->select(['id', 'name', 'organization_id', 'building_id'])
                                ->when(
                                    filled($get('building_id')),
                                    fn (Builder $query): Builder => $query->where('building_id', (int) $get('building_id')),
                                    fn (Builder $query): Builder => filled($get('organization_id'))
                                        ? $query->where('organization_id', (int) $get('organization_id'))
                                        : $query,
                                )
                                ->ordered()
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable(),
                        Select::make('type')
                            ->options(collect(ProjectType::cases())->mapWithKeys(
                                fn (ProjectType $type): array => [$type->value => $type->getLabel()],
                            )->all())
                            ->required(),
                        Select::make('priority')
                            ->options(collect(ProjectPriority::cases())->mapWithKeys(
                                fn (ProjectPriority $priority): array => [$priority->value => $priority->getLabel()],
                            )->all())
                            ->required(),
                    ])->columns(2),
                Section::make('Schedule & Budget')
                    ->schema([
                        DatePicker::make('estimated_start_date'),
                        DatePicker::make('actual_start_date'),
                        DatePicker::make('estimated_end_date'),
                        DatePicker::make('actual_end_date')
                            ->visible(fn (callable $get): bool => $get('status') === ProjectStatus::COMPLETED->value),
                        TextInput::make('budget_amount')
                            ->numeric()
                            ->prefix('EUR'),
                        TextInput::make('actual_cost')
                            ->numeric()
                            ->disabled(),
                        Toggle::make('cost_passed_to_tenant'),
                        Toggle::make('requires_approval'),
                    ])->columns(2),
                Section::make('Team')
                    ->schema([
                        Select::make('manager_id')
                            ->options(fn (callable $get): array => User::query()
                                ->select(['id', 'name', 'organization_id'])
                                ->when(
                                    filled($get('organization_id')),
                                    fn (Builder $query): Builder => $query->where('organization_id', (int) $get('organization_id')),
                                )
                                ->orderedByName()
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable(),
                        TextInput::make('external_contractor'),
                        TextInput::make('contractor_contact'),
                        TextInput::make('contractor_reference'),
                    ])->columns(2),
                Section::make('Internal')
                    ->schema([
                        RichEditor::make('description')
                            ->columnSpanFull(),
                        RichEditor::make('notes')
                            ->columnSpanFull(),
                        Select::make('status')
                            ->options(collect(ProjectStatus::cases())->mapWithKeys(
                                fn (ProjectStatus $status): array => [$status->value => $status->getLabel()],
                            )->all())
                            ->required(),
                        Textarea::make('cancellation_reason')
                            ->visible(fn (callable $get): bool => $get('status') === ProjectStatus::CANCELLED->value)
                            ->required(fn (callable $get): bool => $get('status') === ProjectStatus::CANCELLED->value),
                        KeyValue::make('metadata')
                            ->nullable()
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }
}
