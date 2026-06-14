<?php

declare(strict_types=1);

namespace App\Filament\Actions\Notifications;

use App\Enums\UserRole;
use App\Filament\Support\Notifications\DomainNotificationContentFactory;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;

final class NotifyOrganizationAdmins
{
    public function __construct(
        private readonly DomainNotificationContentFactory $contentFactory,
        private readonly DispatchDomainNotification $dispatchDomainNotification,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return Collection<int, DatabaseNotification>
     */
    public function handle(
        Organization $organization,
        string $type,
        Model $subject,
        array $data = [],
        ?User $actor = null,
    ): Collection {
        $content = $this->contentFactory->make($type, $subject, $data);

        return $this->recipients($organization)
            ->map(fn (User $recipient) => $this->dispatchDomainNotification->handle(
                recipient: $recipient,
                content: $content,
                organization: $organization,
                subject: $subject,
                actor: $actor,
            ))
            ->filter()
            ->values();
    }

    /**
     * @return EloquentCollection<int, User>
     */
    private function recipients(Organization $organization): EloquentCollection
    {
        $users = User::query()
            ->select(['id', 'organization_id', 'name', 'email', 'role', 'status'])
            ->forOrganization($organization->id)
            ->active()
            ->where('role', UserRole::ADMIN->value)
            ->get();

        if ($organization->owner_user_id !== null && ! $users->contains('id', $organization->owner_user_id)) {
            $owner = User::query()
                ->select(['id', 'organization_id', 'name', 'email', 'role', 'status'])
                ->active()
                ->find($organization->owner_user_id);

            if ($owner instanceof User) {
                $users->push($owner);
            }
        }

        return new EloquentCollection($users->unique('id')->values()->all());
    }
}
