<?php

declare(strict_types=1);

namespace App\Filament\Resources\InvoiceResource\RelationManagers;

use App\Models\Invoice;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Actions;
use Filament\Tables\Table;

/**
 * Relation manager for invoice items.
 *
 * Manages the relationship between invoices and their line items with:
 * - Finalization protection (no edits to finalized invoices)
 * - Meter reading snapshot display
 * - Quantity and pricing management
 */
class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\TextInput::make('description')
                    ->label('Description')
                    ->required()
                    ->maxLength(255)
                    ->disabled(fn (): bool => $this->getOwnerRecord()->isFinalized())
                    ->validationMessages([
                        'required' => 'Description is required',
                        'max' => 'Description cannot exceed 255 characters',
                    ]),
                
                Forms\Components\TextInput::make('quantity')
                    ->label('Quantity')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01)
                    ->disabled(fn (): bool => $this->getOwnerRecord()->isFinalized())
                    ->validationMessages([
                        'required' => 'Quantity is required',
                        'numeric' => 'Quantity must be a number',
                        'min' => 'Quantity must be at least 0',
                    ]),
                
                Forms\Components\TextInput::make('unit')
                    ->label('Unit')
                    ->required()
                    ->maxLength(50)
                    ->disabled(fn (): bool => $this->getOwnerRecord()->isFinalized())
                    ->validationMessages([
                        'required' => 'Unit is required',
                        'max' => 'Unit cannot exceed 50 characters',
                    ]),
                
                Forms\Components\TextInput::make('unit_price')
                    ->label('Unit Price')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->prefix('€')
                    ->step(0.0001)
                    ->disabled(fn (): bool => $this->getOwnerRecord()->isFinalized())
                    ->validationMessages([
                        'required' => 'Unit price is required',
                        'numeric' => 'Unit price must be a number',
                        'min' => 'Unit price must be at least 0',
                    ]),
                
                Forms\Components\TextInput::make('total')
                    ->label('Total')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->prefix('€')
                    ->step(0.01)
                    ->disabled(fn (): bool => $this->getOwnerRecord()->isFinalized())
                    ->validationMessages([
                        'required' => 'Total is required',
                        'numeric' => 'Total must be a number',
                        'min' => 'Total must be at least 0',
                    ]),
                
                Forms\Components\Textarea::make('meter_reading_snapshot')
                    ->label('Meter Reading Snapshot (JSON)')
                    ->rows(3)
                    ->disabled(fn (): bool => $this->getOwnerRecord()->isFinalized())
                    ->helperText('Snapshotted meter reading data in JSON format'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Quantity')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('unit')
                    ->label('Unit')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('unit_price')
                    ->label('Unit Price')
                    ->money('EUR', divideBy: 1)
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('EUR')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('meter_reading_snapshot')
                    ->label('Snapshot')
                    ->formatStateUsing(fn ($state): string => 
                        $state ? 'Yes' : 'No'
                    )
                    ->badge()
                    ->color(fn ($state): string => $state ? 'success' : 'gray')
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Actions\CreateAction::make()
                    ->disabled(fn (): bool => $this->getOwnerRecord()->isFinalized())
                    ->tooltip(fn (): ?string => 
                        $this->getOwnerRecord()->isFinalized() 
                            ? 'Cannot add items to finalized invoice' 
                            : null
                    ),
            ])
            ->actions([
                Actions\EditAction::make()
                    ->disabled(fn (): bool => $this->getOwnerRecord()->isFinalized())
                    ->tooltip(fn (): ?string => 
                        $this->getOwnerRecord()->isFinalized() 
                            ? 'Cannot edit items in finalized invoice' 
                            : null
                    ),
                Actions\DeleteAction::make()
                    ->disabled(fn (): bool => $this->getOwnerRecord()->isFinalized())
                    ->tooltip(fn (): ?string => 
                        $this->getOwnerRecord()->isFinalized() 
                            ? 'Cannot delete items from finalized invoice' 
                            : null
                    ),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make()
                        ->disabled(fn (): bool => $this->getOwnerRecord()->isFinalized()),
                ]),
            ]);
    }
}
