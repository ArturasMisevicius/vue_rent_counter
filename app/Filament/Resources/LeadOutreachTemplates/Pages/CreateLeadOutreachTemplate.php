<?php

declare(strict_types=1);

namespace App\Filament\Resources\LeadOutreachTemplates\Pages;

use App\Filament\Resources\LeadOutreachTemplates\LeadOutreachTemplateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLeadOutreachTemplate extends CreateRecord
{
    protected static string $resource = LeadOutreachTemplateResource::class;

    protected static string|array $routeMiddleware = 'manager.permission:leads,create';
}
