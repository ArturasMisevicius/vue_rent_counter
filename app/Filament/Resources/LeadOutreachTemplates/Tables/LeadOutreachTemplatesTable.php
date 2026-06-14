<?php

declare(strict_types=1);

namespace App\Filament\Resources\LeadOutreachTemplates\Tables;

use App\Enums\LeadOutreachChannel;
use App\Filament\Resources\LeadOutreachTemplates\LeadOutreachTemplateResource;
use App\Models\LeadOutreachTemplate;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LeadOutreachTemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.lead_outreach_templates.fields.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('channel')
                    ->label(__('admin.lead_outreach_templates.fields.channel'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('locale')
                    ->label(__('admin.lead_outreach_templates.fields.locale'))
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label(__('admin.lead_outreach_templates.fields.is_active'))
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('channel')
                    ->label(__('admin.lead_outreach_templates.fields.channel'))
                    ->options(LeadOutreachChannel::options()),
            ])
            ->recordActions([
                EditAction::make()
                    ->label(__('admin.actions.edit'))
                    ->authorize(fn (LeadOutreachTemplate $record): bool => LeadOutreachTemplateResource::canEdit($record)),
                DeleteAction::make()
                    ->label(__('admin.actions.delete'))
                    ->authorize(fn (LeadOutreachTemplate $record): bool => LeadOutreachTemplateResource::canDelete($record)),
            ])
            ->defaultSort('name');
    }
}
