<?php

namespace App\Filament\Resources\OrganizationUsers\Pages;

use App\Filament\Actions\Admin\OrganizationUsers\CreateOrganizationManagerAction;
use App\Filament\Resources\OrganizationUsers\OrganizationUserResource;
use App\Filament\Resources\Pages\Concerns\HasContainedSuperadminSurface;
use App\Filament\Support\Admin\OrganizationContext;
use App\Models\Organization;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

class CreateOrganizationUser extends CreateRecord
{
    use HasContainedSuperadminSurface;

    protected static string $resource = OrganizationUserResource::class;

    public function getTitle(): string|Htmlable
    {
        if ($this->currentUser()?->isSuperadmin()) {
            return parent::getTitle();
        }

        return __('superadmin.organizations.relations.managers.actions.create');
    }

    public function getBreadcrumb(): string
    {
        if ($this->currentUser()?->isSuperadmin()) {
            return parent::getBreadcrumb();
        }

        return __('superadmin.organizations.relations.managers.actions.create');
    }

    public function form(Schema $schema): Schema
    {
        if ($this->currentUser()?->isSuperadmin()) {
            return parent::form($schema);
        }

        return $schema
            ->components([
                Section::make(__('superadmin.users.sections.details'))
                    ->description(__('superadmin.organizations.relations.managers.messages.invitation_onboarding_hint'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('superadmin.users.fields.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label(__('superadmin.users.fields.email'))
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(table: User::class, column: 'email'),
                        Select::make('locale')
                            ->label(__('superadmin.users.fields.locale'))
                            ->options(config('tenanto.locales', []))
                            ->default(fn (): string => $this->currentUser()?->locale ?? config('app.locale', 'en'))
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $actor = $this->currentUser();

        if ($actor?->isSuperadmin()) {
            return parent::handleRecordCreation($data);
        }

        $organization = app(OrganizationContext::class)->currentOrganization();

        abort_unless($actor instanceof User && $organization instanceof Organization, 403);

        return app(CreateOrganizationManagerAction::class)->handle(
            organization: $organization,
            actor: $actor,
            data: [
                'name' => (string) $data['name'],
                'email' => (string) $data['email'],
                'locale' => (string) $data['locale'],
            ],
        );
    }

    protected function getCreatedNotification(): ?Notification
    {
        if ($this->currentUser()?->isSuperadmin()) {
            return parent::getCreatedNotification();
        }

        return Notification::make()
            ->success()
            ->title(__('superadmin.organizations.relations.managers.messages.manager_invited'))
            ->body(__('superadmin.organizations.relations.managers.messages.invitation_sent', [
                'email' => (string) $this->record?->user?->email,
            ]));
    }

    protected function shouldWrapSuperadminSurface(): bool
    {
        return $this->currentUser()?->isSuperadmin() ?? false;
    }

    private function currentUser(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }
}
