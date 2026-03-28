<?php

namespace App\Livewire\Filament;

use App\Enums\UserRole;
use App\Filament\Support\Admin\ManagerPermissions\ManagerPermissionCatalog;
use App\Filament\Support\Admin\ManagerPermissions\ManagerPermissionService;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ManagerPermissionMatrixPanel extends Component
{
    public OrganizationUser $record;

    public ?int $organizationId = null;

    public ?int $userId = null;

    /**
     * @var array<string, array{can_create: bool, can_edit: bool, can_delete: bool}>
     */
    public array $matrix = [];

    /**
     * @var array<string, array{available: bool, reason: string|null}>
     */
    public array $availability = [];

    /**
     * @var array<int, array{id: int, name: string, email: string}>
     */
    public array $copyableManagers = [];

    public ?int $copyFromManagerId = null;

    public string $selectedPreset = 'read_only';

    public bool $isManager = false;

    public bool $showsSuperadminBanner = false;

    public string $copyModalId;

    public function mount(OrganizationUser $record, ?int $organizationId = null, ?int $userId = null): void
    {
        $this->record = $record;
        $this->organizationId = $organizationId ?? $record->organization_id;
        $this->userId = $userId ?? $record->user_id;
        $this->copyModalId = "copy-manager-permissions-{$record->getKey()}";

        $this->hydrateState();
    }

    public function save(): void
    {
        $manager = $this->manager();
        $organization = $this->organization();
        $actor = auth()->user();

        abort_if(! $manager instanceof User || ! $organization instanceof Organization || ! $actor instanceof User, 403);

        app(ManagerPermissionService::class)->saveMatrix(
            $manager,
            $organization,
            ManagerPermissionCatalog::normalizeMatrix($this->matrix),
            $actor,
        );

        $this->matrix = app(ManagerPermissionService::class)->getMatrix($manager, $organization);
        $this->selectedPreset = $this->detectPreset($this->matrix);

        Notification::make()
            ->success()
            ->title(__('admin.manager_permissions.notifications.saved_title'))
            ->send();
    }

    public function openCopyModal(): void
    {
        if (! $this->isManager || $this->copyableManagers === []) {
            return;
        }

        $this->dispatch('open-modal', id: $this->copyModalId);
    }

    public function copyFromSelectedManager(): void
    {
        $organization = $this->organization();

        abort_if(! $organization instanceof Organization || ! $this->copyFromManagerId, 403);

        $source = User::query()
            ->select(['id', 'organization_id', 'name', 'email', 'role', 'status', 'locale', 'last_login_at', 'created_at', 'updated_at'])
            ->whereKey($this->copyFromManagerId)
            ->where('organization_id', $organization->id)
            ->firstOrFail();

        $this->matrix = app(ManagerPermissionService::class)->getMatrix($source, $organization);
        $this->selectedPreset = $this->detectPreset($this->matrix);
        $this->dispatch('close-modal', id: $this->copyModalId);
    }

    public function render(): View
    {
        return view('livewire.filament.manager-permission-matrix', [
            'availability' => $this->availability,
            'copyableManagers' => $this->copyableManagers,
            'labels' => ManagerPermissionCatalog::labels(),
            'manager' => $this->manager(),
            'organization' => $this->organization(),
            'presetLabels' => collect(ManagerPermissionCatalog::presets())
                ->mapWithKeys(fn (array $preset, string $key): array => [$key => $preset['name']])
                ->all(),
            'presetMatrices' => collect(ManagerPermissionCatalog::presets())
                ->mapWithKeys(fn (array $preset, string $key): array => [$key => $preset['matrix']])
                ->all(),
        ]);
    }

    private function hydrateState(): void
    {
        $organization = $this->organization();
        $manager = $this->manager();

        $this->isManager = $this->record->role === UserRole::MANAGER->value
            && $manager instanceof User
            && $organization instanceof Organization;
        $this->showsSuperadminBanner = auth()->user()?->isSuperadmin() ?? false;

        $this->availability = $organization instanceof Organization
            ? ManagerPermissionCatalog::availabilityForOrganization($organization)
            : collect(ManagerPermissionCatalog::resources())
                ->mapWithKeys(fn (string $resource): array => [
                    $resource => ['available' => true, 'reason' => null],
                ])
                ->all();

        $this->matrix = ($this->isManager && $manager instanceof User && $organization instanceof Organization)
            ? app(ManagerPermissionService::class)->getMatrix($manager, $organization)
            : ManagerPermissionCatalog::defaultMatrix();

        $this->selectedPreset = $this->detectPreset($this->matrix);
        $this->copyableManagers = ($this->isManager && $organization instanceof Organization && $manager instanceof User)
            ? $this->resolveCopyableManagers($organization, $manager)
            : [];
    }

    /**
     * @param  array<string, array{can_create: bool, can_edit: bool, can_delete: bool}>  $matrix
     */
    private function detectPreset(array $matrix): string
    {
        foreach (ManagerPermissionCatalog::presets() as $key => $preset) {
            if ($preset['matrix'] === $matrix) {
                return $key;
            }
        }

        return 'custom';
    }

    private function organization(): ?Organization
    {
        if ($this->organizationId === null) {
            return null;
        }

        return Organization::query()
            ->select(['id', 'name', 'slug', 'status', 'owner_user_id', 'created_at', 'updated_at'])
            ->find($this->organizationId);
    }

    private function manager(): ?User
    {
        if ($this->userId === null) {
            return null;
        }

        return User::query()
            ->select(['id', 'organization_id', 'name', 'email', 'role', 'status', 'locale', 'last_login_at', 'created_at', 'updated_at'])
            ->find($this->userId);
    }

    /**
     * @return array<int, array{id: int, name: string, email: string}>
     */
    private function resolveCopyableManagers(Organization $organization, User $manager): array
    {
        return User::query()
            ->select(['id', 'name', 'email'])
            ->where('organization_id', $organization->id)
            ->where('role', UserRole::MANAGER)
            ->whereKeyNot($manager->id)
            ->orderBy('name')
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ])
            ->all();
    }
}
