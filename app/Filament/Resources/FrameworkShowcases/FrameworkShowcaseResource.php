<?php

declare(strict_types=1);

namespace App\Filament\Resources\FrameworkShowcases;

use App\Filament\Concerns\AuthorizesSuperadminAccess;
use App\Filament\Exports\FrameworkShowcaseExporter;
use App\Filament\Resources\FrameworkShowcases\Pages\CreateFrameworkShowcase;
use App\Filament\Resources\FrameworkShowcases\Pages\EditFrameworkShowcase;
use App\Filament\Resources\FrameworkShowcases\Pages\ListFrameworkShowcases;
use App\Filament\Resources\FrameworkShowcases\Pages\ViewFrameworkShowcase;
use App\Models\FrameworkShowcase;
use App\Models\Organization;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class FrameworkShowcaseResource extends Resource
{
    use AuthorizesSuperadminAccess;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = FrameworkShowcase::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('created_by_user_id')
                ->default(fn (): ?int => auth()->id()),
            Section::make('Content')
                ->schema([
                    TextInput::make('title')
                        ->required()
                        ->maxLength(255),
                    Select::make('organization_id')
                        ->label('Organization')
                        ->options(fn (): array => Organization::query()
                            ->select(['id', 'name'])
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all())
                        ->searchable()
                        ->preload(),
                    Select::make('status')
                        ->options([
                            'draft' => 'Draft',
                            'review' => 'In Review',
                            'published' => 'Published',
                        ])
                        ->default('draft')
                        ->required(),
                    Textarea::make('summary')
                        ->maxLength(280)
                        ->rows(3)
                        ->columnSpanFull(),
                    RichEditor::make('content')
                        ->toolbarButtons([
                            'bold',
                            'italic',
                            'underline',
                            'bulletList',
                            'orderedList',
                            'link',
                            'blockquote',
                        ])
                        ->columnSpanFull(),
                ])
                ->columns(2),
            Section::make('Presentation')
                ->schema([
                    FileUpload::make('thumbnail_path')
                        ->label('Thumbnail')
                        ->image()
                        ->directory('framework-showcases')
                        ->imageAspectRatio('16:9')
                        ->automaticallyCropImagesToAspectRatio()
                        ->automaticallyResizeImagesMode('cover')
                        ->automaticallyResizeImagesToWidth('1280')
                        ->automaticallyResizeImagesToHeight('720'),
                    Toggle::make('is_featured')
                        ->live(),
                    Textarea::make('featured_description')
                        ->rows(3)
                        ->visible(fn (callable $get): bool => (bool) $get('is_featured'))
                        ->columnSpanFull(),
                    DateTimePicker::make('published_at'),
                    TagsInput::make('tags')
                        ->separator(',')
                        ->columnSpanFull(),
                ])
                ->columns(2),
            Section::make('SEO')
                ->schema([
                    TextInput::make('meta_title')->maxLength(255),
                    Textarea::make('meta_description')->rows(3),
                ])
                ->columns(2)
                ->collapsible(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('title')
                ->columnSpanFull()
                ->weight('bold'),
            Section::make('Overview')
                ->schema([
                    TextEntry::make('organization.name')->badge(),
                    TextEntry::make('author.name')->label('Author'),
                    TextEntry::make('status')->badge(),
                    TextEntry::make('published_at')->dateTime(),
                ])
                ->columns(2),
            Section::make('Content')
                ->schema([
                    TextEntry::make('summary'),
                    TextEntry::make('content')->html()->columnSpanFull(),
                ]),
            Section::make('Search & Metadata')
                ->schema([
                    TextEntry::make('meta_title'),
                    TextEntry::make('meta_description'),
                    TextEntry::make('tags')
                        ->badge()
                        ->separator(', '),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('thumbnail_path')
                    ->label('Thumbnail')
                    ->square(),
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('organization.name')
                    ->badge()
                    ->placeholder('No organization'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'review' => 'warning',
                        'published' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable(),
                ToggleColumn::make('is_featured'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'review' => 'In Review',
                        'published' => 'Published',
                    ]),
                SelectFilter::make('organization')
                    ->relationship('organization', 'name'),
                TernaryFilter::make('is_featured'),
                Filter::make('published')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('published_at')),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('sharePreview')
                    ->label('Share preview')
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->slideOver()
                    ->schema([
                        TextInput::make('recipient')
                            ->email()
                            ->required(),
                        Textarea::make('message')
                            ->rows(4)
                            ->default(fn (FrameworkShowcase $record): string => "Preview the {$record->title} framework showcase when you have a moment.")
                            ->required(),
                    ])
                    ->action(function (array $data, FrameworkShowcase $record): void {
                        Notification::make()
                            ->title('Preview handoff prepared')
                            ->body("Prepared a preview for {$data['recipient']} using {$record->title}.")
                            ->success()
                            ->send();
                    }),
                Action::make('publishRecord')
                    ->label('Publish')
                    ->action(fn (FrameworkShowcase $record): mixed => $record->publish())
                    ->requiresConfirmation()
                    ->visible(fn (?FrameworkShowcase $record): bool => $record?->status !== 'published'),
            ])
            ->toolbarActions([
                BulkAction::make('publish')
                    ->label('Publish selected')
                    ->action(function (Collection $records): void {
                        $records->each(fn (FrameworkShowcase $record): mixed => $record->publish());
                    }),
                DeleteBulkAction::make(),
                ExportBulkAction::make()
                    ->exporter(FrameworkShowcaseExporter::class),
            ])
            ->defaultSort('published_at', 'desc')
            ->poll('30s');
    }

    public static function getRelations(): array
    {
        return [];
    }

    /**
     * @return Builder<FrameworkShowcase>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->select([
                'id',
                'organization_id',
                'created_by_user_id',
                'title',
                'slug',
                'status',
                'thumbnail_path',
                'published_at',
                'is_featured',
                'created_at',
            ])
            ->with([
                'organization:id,name',
                'author:id,name',
            ]);
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListFrameworkShowcases::route('/'),
            'create' => CreateFrameworkShowcase::route('/create'),
            'view' => ViewFrameworkShowcase::route('/{record}'),
            'edit' => EditFrameworkShowcase::route('/{record}/edit'),
        ];
    }
}
