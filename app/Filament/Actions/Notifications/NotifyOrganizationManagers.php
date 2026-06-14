<?php

declare(strict_types=1);

namespace App\Filament\Actions\Notifications;

use App\Enums\UserRole;
use App\Filament\Support\Notifications\DomainNotificationContentFactory;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;

final class NotifyOrganizationManagers
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

        return User::query()
            ->select(['id', 'organization_id', 'name', 'email', 'role', 'status'])
            ->forOrganization($organization->id)
            ->active()
            ->where('role', UserRole::MANAGER->value)
            ->get()
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
}
