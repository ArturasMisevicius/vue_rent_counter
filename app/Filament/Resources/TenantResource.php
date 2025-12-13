<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\TenantResource\Pages;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use UnitEnum;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\TextInput;
use Filament\Schemas\Components\Select;
use Filament\Schemas\Components\DatePicker;
use Filament\Schemas\Components\Textarea;
use Filament\Schemas\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;


final class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-users';
    
    protected static UnitEnum|string|null $navigationGroup = 'Utilities Management';
    
    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Tenant Information')
                    ->schema([
                        TextInput::make('tenant_id')
                            ->label('Tenant ID')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->helperText('Unique identifier for the tenant'),
                            
                        TextInput::make('first_name')
                            ->required()
                            ->maxLength(100),
                            
                        TextInput::make('last_name')
                            ->required()
                            ->maxLength(100),
                            
                        TextInput::make('email')
                            ->email()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                            
                        TextInput::make('phone')
                            ->tel()
                            ->maxLength(20),
                    ])
                    ->columns(2),
                    
                Section::make('Property Assignment')
                    ->schema([
                        Select::make('property_id')
                            ->label('Property')
                            ->relationship('property', 'unit_number')
                            ->getOptionLabelFromRecordUsing(fn ($record) => 
                                "{$record->building->name} - Unit {$record->unit_number}"
                            )
                            ->required()
                            ->searchable()
                            ->preload(),
                    ]),
                    
                Section::make('Lease Information')
                    ->schema([
                        DatePicker::make('lease_start_date')
                            ->required()
                            ->native(false),
                            
                        DatePicker::make('lease_end_date')
                            ->native(false)
                            ->after('lease_start_date'),
                            
                        TextInput::make('monthly_rent')
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0)
                            ->prefix('€')
                            ->helperText('Monthly rent amount'),
                            
                        TextInput::make('deposit_amount')
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0)
                            ->prefix('€')
                            ->helperText('Security deposit amount'),
                    ])
                    ->columns(2),
                    
                Section::make('Additional Information')
                    ->schema([
                        Textarea::make('notes')
                            ->maxLength(1000)
                            ->columnSpanFull(),
                            
                        Toggle::make('is_active')
                            ->label('Active Tenant')
                            ->default(true)
                            ->helperText('Inactive tenants will not receive new invoices'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tenant_id')
                    ->label('Tenant ID')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                    
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Name')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable()
                    ->getStateUsing(fn ($record) => "{$record->first_name} {$record->last_name}"),
                    
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->copyable()
                    ->placeholder('No email'),
                    
                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->copyable()
                    ->placeholder('No phone'),
                    
                Tables\Columns\TextColumn::make('property.building.name')
                    ->label('Building')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('property.unit_number')
                    ->label('Unit')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('lease_start_date')
                    ->date()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('lease_end_date')
                    ->date()
                    ->sortable()
                    ->placeholder('Ongoing'),
                    
                Tables\Columns\TextColumn::make('monthly_rent')
                    ->money('EUR')
                    ->sortable()
                    ->placeholder('Not set'),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->tooltip(fn ($record) => $record->is_active ? 'Active tenant' : 'Inactive tenant'),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('property')
                    ->relationship('property', 'unit_number')
                    ->getOptionLabelFromRecordUsing(fn ($record) => 
                        "{$record->building->name} - Unit {$record->unit_number}"
                    )
                    ->searchable()
                    ->preload(),
                    
                Tables\Filters\SelectFilter::make('building')
                    ->label('Building')
                    ->options(fn () => 
                        \App\Models\Building::pluck('name', 'id')->toArray()
                    )
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'],
                            fn (Builder $query, $value): Builder => 
                                $query->whereHas('property.building', fn ($q) => $q->where('id', $value))
                        );
                    }),
                    
                Tables\Filters\Filter::make('lease_period')
                    ->form([
                        DatePicker::make('lease_from')
                            ->label('Lease Start From'),
                        DatePicker::make('lease_until')
                            ->label('Lease Start Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['lease_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('lease_start_date', '>=', $date),
                            )
                            ->when(
                                $data['lease_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('lease_start_date', '<=', $date),
                            );
                    }),
                    
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                
                Tables\Actions\Action::make('generate_invoice')
                    ->label('Generate Invoice')
                    ->icon('heroicon-o-document-text')
                    ->color('success')
                    ->form([
                        DatePicker::make('period_start')
                            ->label('Billing Period Start')
                            ->required()
                            ->default(now()->startOfMonth())
                            ->native(false),
                            
                        DatePicker::make('period_end')
                            ->label('Billing Period End')
                            ->required()
                            ->default(now()->endOfMonth())
                            ->native(false)
                            ->after('period_start'),
                    ])
                    ->action(function (array $data, Tenant $record): void {
                        try {
                            $billingService = app(\App\Services\BillingService::class);
                            
                            $invoice = $billingService->generateInvoice(
                                $record,
                                \Carbon\Carbon::parse($data['period_start']),
                                \Carbon\Carbon::parse($data['period_end'])
                            );
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Invoice Generated Successfully')
                                ->body("Invoice #{$invoice->id} has been created for {$record->full_name}")
                                ->success()
                                ->actions([
                                    \Filament\Notifications\Actions\Action::make('view')
                                        ->button()
                                        ->url(route('filament.admin.resources.invoices.view', $invoice)),
                                ])
                                ->send();
                                
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Invoice Generation Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Generate Invoice')
                    ->modalDescription('Generate a new invoice for this tenant for the specified billing period.')
                    ->visible(fn (Tenant $record) => $record->is_active),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Tenants')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn (\Illuminate\Database\Eloquent\Collection $records) => 
                            $records->each->update(['is_active' => true])
                        )
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation(),
                        
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Tenants')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn (\Illuminate\Database\Eloquent\Collection $records) => 
                            $records->each->update(['is_active' => false])
                        )
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'view' => Pages\ViewTenant::route('/{record}'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }
    
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_active', true)->count();
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}