<?php

declare(strict_types=1);

namespace App\Filament\Resources\Projects\Schemas;

use App\Filament\Support\Superadmin\Projects\ProjectOverviewData;
use App\Models\Project;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class ProjectInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                View::make('filament.resources.projects.overview')
                    ->viewData(fn (Project $record): array => app(ProjectOverviewData::class)->for($record)),
            ]);
    }
}
