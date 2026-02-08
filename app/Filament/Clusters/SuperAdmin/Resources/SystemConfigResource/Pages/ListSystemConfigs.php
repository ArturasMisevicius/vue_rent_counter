<?php

declare(strict_types=1);

namespace App\Filament\Clusters\SuperAdmin\Resources\SystemConfigResource\Pages;

use App\Filament\Clusters\SuperAdmin\Resources\SystemConfigResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListSystemConfigs extends ListRecords
{
    protected static string $resource = SystemConfigResource::class;

    public function getTitle(): string
    {
        return __('superadmin.config.pages.list.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-o-plus'),
        ];
    }
}