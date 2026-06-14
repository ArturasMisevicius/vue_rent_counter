<?php

declare(strict_types=1);

namespace App\Filament\Resources\ListingLeads\Schemas;

use App\Enums\ListingLeadStatus;
use App\Filament\Support\Admin\OrganizationContext;
use App\Models\LeadSource;
use App\Models\Organization;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class ListingLeadForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin.leads.sections.source'))
                ->schema([
                    self::organizationField(),
                    Select::make('lead_source_id')
                        ->label(__('admin.leads.fields.lead_source'))
                        ->options(fn (): array => self::sourceOptions())
                        ->searchable()
                        ->preload(),
                    TextInput::make('external_id')
                        ->label(__('admin.leads.fields.external_id'))
                        ->maxLength(255),
                    TextInput::make('source_url')
                        ->label(__('admin.leads.fields.source_url'))
                        ->url()
                        ->maxLength(255),
                ])
                ->columns(2),
            Section::make(__('admin.leads.sections.listing'))
                ->schema([
                    TextInput::make('listing_title')
                        ->label(__('admin.leads.fields.listing_title'))
                        ->required()
                        ->maxLength(255),
                    TextInput::make('property_address')
                        ->label(__('admin.leads.fields.property_address'))
                        ->maxLength(255),
                    TextInput::make('city')
                        ->label(__('admin.leads.fields.city'))
                        ->maxLength(255),
                    TextInput::make('district')
                        ->label(__('admin.leads.fields.district'))
                        ->maxLength(255),
                    TextInput::make('property_type')
                        ->label(__('admin.leads.fields.property_type'))
                        ->maxLength(255),
                    TextInput::make('area')
                        ->label(__('admin.leads.fields.area'))
                        ->numeric(),
                    TextInput::make('rooms')
                        ->label(__('admin.leads.fields.rooms'))
                        ->integer(),
                    TextInput::make('floor')
                        ->label(__('admin.leads.fields.floor'))
                        ->maxLength(255),
                    TextInput::make('price')
                        ->label(__('admin.leads.fields.price'))
                        ->numeric(),
                    TextInput::make('currency')
                        ->label(__('admin.leads.fields.currency'))
                        ->default('EUR')
                        ->required()
                        ->minLength(3)
                        ->maxLength(3),
                    Textarea::make('description')
                        ->label(__('admin.leads.fields.description'))
                        ->rows(4)
                        ->columnSpanFull(),
                ])
                ->columns(3),
            Section::make(__('admin.leads.sections.contact'))
                ->schema([
                    TextInput::make('owner_name')
                        ->label(__('admin.leads.fields.owner_name'))
                        ->maxLength(255),
                    TextInput::make('owner_phone')
                        ->label(__('admin.leads.fields.owner_phone'))
                        ->tel()
                        ->maxLength(255),
                    TextInput::make('owner_email')
                        ->label(__('admin.leads.fields.owner_email'))
                        ->email()
                        ->maxLength(255),
                    Textarea::make('contact_raw')
                        ->label(__('admin.leads.fields.contact_raw'))
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(3),
            Section::make(__('admin.leads.sections.workflow'))
                ->schema([
                    Select::make('status')
                        ->label(__('admin.leads.fields.status'))
                        ->options(ListingLeadStatus::options())
                        ->default(ListingLeadStatus::NEW->value)
                        ->required(),
                    Select::make('assigned_to_user_id')
                        ->label(__('admin.leads.fields.assigned_to'))
                        ->options(fn (): array => self::assigneeOptions())
                        ->searchable()
                        ->preload(),
                    DateTimePicker::make('last_contacted_at')
                        ->label(__('admin.leads.fields.last_contacted_at')),
                    DateTimePicker::make('next_follow_up_at')
                        ->label(__('admin.leads.fields.next_follow_up_at')),
                ])
                ->columns(2),
        ]);
    }

    private static function organizationField(): Select|Hidden
    {
        $user = Auth::user();

        if ($user instanceof User && $user->isSuperadmin() && app(OrganizationContext::class)->currentOrganizationId() === null) {
            return Select::make('organization_id')
                ->label(__('superadmin.organizations.singular'))
                ->options(fn (): array => Organization::query()
                    ->select(['id', 'name'])
                    ->ordered()
                    ->pluck('name', 'id')
                    ->all())
                ->searchable()
                ->preload()
                ->required();
        }

        return Hidden::make('organization_id')
            ->default(fn (): ?int => app(OrganizationContext::class)->currentOrganizationId());
    }

    /**
     * @return array<int, string>
     */
    private static function sourceOptions(): array
    {
        $organizationId = app(OrganizationContext::class)->currentOrganizationId();

        if ($organizationId === null) {
            return [];
        }

        return LeadSource::query()
            ->select(['id', 'organization_id', 'name'])
            ->forOrganization($organizationId)
            ->ordered()
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private static function assigneeOptions(): array
    {
        $organizationId = app(OrganizationContext::class)->currentOrganizationId();

        if ($organizationId === null) {
            return [];
        }

        return User::query()
            ->select(['id', 'organization_id', 'name', 'email', 'role', 'status'])
            ->forOrganization($organizationId)
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
}
