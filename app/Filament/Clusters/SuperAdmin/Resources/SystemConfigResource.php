<?php

declare(strict_types=1);

namespace App\Filament\Clusters\SuperAdmin\Resources;

use App\Filament\Clusters\SuperAdmin;
use App\Filament\Clusters\SuperAdmin\Resources\SystemConfigResource\Pages;
use App\Models\SystemConfiguration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\TextInput;
use Filament\Schemas\Components\Textarea;
use Filament\Schemas\Components\Select;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Toggle;
use Filament\Schemas\Components\KeyValue;
use Filament\Schemas\Components\DateTimePicker;
use Filament\Schemas\Components\Get;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class SystemConfigResource extends Resource
{
    protected static ?string $model = SystemConfiguration::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $cluster = SuperAdmin::class;

    protected static ?int $navigationSort = 5;

    public static function getNavigationLabel(): string
    {
        return __('superadmin.navigation.system_config');
    }

    public static function getModelLabel(): string
    {
        return __('superadmin.config.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('superadmin.config.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('superadmin.config.sections.basic_info'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('key')
                                    ->label(__('superadmin.config.fields.key'))
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255)
                                    ->helperText(__('superadmin.config.help.key')),

                                TextInput::make('category')
                                    ->label(__('superadmin.config.fields.category'))
                                    ->required()
                                    ->maxLength(100)
                                    ->datalist([
                                        'system',
                                        'security',
                                        'features',
                                        'integrations',
                                        'notifications',
                                        'billing',
                                        'maintenance',
                                    ])
                                    ->helperText(__('superadmin.config.help.category')),
                            ]),

                        TextInput::make('name')
                            ->label(__('superadmin.config.fields.name'))
                            ->required()
                            ->maxLength(255)
                            ->helperText(__('superadmin.config.help.name')),

                        Textarea::make('description')
                            ->label(__('superadmin.config.fields.description'))
                            ->rows(3)
                            ->maxLength(500)
                            ->helperText(__('superadmin.config.help.description')),
                    ]),

                Section::make(__('superadmin.config.sections.value_settings'))
                    ->schema([
                        Select::make('type')
                            ->label(__('superadmin.config.fields.type'))
                            ->required()
                            ->options([
                                'string' => __('superadmin.config.types.string'),
                                'integer' => __('superadmin.config.types.integer'),
                                'float' => __('superadmin.config.types.float'),
                                'boolean' => __('superadmin.config.types.boolean'),
                                'array' => __('superadmin.config.types.array'),
                                'json' => __('superadmin.config.types.json'),
                            ])
                            ->live()
                            ->helperText(__('superadmin.config.help.type')),

                        Group::make()
                            ->schema([
                                TextInput::make('value')
                                    ->label(__('superadmin.config.fields.value'))
                                    ->visible(fn (Get $get) => in_array($get('type'), ['string', 'integer', 'float']))
                                    ->helperText(__('superadmin.config.help.value')),

                                Toggle::make('value')
                                    ->label(__('superadmin.config.fields.value'))
                                    ->visible(fn (Get $get) => $get('type') === 'boolean')
                                    ->helperText(__('superadmin.config.help.boolean_value')),

                                KeyValue::make('value')
                                    ->label(__('superadmin.config.fields.value'))
                                    ->visible(fn (Get $get) => $get('type') === 'array')
                                    ->helperText(__('superadmin.config.help.array_value')),

                                Textarea::make('value')
                                    ->label(__('superadmin.config.fields.value'))
                                    ->visible(fn (Get $get) => $get('type') === 'json')
                                    ->rows(5)
                                    ->helperText(__('superadmin.config.help.json_value')),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Toggle::make('is_public')
                                    ->label(__('superadmin.config.fields.is_public'))
                                    ->helperText(__('superadmin.config.help.is_public'))
                                    ->default(false),

                                Toggle::make('is_encrypted')
                                    ->label(__('superadmin.config.fields.is_encrypted'))
                                    ->helperText(__('superadmin.config.help.is_encrypted'))
                                    ->default(false),
                            ]),
                    ]),

                Section::make(__('superadmin.config.sections.validation'))
                    ->schema([
                        Textarea::make('validation_rules')
                            ->label(__('superadmin.config.fields.validation_rules'))
                            ->rows(3)
                            ->helperText(__('superadmin.config.help.validation_rules'))
                            ->placeholder('required|string|max:255'),

                        Textarea::make('allowed_values')
                            ->label(__('superadmin.config.fields.allowed_values'))
                            ->rows(3)
                            ->helperText(__('superadmin.config.help.allowed_values'))
                            ->placeholder('value1,value2,value3'),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Section::make(__('superadmin.config.sections.metadata'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('created_by')
                                    ->label(__('superadmin.config.fields.created_by'))
                                    ->disabled(),

                                TextInput::make('updated_by')
                                    ->label(__('superadmin.config.fields.updated_by'))
                                    ->disabled(),
                            ]),

                        Grid::make(2)
                            ->schema([
                                DateTimePicker::make('created_at')
                                    ->label(__('superadmin.config.fields.created_at'))
                                    ->disabled()
                                    ->native(false),

                                DateTimePicker::make('updated_at')
                                    ->label(__('superadmin.config.fields.updated_at'))
                                    ->disabled()
                                    ->native(false),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->visibleOn('edit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key')
                    ->label(__('superadmin.config.fields.key'))
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono')
                    ->copyable(),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('superadmin.config.fields.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('category')
                    ->label(__('superadmin.config.fields.category'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'system' => 'primary',
                        'security' => 'danger',
                        'features' => 'success',
                        'integrations' => 'info',
                        'notifications' => 'warning',
                        'billing' => 'purple',
                        'maintenance' => 'gray',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label(__('superadmin.config.fields.type'))
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('value')
                    ->label(__('superadmin.config.fields.value'))
                    ->limit(50)
                    ->tooltip(function ($record) {
                        if ($record->is_encrypted) {
                            return __('superadmin.config.tooltips.encrypted_value');
                        }
                        return is_string($record->value) ? $record->value : json_encode($record->value);
                    })
                    ->formatStateUsing(function ($record) {
                        if ($record->is_encrypted) {
                            return '••••••••';
                        }
                        
                        return match ($record->type) {
                            'boolean' => $record->value ? __('superadmin.config.values.true') : __('superadmin.config.values.false'),
                            'array', 'json' => is_array($record->value) ? json_encode($record->value) : $record->value,
                            default => $record->value,
                        };
                    }),

                Tables\Columns\IconColumn::make('is_public')
                    ->label(__('superadmin.config.fields.public'))
                    ->boolean()
                    ->trueIcon('heroicon-o-eye')
                    ->falseIcon('heroicon-o-eye-slash')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip(fn ($state) => $state ? 
                        __('superadmin.config.tooltips.public') : 
                        __('superadmin.config.tooltips.private')),

                Tables\Columns\IconColumn::make('is_encrypted')
                    ->label(__('superadmin.config.fields.encrypted'))
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-lock-open')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->tooltip(fn ($state) => $state ? 
                        __('superadmin.config.tooltips.encrypted') : 
                        __('superadmin.config.tooltips.not_encrypted')),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('superadmin.config.fields.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label(__('superadmin.config.filters.category'))
                    ->options([
                        'system' => __('superadmin.config.categories.system'),
                        'security' => __('superadmin.config.categories.security'),
                        'features' => __('superadmin.config.categories.features'),
                        'integrations' => __('superadmin.config.categories.integrations'),
                        'notifications' => __('superadmin.config.categories.notifications'),
                        'billing' => __('superadmin.config.categories.billing'),
                        'maintenance' => __('superadmin.config.categories.maintenance'),
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('type')
                    ->label(__('superadmin.config.filters.type'))
                    ->options([
                        'string' => __('superadmin.config.types.string'),
                        'integer' => __('superadmin.config.types.integer'),
                        'float' => __('superadmin.config.types.float'),
                        'boolean' => __('superadmin.config.types.boolean'),
                        'array' => __('superadmin.config.types.array'),
                        'json' => __('superadmin.config.types.json'),
                    ])
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('is_public')
                    ->label(__('superadmin.config.filters.is_public'))
                    ->trueLabel(__('superadmin.config.values.public_only'))
                    ->falseLabel(__('superadmin.config.values.private_only'))
                    ->placeholder(__('superadmin.config.values.all')),

                Tables\Filters\TernaryFilter::make('is_encrypted')
                    ->label(__('superadmin.config.filters.is_encrypted'))
                    ->trueLabel(__('superadmin.config.values.encrypted_only'))
                    ->falseLabel(__('superadmin.config.values.unencrypted_only'))
                    ->placeholder(__('superadmin.config.values.all')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                
                Tables\Actions\Action::make('duplicate')
                    ->label(__('superadmin.config.actions.duplicate'))
                    ->icon('heroicon-o-document-duplicate')
                    ->color('info')
                    ->form([
                        TextInput::make('new_key')
                            ->label(__('superadmin.config.fields.new_key'))
                            ->required()
                            ->unique(SystemConfiguration::class, 'key')
                            ->maxLength(255),
                    ])
                    ->action(function (SystemConfiguration $record, array $data) {
                        $newConfig = $record->replicate();
                        $newConfig->key = $data['new_key'];
                        $newConfig->name = $record->name . ' (Copy)';
                        $newConfig->save();

                        \Filament\Notifications\Notification::make()
                            ->title(__('superadmin.config.notifications.duplicated'))
                            ->success()
                            ->send();
                    }),

                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_update_category')
                        ->label(__('superadmin.config.bulk_actions.update_category'))
                        ->icon('heroicon-o-tag')
                        ->color('info')
                        ->form([
                            Select::make('category')
                                ->label(__('superadmin.config.fields.category'))
                                ->required()
                                ->options([
                                    'system' => __('superadmin.config.categories.system'),
                                    'security' => __('superadmin.config.categories.security'),
                                    'features' => __('superadmin.config.categories.features'),
                                    'integrations' => __('superadmin.config.categories.integrations'),
                                    'notifications' => __('superadmin.config.categories.notifications'),
                                    'billing' => __('superadmin.config.categories.billing'),
                                    'maintenance' => __('superadmin.config.categories.maintenance'),
                                ]),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                            $records->each(function ($record) use ($data) {
                                $record->update(['category' => $data['category']]);
                            });

                            \Filament\Notifications\Notification::make()
                                ->title(__('superadmin.config.notifications.bulk_updated'))
                                ->body(__('superadmin.config.notifications.bulk_updated_body', [
                                    'count' => $records->count(),
                                ]))
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('category')
            ->striped()
            ->searchable()
            ->persistSearchInSession()
            ->persistFiltersInSession();
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSystemConfigs::route('/'),
            'create' => Pages\CreateSystemConfig::route('/create'),
            'view' => Pages\ViewSystemConfig::route('/{record}'),
            'edit' => Pages\EditSystemConfig::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->orderBy('category')
            ->orderBy('key');
    }
}