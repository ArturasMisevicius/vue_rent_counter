<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\Tenants;

use App\Enums\AuditLogAction;
use App\Enums\PropertyAssignmentStatus;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Actions\Admin\Properties\AssignTenantToPropertyAction;
use App\Filament\Support\Admin\ManagerPermissions\ManagerPermissionService;
use App\Filament\Support\Admin\SubscriptionLimitGuard;
use App\Filament\Support\Audit\AuditLogger;
use App\Filament\Support\Tenants\CheckTenantBillingReadiness;
use App\Filament\Support\Tenants\TenantCreationResult;
use App\Http\Requests\Admin\Tenants\StoreTenantRequest;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class CreateTenantWithAssignment
{
    public function __construct(
        private readonly SubscriptionLimitGuard $subscriptionLimitGuard,
        private readonly AssignTenantToPropertyAction $assignTenantToPropertyAction,
        private readonly SendTenantInvitation $sendTenantInvitation,
        private readonly CheckTenantBillingReadiness $checkTenantBillingReadiness,
        private readonly AuditLogger $auditLogger,
        private readonly ManagerPermissionService $managerPermissionService,
    ) {}

    public function handle(User $actor, array $data, ?Organization $organization = null): TenantCreationResult
    {
        $organization ??= $actor->organization;

        $this->authorize($actor, $organization);

        $validated = $this->validate($actor, (int) $organization->id, $data);

        return DB::transaction(function () use ($actor, $organization, $validated): TenantCreationResult {
            $tenant = User::query()->create([
                'organization_id' => $organization->id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'role' => UserRole::TENANT,
                'status' => UserStatus::INACTIVE,
                'tenant_status' => TenantStatus::DRAFT,
                'portal_access_enabled' => false,
                'locale' => $validated['locale'],
                'password' => Str::random(32),
            ]);

            $this->auditLogger->record(
                AuditLogAction::CREATED,
                $tenant,
                [
                    'context' => [
                        'mutation' => 'tenant.created',
                    ],
                    'after' => [
                        'organization_id' => $tenant->organization_id,
                        'name' => $tenant->name,
                        'email' => $tenant->email,
                        'phone' => $tenant->phone,
                        'locale' => $tenant->locale,
                        'internal_note' => $validated['internal_note'] ?? null,
                    ],
                ],
                actorUserId: $actor->id,
                description: "Tenant {$tenant->name} created",
            );

            $assignment = $this->createAssignment($actor, $tenant, $validated);
            $invitation = $this->sendInvitationIfRequested($actor, $organization, $tenant, $validated);
            $tenant = $tenant->fresh([
                'currentPropertyAssignment.property',
                'latestTenantInvitation',
            ]);

            $billingReadiness = $this->checkTenantBillingReadiness->handle($tenant);

            if (! $billingReadiness->isReady()) {
                $this->auditLogger->record(
                    AuditLogAction::UPDATED,
                    $tenant,
                    [
                        'context' => [
                            'mutation' => 'tenant_billing_readiness.'.$billingReadiness->status->value,
                        ],
                        'billing_readiness' => $billingReadiness->toArray(),
                    ],
                    actorUserId: $actor->id,
                    description: "Tenant {$tenant->name} billing readiness is {$billingReadiness->status->value}",
                );
            }

            if ((bool) $validated['duplicate_override']) {
                $this->auditLogger->record(
                    AuditLogAction::UPDATED,
                    $tenant,
                    [
                        'context' => [
                            'mutation' => 'tenant_duplicate_warning.ignored',
                        ],
                        'duplicate_override' => true,
                    ],
                    actorUserId: $actor->id,
                    description: "Duplicate warning ignored for tenant {$tenant->name}",
                );
            }

            return new TenantCreationResult(
                tenant: $tenant,
                assignment: $assignment,
                invitation: $invitation,
                billingReadiness: $billingReadiness,
                nextSteps: $this->nextSteps($invitation, $assignment, $billingReadiness->nextSteps),
            );
        });
    }

    private function authorize(User $actor, ?Organization $organization): void
    {
        if ((! $actor->isAdmin() && ! $actor->isManager() && ! $actor->isSuperadmin()) || ! $organization instanceof Organization) {
            abort(403);
        }

        if ($actor->isSuperadmin()) {
            return;
        }

        if ((int) $actor->organization_id !== (int) $organization->id) {
            abort(403);
        }

        if ($actor->isManager() && ! $this->managerPermissionService->can($actor, $organization, 'tenants', 'create')) {
            abort(403);
        }

        $this->subscriptionLimitGuard->ensureCanCreateTenant($organization);
    }

    /**
     * @return array{
     *     name: string,
     *     email: string,
     *     phone: string|null,
     *     internal_note: string|null,
     *     locale: string,
     *     create_portal_access: bool,
     *     send_invitation_now: bool,
     *     invitation_expiration_days: int,
     *     property_id: int|null,
     *     unit_area_sqm: float|int|null,
     *     move_in_date: string|null,
     *     move_out_date: string|null,
     *     assignment_status: string,
     *     is_primary: bool,
     *     occupants_count: int|null,
     *     duplicate_override: bool,
     *     property: Property|null
     * }
     */
    private function validate(User $actor, int $organizationId, array $data): array
    {
        /** @var StoreTenantRequest $request */
        $request = new StoreTenantRequest;
        $validated = $request
            ->forOrganization($organizationId)
            ->validatePayload($data, $actor);

        $property = null;

        if ($validated['property_id'] !== null) {
            $property = Property::query()
                ->select(['id', 'organization_id', 'building_id', 'name', 'floor', 'unit_number', 'type', 'floor_area_sqm'])
                ->where('organization_id', $organizationId)
                ->find($validated['property_id']);
        }

        return [
            'phone' => $validated['phone'] ?? null,
            'internal_note' => $validated['internal_note'] ?? null,
            'property_id' => $validated['property_id'] ?? null,
            'unit_area_sqm' => $validated['unit_area_sqm'] ?? null,
            'move_in_date' => $validated['move_in_date'] ?? null,
            'move_out_date' => $validated['move_out_date'] ?? null,
            'occupants_count' => $validated['occupants_count'] ?? null,
            ...$validated,
            'property' => $property,
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function createAssignment(User $actor, User $tenant, array $validated): ?PropertyAssignment
    {
        if (! $validated['property'] instanceof Property) {
            return null;
        }

        $assignment = $this->assignTenantToPropertyAction->handle(
            property: $validated['property'],
            tenant: $tenant,
            unitAreaSqm: $validated['unit_area_sqm'] !== null ? (float) $validated['unit_area_sqm'] : null,
            moveInDate: $validated['move_in_date'] !== null ? CarbonImmutable::parse((string) $validated['move_in_date']) : null,
            moveOutDate: $validated['move_out_date'] !== null ? CarbonImmutable::parse((string) $validated['move_out_date']) : null,
            status: PropertyAssignmentStatus::from((string) $validated['assignment_status']),
            isPrimary: (bool) $validated['is_primary'],
            occupantsCount: $validated['occupants_count'] !== null ? (int) $validated['occupants_count'] : null,
            actor: $actor,
        );

        $this->auditLogger->record(
            AuditLogAction::CREATED,
            $assignment,
            [
                'context' => [
                    'mutation' => 'tenant_property_assignment.created',
                ],
                'tenant' => [
                    'id' => $tenant->id,
                    'email' => $tenant->email,
                ],
                'property' => [
                    'id' => $assignment->property_id,
                ],
                'after' => [
                    'status' => $assignment->status?->value,
                    'is_primary' => $assignment->is_primary,
                    'move_in_date' => $assignment->assigned_at?->toDateString(),
                    'move_out_date' => $assignment->unassigned_at?->toDateString(),
                ],
            ],
            actorUserId: $actor->id,
            description: "Tenant {$tenant->name} assigned to property {$assignment->property_id}",
        );

        return $assignment;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function sendInvitationIfRequested(
        User $actor,
        Organization $organization,
        User $tenant,
        array $validated,
    ): ?OrganizationInvitation {
        if (! $validated['create_portal_access'] || ! $validated['send_invitation_now']) {
            return null;
        }

        return $this->sendTenantInvitation->handle(
            $actor->isSuperadmin() ? $this->resolveInviterForSuperadmin($organization) : $actor,
            $tenant,
            (int) $validated['invitation_expiration_days'],
        );
    }

    /**
     * @param  array<int, string>  $billingNextSteps
     * @return array<int, string>
     */
    private function nextSteps(
        ?OrganizationInvitation $invitation,
        ?PropertyAssignment $assignment,
        array $billingNextSteps,
    ): array {
        $steps = [];

        if (! $invitation instanceof OrganizationInvitation) {
            $steps[] = 'send_invitation';
        }

        $steps[] = 'upload_rental_contract';

        if (! $assignment instanceof PropertyAssignment) {
            $steps[] = 'assign_property';
        }

        $steps = [
            ...$steps,
            ...$billingNextSteps,
            'open_tenant_profile',
        ];

        return array_values(array_unique($steps));
    }

    private function resolveInviterForSuperadmin(Organization $organization): User
    {
        $organization->loadMissing([
            'owner:id,organization_id,name,email,role,status',
        ]);

        $inviter = $organization->owner;

        if (! $inviter instanceof User || ! $inviter->isAdminLike()) {
            $inviter = $organization->users()
                ->select(['id', 'organization_id', 'name', 'email', 'role', 'status'])
                ->adminLike()
                ->orderedByName()
                ->first();
        }

        if (! $inviter instanceof User) {
            throw ValidationException::withMessages([
                'organization_id' => __('superadmin.organizations.messages.no_primary_admin'),
            ]);
        }

        return $inviter;
    }
}
