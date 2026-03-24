<?php

namespace App\Filament\Resources\PropertyAssignments;

use App\Filament\Concerns\AuthorizesSuperadminAccess;
use App\Filament\Resources\PropertyAssignments\Pages\CreatePropertyAssignment;
use App\Filament\Resources\PropertyAssignments\Pages\EditPropertyAssignment;
use App\Filament\Resources\PropertyAssignments\Pages\ListPropertyAssignments;
use App\Filament\Resources\PropertyAssignments\Pages\ViewPropertyAssignment;
use App\Filament\Resources\PropertyAssignments\Schemas\PropertyAssignmentForm;
use App\Filament\Resources\PropertyAssignments\Schemas\PropertyAssignmentInfolist;
use App\Filament\Resources\PropertyAssignments\Tables\PropertyAssignmentsTable;
use App\Models\PropertyAssignment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PropertyAssignmentResource extends Resource
{
    use AuthorizesSuperadminAccess;

    protected static ?string $model = PropertyAssignment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return PropertyAssignmentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PropertyAssignmentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PropertyAssignmentsTable::configure($table);
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
            'index' => ListPropertyAssignments::route('/'),
            'create' => CreatePropertyAssignment::route('/create'),
            'view' => ViewPropertyAssignment::route('/{record}'),
            'edit' => EditPropertyAssignment::route('/{record}/edit'),
        ];
    }
}
