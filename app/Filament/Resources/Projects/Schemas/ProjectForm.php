<?php

declare(strict_types=1);

namespace App\Filament\Resources\Projects\Schemas;

use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Filament\Support\Admin\OrganizationContext;
use App\Models\Building;
use App\Models\Organization;
use App\Models\Project;
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
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.projects.sections.identity'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('admin.projects.fields.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('reference_number')
                            ->label(__('admin.projects.fields.reference_number'))
                            ->maxLength(50)
                            ->rule(
                                fn (callable $get, ?Project $record) => Rule::unique(Project::class, 'reference_number')
                                    ->where(fn ($query) => $query->where('organization_id', (int) $get('organization_id')))
                                    ->ignore($record),
                                fn (callable $get): bool => filled($get('organization_id')),
                            ),
                        Select::make('organization_id')
                            ->label(__('admin.projects.fields.organization'))
                            ->options(fn (): array => Organization::query()
                                ->select(['id', 'name'])
                                ->ordered()
                                ->pluck('name', 'id')
                                ->all())
                            ->default(fn (): ?int => app(OrganizationContext::class)->currentOrganizationId())
                            ->searchable()
                            ->required()
                            ->helperText(fn (callable $get, ?Project $record): ?string => ($record !== null && (int) $get('organization_id') !== $record->organization_id)
                                ? __('admin.projects.helpers.organization_change')
                                : null)
                            ->afterStateUpdated(function (Set $set): void {
                                $set('building_id', null);
                                $set('property_id', null);
                                $set('manager_id', null);
                            })
                            ->live(),
                        Select::make('building_id')
                            ->label(__('admin.projects.fields.building'))
                            ->options(fn (callable $get): array => Building::query()
                                ->select(['id', 'name', 'organization_id'])
                                ->when(
                                    filled($get('organization_id')),
                                    fn (Builder $query): Builder => $query->where('organization_id', (int) $get('organization_id')),
                                )
                                ->ordered()
                                ->get()
                                ->mapWithKeys(fn (Building $building): array => [$building->id => $building->displayName()])
                                ->all())
                            ->searchable()
                            ->afterStateUpdated(function (Set $set): void {
                                $set('property_id', null);
                            })
                            ->live(),
                        Select::make('property_id')
                            ->label(__('admin.projects.fields.property'))
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
                                ->get()
                                ->mapWithKeys(fn (Property $property): array => [$property->id => $property->displayName()])
                                ->all())
                            ->searchable(),
                        Select::make('type')
                            ->label(__('admin.projects.fields.type'))
                            ->options(self::projectTypeOptions())
                            ->required(),
                        Select::make('priority')
                            ->label(__('admin.projects.fields.priority'))
                            ->options(self::projectPriorityOptions())
                            ->required(),
                    ])->columns(2),
                Section::make(__('admin.projects.sections.schedule_budget'))
                    ->schema([
                        DatePicker::make('estimated_start_date')
                            ->label(__('admin.projects.fields.estimated_start_date')),
                        DatePicker::make('actual_start_date')
                            ->label(__('admin.projects.fields.actual_start_date'))
                            ->helperText(__('admin.projects.helpers.actual_start_override')),
                        DatePicker::make('estimated_end_date')
                            ->label(__('admin.projects.fields.estimated_end_date')),
                        DatePicker::make('actual_end_date')
                            ->label(__('admin.projects.fields.actual_end_date'))
                            ->visible(fn (callable $get): bool => $get('status') === ProjectStatus::COMPLETED->value),
                        TextInput::make('budget_amount')
                            ->label(__('admin.projects.fields.budget_amount'))
                            ->numeric()
                            ->prefix('EUR'),
                        Toggle::make('cost_passed_to_tenant')
                            ->label(__('admin.projects.fields.cost_passed_to_tenant'))
                            ->helperText(fn (callable $get): ?string => $get('cost_passed_to_tenant')
                                ? __('admin.projects.helpers.cost_passthrough_ready')
                                : null),
                        Toggle::make('requires_approval')
                            ->label(__('admin.projects.fields.requires_approval')),
                    ])->columns(2),
                Section::make(__('admin.projects.sections.team'))
                    ->schema([
                        Select::make('manager_id')
                            ->label(__('admin.projects.fields.manager'))
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
                        TextInput::make('external_contractor')
                            ->label(__('admin.projects.fields.external_contractor')),
                        TextInput::make('contractor_contact')
                            ->label(__('admin.projects.fields.contractor_contact')),
                        TextInput::make('contractor_reference')
                            ->label(__('admin.projects.fields.contractor_reference')),
                    ])->columns(2),
                Section::make(__('admin.projects.sections.internal'))
                    ->schema([
                        RichEditor::make('description')
                            ->label(__('admin.projects.fields.description'))
                            ->columnSpanFull(),
                        RichEditor::make('notes')
                            ->label(__('admin.projects.fields.notes'))
                            ->visible(function (): bool {
                                $user = request()->user();

                                return $user instanceof User && $user->isSuperadmin();
                            })
                            ->columnSpanFull(),
                        Select::make('status')
                            ->label(__('admin.projects.fields.status'))
                            ->options([ProjectStatus::DRAFT->value => ProjectStatus::DRAFT->getLabel()])
                            ->default(ProjectStatus::DRAFT->value)
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required()
                            ->visibleOn('create'),
                        Textarea::make('cancellation_reason')
                            ->label(__('admin.projects.fields.cancellation_reason'))
                            ->visible(fn (callable $get): bool => $get('status') === ProjectStatus::CANCELLED->value)
                            ->required(fn (callable $get): bool => $get('status') === ProjectStatus::CANCELLED->value),
                        KeyValue::make('metadata')
                            ->label(__('admin.projects.fields.metadata'))
                            ->nullable()
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    private static function projectPriorityOptions(): array
    {
        return collect(ProjectPriority::cases())->mapWithKeys(
            fn (ProjectPriority $priority): array => [$priority->value => $priority->getLabel()],
        )->all();
    }

    private static function projectStatusOptions(): array
    {
        return collect(ProjectStatus::cases())->mapWithKeys(
            fn (ProjectStatus $status): array => [$status->value => $status->getLabel()],
        )->all();
    }

    private static function projectTypeOptions(): array
    {
        return collect(ProjectType::cases())->mapWithKeys(
            fn (ProjectType $type): array => [$type->value => $type->getLabel()],
        )->all();
    }
}
