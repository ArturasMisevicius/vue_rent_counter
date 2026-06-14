<?php

declare(strict_types=1);

namespace App\Filament\Resources\ListingLeads\Tables;

use App\Enums\LeadOutreachChannel;
use App\Enums\LeadOutreachDirection;
use App\Enums\ListingLeadStatus;
use App\Enums\PropertyType;
use App\Filament\Actions\Admin\Leads\ArchiveLead;
use App\Filament\Actions\Admin\Leads\AssignLead;
use App\Filament\Actions\Admin\Leads\ConvertLeadToProperty;
use App\Filament\Actions\Admin\Leads\MarkLeadDoNotContact;
use App\Filament\Actions\Admin\Leads\RecordOutreachActivity;
use App\Filament\Actions\Admin\Leads\ScheduleLeadFollowUp;
use App\Filament\Resources\ListingLeads\ListingLeadResource;
use App\Filament\Support\Formatting\EuMoneyFormatter;
use App\Models\Building;
use App\Models\ListingLead;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ListingLeadsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('status')
                    ->label(__('admin.leads.fields.status'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('listing_title')
                    ->label(__('admin.leads.fields.listing_title'))
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('property_address')
                    ->label(__('admin.leads.fields.property_address'))
                    ->searchable()
                    ->toggleable()
                    ->wrap(),
                TextColumn::make('price')
                    ->label(__('admin.leads.fields.price'))
                    ->state(fn (ListingLead $record): string => $record->price === null ? '—' : EuMoneyFormatter::format($record->price, $record->currency))
                    ->sortable(),
                TextColumn::make('contact')
                    ->label(__('admin.leads.fields.contact'))
                    ->state(fn (ListingLead $record): string => $record->contact?->displayName() ?? $record->owner_name ?? '—')
                    ->searchable(['owner_name', 'owner_phone', 'owner_email'])
                    ->wrap(),
                IconColumn::make('contact.do_not_contact')
                    ->label(__('admin.leads.fields.do_not_contact'))
                    ->boolean(),
                TextColumn::make('last_contacted_at')
                    ->label(__('admin.leads.fields.last_contacted_at'))
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('next_follow_up_at')
                    ->label(__('admin.leads.fields.next_follow_up_at'))
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('assignedTo.name')
                    ->label(__('admin.leads.fields.assigned_to'))
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('source.name')
                    ->label(__('admin.leads.fields.lead_source'))
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin.leads.fields.status'))
                    ->options(ListingLeadStatus::options()),
                SelectFilter::make('assigned_to_user_id')
                    ->label(__('admin.leads.fields.assigned_to'))
                    ->options(fn (): array => self::assigneeOptions()),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label(__('admin.actions.view')),
                EditAction::make()
                    ->label(__('admin.actions.edit'))
                    ->authorize(fn (ListingLead $record): bool => ListingLeadResource::canEdit($record)),
                self::assignAction(),
                self::recordOutreachAction(),
                self::scheduleFollowUpAction(),
                self::markDoNotContactAction(),
                self::convertAction(),
                self::archiveAction(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    private static function assignAction(): Action
    {
        return Action::make('assign')
            ->label(__('admin.leads.actions.assign'))
            ->schema([
                Select::make('assigned_to_user_id')
                    ->label(__('admin.leads.fields.assigned_to'))
                    ->options(fn (ListingLead $record): array => self::assigneeOptions((int) $record->organization_id))
                    ->searchable()
                    ->preload()
                    ->required(),
            ])
            ->authorize(fn (ListingLead $record): bool => self::currentUser()?->can('assign', $record) ?? false)
            ->action(function (ListingLead $record, array $data, AssignLead $assignLead): void {
                $actor = self::currentUser();
                $assignee = User::query()
                    ->select(['id', 'organization_id', 'name', 'email', 'role', 'status'])
                    ->findOrFail((int) $data['assigned_to_user_id']);

                abort_unless($actor instanceof User, 403);

                $assignLead->handle($actor, $record, $assignee);

                Notification::make()
                    ->success()
                    ->title(__('admin.leads.messages.assigned'))
                    ->send();
            });
    }

    private static function recordOutreachAction(): Action
    {
        return Action::make('recordOutreach')
            ->label(__('admin.leads.actions.record_outreach'))
            ->schema([
                Select::make('channel')
                    ->label(__('admin.leads.fields.channel'))
                    ->options(LeadOutreachChannel::options())
                    ->default(LeadOutreachChannel::MANUAL->value)
                    ->required(),
                Select::make('direction')
                    ->label(__('admin.leads.fields.direction'))
                    ->options(LeadOutreachDirection::options())
                    ->default(LeadOutreachDirection::OUTBOUND->value)
                    ->required(),
                TextInput::make('subject')
                    ->label(__('admin.leads.fields.subject'))
                    ->maxLength(255),
                Textarea::make('message_summary')
                    ->label(__('admin.leads.fields.message_summary'))
                    ->required()
                    ->rows(4)
                    ->columnSpanFull(),
                DateTimePicker::make('next_follow_up_at')
                    ->label(__('admin.leads.fields.next_follow_up_at')),
                Textarea::make('override_reason')
                    ->label(__('admin.leads.fields.override_reason'))
                    ->rows(2)
                    ->columnSpanFull(),
            ])
            ->authorize(fn (ListingLead $record): bool => self::currentUser()?->can('recordOutreach', $record) ?? false)
            ->disabled(fn (ListingLead $record): bool => ! (self::currentUser()?->isAdmin() || self::currentUser()?->isSuperadmin())
                && (($record->contact?->do_not_contact ?? false) || $record->status === ListingLeadStatus::DO_NOT_CONTACT))
            ->modalDescription(fn (ListingLead $record): ?string => (($record->contact?->do_not_contact ?? false) || $record->status === ListingLeadStatus::DO_NOT_CONTACT)
                ? __('admin.leads.messages.do_not_contact_blocked')
                : null)
            ->action(function (ListingLead $record, array $data, RecordOutreachActivity $recordOutreachActivity): void {
                $actor = self::currentUser();
                abort_unless($actor instanceof User, 403);

                $recordOutreachActivity->handle($actor, $record, $data);

                Notification::make()
                    ->success()
                    ->title(__('admin.leads.messages.outreach_recorded'))
                    ->send();
            });
    }

    private static function scheduleFollowUpAction(): Action
    {
        return Action::make('scheduleFollowUp')
            ->label(__('admin.leads.actions.schedule_follow_up'))
            ->schema([
                DateTimePicker::make('next_follow_up_at')
                    ->label(__('admin.leads.fields.next_follow_up_at'))
                    ->required(),
                Textarea::make('note')
                    ->label(__('admin.leads.fields.note'))
                    ->rows(3),
            ])
            ->authorize(fn (ListingLead $record): bool => self::currentUser()?->can('recordOutreach', $record) ?? false)
            ->action(function (ListingLead $record, array $data, ScheduleLeadFollowUp $scheduleLeadFollowUp): void {
                $actor = self::currentUser();
                abort_unless($actor instanceof User, 403);

                $scheduleLeadFollowUp->handle($actor, $record, (string) $data['next_follow_up_at'], $data['note'] ?? null);

                Notification::make()
                    ->success()
                    ->title(__('admin.leads.messages.follow_up_scheduled'))
                    ->send();
            });
    }

    private static function markDoNotContactAction(): Action
    {
        return Action::make('markDoNotContact')
            ->label(__('admin.leads.actions.mark_do_not_contact'))
            ->color('danger')
            ->requiresConfirmation()
            ->schema([
                Textarea::make('reason')
                    ->label(__('admin.leads.fields.do_not_contact_reason'))
                    ->required()
                    ->rows(3),
            ])
            ->authorize(fn (ListingLead $record): bool => self::currentUser()?->can('recordOutreach', $record) ?? false)
            ->action(function (ListingLead $record, array $data, MarkLeadDoNotContact $markLeadDoNotContact): void {
                $actor = self::currentUser();
                abort_unless($actor instanceof User, 403);

                $markLeadDoNotContact->handle($actor, $record, (string) $data['reason']);

                Notification::make()
                    ->success()
                    ->title(__('admin.leads.messages.marked_do_not_contact'))
                    ->send();
            });
    }

    private static function convertAction(): Action
    {
        return Action::make('convert')
            ->label(__('admin.leads.actions.convert'))
            ->schema([
                Select::make('building_id')
                    ->label(__('admin.properties.fields.building'))
                    ->options(fn (ListingLead $record): array => Building::query()
                        ->select(['id', 'organization_id', 'name'])
                        ->forOrganization((int) $record->organization_id)
                        ->ordered()
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('name')
                    ->label(__('admin.properties.fields.name'))
                    ->maxLength(255),
                TextInput::make('unit_number')
                    ->label(__('admin.properties.fields.unit_number'))
                    ->maxLength(255),
                Select::make('type')
                    ->label(__('admin.properties.fields.type'))
                    ->options(PropertyType::options())
                    ->default(PropertyType::OTHER->value),
                TextInput::make('floor_area_sqm')
                    ->label(__('admin.properties.fields.floor_area_sqm'))
                    ->numeric(),
            ])
            ->authorize(fn (ListingLead $record): bool => self::currentUser()?->can('convert', $record) ?? false)
            ->visible(fn (ListingLead $record): bool => $record->canConvert())
            ->action(function (ListingLead $record, array $data, ConvertLeadToProperty $convertLeadToProperty): void {
                $actor = self::currentUser();
                abort_unless($actor instanceof User, 403);

                $convertLeadToProperty->handle($actor, $record, $data);

                Notification::make()
                    ->success()
                    ->title(__('admin.leads.messages.converted'))
                    ->send();
            });
    }

    private static function archiveAction(): Action
    {
        return Action::make('archive')
            ->label(__('admin.leads.actions.archive'))
            ->color('gray')
            ->requiresConfirmation()
            ->schema([
                Textarea::make('reason')
                    ->label(__('admin.leads.fields.archive_reason'))
                    ->rows(3),
            ])
            ->authorize(fn (ListingLead $record): bool => self::currentUser()?->can('update', $record) ?? false)
            ->action(function (ListingLead $record, array $data, ArchiveLead $archiveLead): void {
                $actor = self::currentUser();
                abort_unless($actor instanceof User, 403);

                $archiveLead->handle($actor, $record, $data['reason'] ?? null);

                Notification::make()
                    ->success()
                    ->title(__('admin.leads.messages.archived'))
                    ->send();
            });
    }

    /**
     * @return array<int, string>
     */
    private static function assigneeOptions(?int $organizationId = null): array
    {
        $organizationId ??= self::currentUser()?->organization_id;

        if ($organizationId === null) {
            return [];
        }

        return User::query()
            ->select(['id', 'organization_id', 'name', 'email', 'role', 'status'])
            ->forOrganization((int) $organizationId)
            ->adminLike()
            ->active()
            ->orderedByName()
            ->limit(500)
            ->get()
            ->mapWithKeys(fn (User $user): array => [
                $user->id => filled($user->email) ? "{$user->name} · {$user->email}" : $user->name,
            ])
            ->all();
    }

    private static function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }
}
