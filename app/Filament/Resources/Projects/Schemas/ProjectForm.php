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
                Section::make('Identity')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('reference_number')
                            ->maxLength(50)
                            ->rule(
                                fn (callable $get, ?Project $record) => Rule::unique(Project::class, 'reference_number')
                                    ->where(fn ($query) => $query->where('organization_id', (int) $get('organization_id')))
                                    ->ignore($record),
                                fn (callable $get): bool => filled($get('organization_id')),
                            ),
                        Select::make('organization_id')
                            ->options(fn (): array => Organization::query()
                                ->select(['id', 'name'])
                                ->ordered()
                                ->pluck('name', 'id')
                                ->all())
                            ->default(fn (): ?int => app(OrganizationContext::class)->currentOrganizationId())
                            ->searchable()
                            ->required()
                            ->helperText(fn (callable $get, ?Project $record): ?string => ($record !== null && (int) $get('organization_id') !== $record->organization_id)
                                ? 'Changing the organization will move child records such as tasks, time entries, cost records, comments, attachments, and audit logs to the new organization.'
                                : null)
                            ->afterStateUpdated(function (Set $set): void {
                                $set('building_id', null);
                                $set('property_id', null);
                                $set('manager_id', null);
                            })
                            ->live(),
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
                            ->afterStateUpdated(function (Set $set): void {
                                $set('property_id', null);
                            })
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
                            ->options(self::projectTypeOptions())
                            ->required(),
                        Select::make('priority')
                            ->options(self::projectPriorityOptions())
                            ->required(),
                    ])->columns(2),
                Section::make('Schedule & Budget')
                    ->schema([
                        DatePicker::make('estimated_start_date'),
                        DatePicker::make('actual_start_date')
                            ->helperText('This value is auto-filled when the project starts, but superadmins can override it.'),
                        DatePicker::make('estimated_end_date'),
                        DatePicker::make('actual_end_date')
                            ->visible(fn (callable $get): bool => $get('status') === ProjectStatus::COMPLETED->value),
                        TextInput::make('budget_amount')
                            ->numeric()
                            ->prefix('EUR'),
                        Toggle::make('cost_passed_to_tenant')
                            ->helperText(fn (callable $get): ?string => $get('cost_passed_to_tenant')
                                ? 'When this project is completed, the passthrough action can generate draft invoice items for the affected tenants.'
                                : null),
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
                            ->visible(function (): bool {
                                $user = request()->user();

                                return $user instanceof User && $user->isSuperadmin();
                            })
                            ->columnSpanFull(),
                        Select::make('status')
                            ->options([ProjectStatus::DRAFT->value => ProjectStatus::DRAFT->getLabel()])
                            ->default(ProjectStatus::DRAFT->value)
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required()
                            ->visibleOn('create'),
                        Textarea::make('cancellation_reason')
                            ->visible(fn (callable $get): bool => $get('status') === ProjectStatus::CANCELLED->value)
                            ->required(fn (callable $get): bool => $get('status') === ProjectStatus::CANCELLED->value),
                        KeyValue::make('metadata')
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
