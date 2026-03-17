<?php

namespace App\Filament\Resources\Meters;

use App\Filament\Resources\Meters\Pages\CreateMeter;
use App\Filament\Resources\Meters\Pages\EditMeter;
use App\Filament\Resources\Meters\Pages\ListMeters;
use App\Filament\Resources\Meters\Pages\ViewMeter;
use App\Filament\Resources\Meters\Schemas\MeterForm;
use App\Filament\Resources\Meters\Schemas\MeterInfolist;
use App\Filament\Resources\Meters\Tables\MetersTable;
use App\Models\Meter;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MeterResource extends Resource
{
    protected static ?string $model = Meter::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->select([
                'id',
                'organization_id',
                'property_id',
                'name',
                'identifier',
                'type',
                'status',
                'unit',
                'installed_at',
                'created_at',
                'updated_at',
            ])
            ->where('organization_id', auth()->user()?->organization_id)
            ->with('property:id,organization_id,name');
    }

    public static function form(Schema $schema): Schema
    {
        return MeterForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MeterInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MetersTable::configure($table);
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
            'index' => ListMeters::route('/'),
            'create' => CreateMeter::route('/create'),
            'view' => ViewMeter::route('/{record}'),
            'edit' => EditMeter::route('/{record}/edit'),
        ];
    }
}
