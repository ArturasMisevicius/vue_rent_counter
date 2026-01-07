<?php

declare(strict_types=1);

namespace App\Filament\Resources\TenantResource\RelationManagers;

use App\Models\Attachment;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Storage;

class AttachmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'attachments';

    protected static ?string $title = 'Documents & Photos';

    protected static ?string $modelLabel = 'attachment';

    protected static ?string $pluralModelLabel = 'attachments';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Section::make('File Upload')
                    ->schema([
                        Forms\Components\FileUpload::make('file')
                            ->label('File')
                            ->required()
                            ->maxSize(10240) // 10MB
                            ->directory('tenants/attachments')
                            ->visibility('private')
                            ->acceptedFileTypes(['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                            ->helperText('Upload documents, photos, or other files (max 10MB)')
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $set('original_filename', $state->getClientOriginalName());
                                    $set('mime_type', $state->getMimeType());
                                    $set('size', $state->getSize());
                                }
                            }),
                            
                        Forms\Components\Select::make('category')
                            ->label('Category')
                            ->options([
                                'photo' => 'Photo',
                                'contract' => 'Lease Contract',
                                'identity' => 'Identity Document',
                                'document' => 'General Document',
                                'other' => 'Other',
                            ])
                            ->required()
                            ->default('document'),
                            
                        Forms\Components\TextInput::make('description')
                            ->label('Description')
                            ->maxLength(255)
                            ->helperText('Optional description of the file'),
                            
                        Forms\Components\Hidden::make('original_filename'),
                        Forms\Components\Hidden::make('mime_type'),
                        Forms\Components\Hidden::make('size'),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('filename')
            ->columns([
                Tables\Columns\ImageColumn::make('thumbnail')
                    ->label('')
                    ->getStateUsing(function (Attachment $record) {
                        if ($record->isImage()) {
                            return $record->url;
                        }
                        return null;
                    })
                    ->size(40)
                    ->circular()
                    ->defaultImageUrl(asset('images/file-icon.png')),
                    
                Tables\Columns\TextColumn::make('original_filename')
                    ->label('File Name')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->limit(30)
                    ->tooltip(fn (Attachment $record): string => $record->original_filename),
                    
                Tables\Columns\TextColumn::make('category')
                    ->label('Category')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'photo' => 'info',
                        'contract' => 'success',
                        'identity' => 'warning',
                        'document' => 'gray',
                        'other' => 'secondary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'photo' => 'Photo',
                        'contract' => 'Contract',
                        'identity' => 'Identity',
                        'document' => 'Document',
                        'other' => 'Other',
                        default => ucfirst($state),
                    }),
                    
                Tables\Columns\TextColumn::make('human_size')
                    ->label('Size')
                    ->sortable(['size']),
                    
                Tables\Columns\TextColumn::make('mime_type')
                    ->label('Type')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(function (string $state): string {
                        return match (true) {
                            str_starts_with($state, 'image/') => 'Image',
                            $state === 'application/pdf' => 'PDF',
                            str_contains($state, 'word') => 'Word',
                            str_contains($state, 'excel') => 'Excel',
                            default => 'File',
                        };
                    }),
                    
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->tooltip(fn (?string $state): ?string => $state)
                    ->placeholder('No description'),
                    
                Tables\Columns\TextColumn::make('uploader.name')
                    ->label('Uploaded By')
                    ->placeholder('System'),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->dateTime()
                    ->sortable()
                    ->since(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'photo' => 'Photo',
                        'contract' => 'Lease Contract',
                        'identity' => 'Identity Document',
                        'document' => 'General Document',
                        'other' => 'Other',
                    ]),
                    
                Tables\Filters\SelectFilter::make('mime_type')
                    ->label('File Type')
                    ->options([
                        'application/pdf' => 'PDF',
                        'image/jpeg' => 'JPEG Image',
                        'image/png' => 'PNG Image',
                        'application/msword' => 'Word Document',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'Word Document (DOCX)',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Upload File')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['tenant_id'] = $this->getOwnerRecord()->tenant_id;
                        $data['uploaded_by'] = auth()->id();
                        
                        if (isset($data['file'])) {
                            $file = $data['file'];
                            $filename = time() . '_' . $file->getClientOriginalName();
                            $path = $file->storeAs('tenants/attachments', $filename, 'private');
                            
                            $data['filename'] = $filename;
                            $data['path'] = $path;
                            $data['disk'] = 'private';
                            $data['metadata'] = [
                                'category' => $data['category'] ?? 'document',
                            ];
                            
                            unset($data['file']);
                        }
                        
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function (Attachment $record) {
                        return Storage::disk($record->disk)->download($record->path, $record->original_filename);
                    }),
                    
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Attachment $record) => $record->url)
                    ->openUrlInNewTab()
                    ->visible(fn (Attachment $record) => $record->isImage() || $record->isPdf()),
                    
                Tables\Actions\EditAction::make()
                    ->form([
                        Forms\Components\Select::make('category')
                            ->label('Category')
                            ->options([
                                'photo' => 'Photo',
                                'contract' => 'Lease Contract',
                                'identity' => 'Identity Document',
                                'document' => 'General Document',
                                'other' => 'Other',
                            ])
                            ->required(),
                            
                        Forms\Components\TextInput::make('description')
                            ->label('Description')
                            ->maxLength(255),
                    ])
                    ->mutateFormDataUsing(function (array $data, Attachment $record): array {
                        $metadata = $record->metadata ?? [];
                        $metadata['category'] = $data['category'];
                        $data['metadata'] = $metadata;
                        
                        return $data;
                    }),
                    
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No documents uploaded')
            ->emptyStateDescription('Upload documents, photos, and other files for this tenant.')
            ->emptyStateIcon('heroicon-o-document-arrow-up')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Upload First File')
                    ->icon('heroicon-o-arrow-up-tray'),
            ]);
    }
}