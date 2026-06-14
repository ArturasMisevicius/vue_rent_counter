<?php

declare(strict_types=1);

namespace App\Filament\Resources\LeadOutreachTemplates\Pages;

use App\Filament\Resources\LeadOutreachTemplates\LeadOutreachTemplateResource;
use Filament\Resources\Pages\EditRecord;

class EditLeadOutreachTemplate extends EditRecord
{
    protected static string $resource = LeadOutreachTemplateResource::class;

    protected static string|array $routeMiddleware = 'manager.permission:leads,edit';
}
