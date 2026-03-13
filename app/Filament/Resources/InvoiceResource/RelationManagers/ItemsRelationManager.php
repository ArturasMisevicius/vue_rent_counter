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
                    ->label(__('invoices.admin.items.description'))
                    ->required()
                    ->maxLength(255)
                    ->disabled(fn (): bool => $this->getOwnerRecord()->isFinalized())
                    ->validationMessages([
                        'required' => 'Description is required',
                        'max' => 'Description cannot exceed 255 characters',
                    ]),
                
                Forms\Components\TextInput::make('quantity')
                    ->label(__('invoices.admin.items.quantity'))
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
                    ->label(__('invoices.admin.items.unit'))
                    ->required()
                    ->maxLength(50)
                    ->disabled(fn (): bool => $this->getOwnerRecord()->isFinalized())
                    ->validationMessages([
                        'required' => 'Unit is required',
                        'max' => 'Unit cannot exceed 50 characters',
                    ]),
                
                Forms\Components\TextInput::make('unit_price')
                    ->label(__('invoices.admin.items.unit_price'))
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->prefix(__('app.units.euro'))
                    ->step(0.0001)
                    ->disabled(fn (): bool => $this->getOwnerRecord()->isFinalized())
                    ->validationMessages([
                        'required' => 'Unit price is required',
                        'numeric' => 'Unit price must be a number',
                        'min' => 'Unit price must be at least 0',
                    ]),
                
                Forms\Components\TextInput::make('total')
                    ->label(__('invoices.admin.items.total'))
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->prefix(__('app.units.euro'))
                    ->step(0.01)
                    ->disabled(fn (): bool => $this->getOwnerRecord()->isFinalized())
                    ->validationMessages([
                        'required' => 'Total is required',
                        'numeric' => 'Total must be a number',
                        'min' => 'Total must be at least 0',
                    ]),
                
                Forms\Components\Textarea::make('meter_reading_snapshot')
                    ->label(__('invoices.admin.items.snapshot'))
                    ->rows(3)
                    ->disabled(fn (): bool => $this->getOwnerRecord()->isFinalized())
                    ->helperText(__('invoices.admin.items.snapshot_helper')),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                Tables\Columns\TextColumn::make('description')
                    ->label(__('invoices.admin.items.description'))
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('quantity')
                    ->label(__('invoices.admin.items.quantity'))
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('unit')
                    ->label(__('invoices.admin.items.unit'))
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('unit_price')
                    ->label(__('invoices.admin.items.unit_price'))
                    ->money('EUR', divideBy: 1)
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('total')
                    ->label(__('invoices.admin.items.total'))
                    ->money('EUR')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('meter_reading_snapshot')
                    ->label(__('invoices.admin.items.snapshot'))
                    ->formatStateUsing(fn ($state): string => 
                        $state ? __('invoices.admin.items.snapshot_yes') : __('invoices.admin.items.snapshot_no')
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
                            ? __('invoices.admin.items.cannot_add_finalized') 
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
