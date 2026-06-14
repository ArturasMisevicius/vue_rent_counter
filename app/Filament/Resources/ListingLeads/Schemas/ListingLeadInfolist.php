<?php

declare(strict_types=1);

namespace App\Filament\Resources\ListingLeads\Schemas;

use App\Filament\Support\Formatting\EuMoneyFormatter;
use App\Models\ListingLead;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class ListingLeadInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin.leads.sections.workflow'))
                ->schema([
                    TextEntry::make('status')
                        ->label(__('admin.leads.fields.status'))
                        ->badge(),
                    TextEntry::make('assignedTo.name')
                        ->label(__('admin.leads.fields.assigned_to'))
                        ->placeholder('—'),
                    TextEntry::make('last_contacted_at')
                        ->label(__('admin.leads.fields.last_contacted_at'))
                        ->dateTime()
                        ->placeholder('—'),
                    TextEntry::make('next_follow_up_at')
                        ->label(__('admin.leads.fields.next_follow_up_at'))
                        ->dateTime()
                        ->placeholder('—'),
                    TextEntry::make('convertedProperty.name')
                        ->label(__('admin.leads.fields.converted_property'))
                        ->placeholder('—'),
                    TextEntry::make('converted_at')
                        ->label(__('admin.leads.fields.converted_at'))
                        ->dateTime()
                        ->placeholder('—'),
                ])
                ->columns(3),
            Section::make(__('admin.leads.sections.listing'))
                ->schema([
                    TextEntry::make('listing_title')
                        ->label(__('admin.leads.fields.listing_title'))
                        ->placeholder('—'),
                    TextEntry::make('property_address')
                        ->label(__('admin.leads.fields.property_address'))
                        ->placeholder('—'),
                    TextEntry::make('city')
                        ->label(__('admin.leads.fields.city'))
                        ->placeholder('—'),
                    TextEntry::make('district')
                        ->label(__('admin.leads.fields.district'))
                        ->placeholder('—'),
                    TextEntry::make('property_type')
                        ->label(__('admin.leads.fields.property_type'))
                        ->placeholder('—'),
                    TextEntry::make('area')
                        ->label(__('admin.leads.fields.area'))
                        ->placeholder('—'),
                    TextEntry::make('rooms')
                        ->label(__('admin.leads.fields.rooms'))
                        ->placeholder('—'),
                    TextEntry::make('floor')
                        ->label(__('admin.leads.fields.floor'))
                        ->placeholder('—'),
                    TextEntry::make('price')
                        ->label(__('admin.leads.fields.price'))
                        ->state(fn (ListingLead $record): string => $record->price === null ? '—' : EuMoneyFormatter::format($record->price, $record->currency)),
                    TextEntry::make('description')
                        ->label(__('admin.leads.fields.description'))
                        ->placeholder('—')
                        ->columnSpanFull(),
                ])
                ->columns(3),
            Section::make(__('admin.leads.sections.contact'))
                ->schema([
                    TextEntry::make('contact.name')
                        ->label(__('admin.leads.fields.contact'))
                        ->state(fn (ListingLead $record): string => $record->contact?->displayName() ?? $record->owner_name ?? '—'),
                    TextEntry::make('owner_phone')
                        ->label(__('admin.leads.fields.owner_phone'))
                        ->placeholder('—'),
                    TextEntry::make('owner_email')
                        ->label(__('admin.leads.fields.owner_email'))
                        ->placeholder('—'),
                    IconEntry::make('contact.do_not_contact')
                        ->label(__('admin.leads.fields.do_not_contact'))
                        ->boolean(),
                    TextEntry::make('contact.do_not_contact_reason')
                        ->label(__('admin.leads.fields.do_not_contact_reason'))
                        ->placeholder('—')
                        ->columnSpanFull(),
                    TextEntry::make('contact_raw')
                        ->label(__('admin.leads.fields.contact_raw'))
                        ->placeholder('—')
                        ->columnSpanFull(),
                ])
                ->columns(3),
            Section::make(__('admin.leads.sections.source'))
                ->schema([
                    TextEntry::make('source.name')
                        ->label(__('admin.leads.fields.lead_source'))
                        ->placeholder('—'),
                    TextEntry::make('external_id')
                        ->label(__('admin.leads.fields.external_id'))
                        ->placeholder('—'),
                    TextEntry::make('source_url')
                        ->label(__('admin.leads.fields.source_url'))
                        ->url(fn (ListingLead $record): ?string => $record->source_url)
                        ->openUrlInNewTab()
                        ->placeholder('—'),
                    TextEntry::make('duplicate_reasons')
                        ->label(__('admin.leads.fields.duplicate_reasons'))
                        ->state(fn (ListingLead $record): string => collect($record->duplicate_reasons ?? [])->pluck('message')->filter()->implode("\n") ?: '—')
                        ->columnSpanFull(),
                ])
                ->columns(2),
            View::make('filament.resources.listing-leads.outreach-timeline')
                ->viewData(fn (ListingLead $record): array => [
                    'activities' => $record->outreachActivities()
                        ->select(['id', 'listing_lead_id', 'user_id', 'channel', 'direction', 'subject', 'message_summary', 'status', 'sent_at', 'received_at', 'next_follow_up_at', 'created_at'])
                        ->with('user:id,name')
                        ->latestFirst()
                        ->limit(50)
                        ->get(),
                ]),
        ]);
    }
}
