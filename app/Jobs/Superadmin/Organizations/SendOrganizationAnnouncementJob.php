<?php

declare(strict_types=1);

namespace App\Jobs\Superadmin\Organizations;

use App\Models\Organization;
use App\Models\User;
use App\Notifications\Superadmin\OrganizationBroadcastNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendOrganizationAnnouncementJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public int $organizationId,
        public string $title,
        public string $body,
        public string $severity,
    ) {}

    public function handle(): void
    {
        $organization = Organization::query()
            ->select(['id', 'name'])
            ->find($this->organizationId);

        if (! $organization instanceof Organization) {
            return;
        }

        User::query()
            ->select([
                'id',
                'organization_id',
                'name',
                'email',
                'locale',
                'created_at',
                'updated_at',
            ])
            ->forOrganization($organization->id)
            ->chunkById(100, function ($users) use ($organization): void {
                foreach ($users as $user) {
                    $user->notify(new OrganizationBroadcastNotification(
                        $organization,
                        $this->title,
                        $this->body,
                        $this->severity,
                    ));
                }
            });
    }
}
