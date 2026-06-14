<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\ExtraCharges;

use App\Enums\AuditLogAction;
use App\Enums\ExtraChargeStatus;
use App\Enums\ExtraChargeTypeCode;
use App\Enums\UserRole;
use App\Filament\Support\Audit\AuditLogger;
use App\Http\Requests\Admin\ExtraCharges\ExtraChargeRequest;
use App\Models\ExtraCharge;
use App\Models\ExtraChargeType;
use App\Models\Organization;
use App\Models\PropertyAssignment;
use App\Models\User;
use App\Notifications\Billing\ExtraChargeAddedToUpcomingInvoiceNotification;
use App\Notifications\Billing\ExtraChargeRequiresApprovalNotification;
use App\Services\Billing\UniversalBillingCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateExtraChargeAction
{
    public function __construct(
        private readonly UniversalBillingCalculator $calculator,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function handle(User $actor, Organization $organization, array $data): ExtraCharge
    {
        $type = $this->chargeType($organization, $data);
        $validated = (new ExtraChargeRequest)
            ->forOrganization($organization->id)
            ->validatePayload($this->withDefaults($type, $data), $actor);

        $this->ensureTenantPropertyConnection($organization, $validated);
        $this->ensureTenantVisibleDescription($type, $validated);

        $totalAmount = $this->totalAmount($validated);
        $this->ensureAmountDirection($type, $totalAmount);

        $status = $this->statusFor($actor, $validated, $totalAmount);

        $charge = DB::transaction(function () use ($actor, $organization, $validated, $status, $totalAmount): ExtraCharge {
            $charge = ExtraCharge::query()->create([
                'organization_id' => $organization->id,
                'tenant_id' => $validated['tenant_id'],
                'property_id' => $validated['property_id'],
                'billing_period_id' => $validated['billing_period_id'] ?? null,
                'invoice_id' => $validated['invoice_id'] ?? null,
                'extra_charge_type_id' => $validated['extra_charge_type_id'],
                'title' => $validated['title'],
                'description_for_tenant' => $validated['description_for_tenant'] ?? null,
                'internal_note' => $validated['internal_note'] ?? null,
                'amount' => $this->calculator->money($validated['amount']),
                'currency' => $validated['currency'],
                'quantity' => $this->calculator->quantity($validated['quantity']),
                'unit_price' => $this->calculator->rate($validated['unit_price']),
                'tax_amount' => $this->calculator->money($validated['tax_amount'] ?? '0'),
                'total_amount' => $totalAmount,
                'status' => $status,
                'is_recurring' => $validated['is_recurring'],
                'starts_at' => $validated['starts_at'] ?? null,
                'ends_at' => $validated['ends_at'] ?? null,
                'created_by_user_id' => $actor->id,
                'approved_by_user_id' => $status === ExtraChargeStatus::APPROVED ? $actor->id : null,
                'approved_at' => $status === ExtraChargeStatus::APPROVED ? now() : null,
            ]);

            $this->auditLogger->record(
                AuditLogAction::CREATED,
                $charge,
                [
                    'context' => ['mutation' => 'extra_charge.created'],
                    'after' => $charge->getAttributes(),
                ],
                $actor->id,
                'Extra charge created',
            );

            return $charge->fresh(['tenant', 'type']);
        });

        if ($charge->status === ExtraChargeStatus::PENDING_REVIEW) {
            $this->notifyAdmins($organization, $actor, $charge);
        } elseif ($charge->isTenantVisible() && $charge->tenant instanceof User) {
            $charge->tenant->notify(new ExtraChargeAddedToUpcomingInvoiceNotification($charge));
        }

        return $charge;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function chargeType(Organization $organization, array $data): ExtraChargeType
    {
        $typeId = $data['extra_charge_type_id'] ?? null;

        if (! is_numeric($typeId)) {
            throw ValidationException::withMessages([
                'extra_charge_type_id' => __('validation.required', [
                    'attribute' => __('requests.attributes.extra_charge_type_id'),
                ]),
            ]);
        }

        $type = ExtraChargeType::query()
            ->select([
                'id',
                'organization_id',
                'name',
                'type',
                'default_amount',
                'currency',
                'is_recurring',
                'tenant_visible_by_default',
                'requires_comment',
                'requires_attachment',
                'is_active',
            ])
            ->forOrganization($organization->id)
            ->find($typeId);

        if (! $type instanceof ExtraChargeType) {
            throw ValidationException::withMessages([
                'extra_charge_type_id' => __('validation.exists', [
                    'attribute' => __('requests.attributes.extra_charge_type_id'),
                ]),
            ]);
        }

        return $type;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function withDefaults(ExtraChargeType $type, array $data): array
    {
        $amount = $data['amount'] ?? $type->default_amount;
        $quantity = $data['quantity'] ?? '1';
        $unitPrice = $data['unit_price'] ?? $amount;
        $taxAmount = $data['tax_amount'] ?? '0';
        $totalAmount = $data['total_amount']
            ?? $this->calculator->money(
                $this->calculator->add(
                    $this->calculator->multiply($quantity, $unitPrice, 6),
                    $taxAmount,
                    6,
                ),
            );

        return [
            ...$data,
            'amount' => $amount,
            'currency' => $data['currency'] ?? $type->currency,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'is_recurring' => $data['is_recurring'] ?? $type->is_recurring,
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function ensureTenantPropertyConnection(Organization $organization, array $validated): void
    {
        $hasAssignment = PropertyAssignment::query()
            ->select(['id'])
            ->forOrganization($organization->id)
            ->forTenant((int) $validated['tenant_id'])
            ->forProperty((int) $validated['property_id'])
            ->exists();

        if ($hasAssignment) {
            return;
        }

        throw ValidationException::withMessages([
            'property_id' => __('admin.extra_charges.messages.tenant_property_required'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function ensureTenantVisibleDescription(ExtraChargeType $type, array $validated): void
    {
        if (! $type->tenant_visible_by_default && ! $type->requires_comment) {
            return;
        }

        if (filled($validated['title'] ?? null) && filled($validated['description_for_tenant'] ?? null)) {
            return;
        }

        throw ValidationException::withMessages([
            'description_for_tenant' => __('admin.extra_charges.messages.tenant_description_required'),
        ]);
    }

    private function ensureAmountDirection(ExtraChargeType $type, string $totalAmount): void
    {
        $typeCode = $type->type instanceof ExtraChargeTypeCode ? $type->type : null;

        if ($typeCode?->allowsNegativeAmount() === true) {
            return;
        }

        if ($this->calculator->compare($totalAmount, '0', 2) > 0) {
            return;
        }

        throw ValidationException::withMessages([
            'total_amount' => __('admin.extra_charges.messages.positive_amount_required'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function statusFor(User $actor, array $validated, string $totalAmount): ExtraChargeStatus
    {
        $requestedStatus = ExtraChargeStatus::tryFrom((string) ($validated['status'] ?? ''));

        if ($requestedStatus === ExtraChargeStatus::DRAFT) {
            return ExtraChargeStatus::DRAFT;
        }

        if ($actor->isManager() && $this->requiresApproval($totalAmount)) {
            return ExtraChargeStatus::PENDING_REVIEW;
        }

        return $requestedStatus instanceof ExtraChargeStatus
            ? $requestedStatus
            : ExtraChargeStatus::APPROVED;
    }

    private function requiresApproval(string $totalAmount): bool
    {
        $threshold = (string) config('tenanto.billing.extra_charge_manager_approval_threshold', '100.00');
        $absoluteAmount = $this->calculator->compare($totalAmount, '0', 2) < 0
            ? $this->calculator->multiply($totalAmount, '-1', 2)
            : $totalAmount;

        return $this->calculator->compare($absoluteAmount, $threshold, 2) > 0;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function totalAmount(array $validated): string
    {
        return $this->calculator->money(
            $validated['total_amount']
            ?? $this->calculator->add(
                $this->calculator->multiply($validated['quantity'], $validated['unit_price'], 6),
                $validated['tax_amount'] ?? '0',
                6,
            ),
        );
    }

    private function notifyAdmins(Organization $organization, User $actor, ExtraCharge $charge): void
    {
        User::query()
            ->select(['id', 'organization_id', 'name', 'email', 'role'])
            ->forOrganization($organization->id)
            ->where('role', UserRole::ADMIN)
            ->whereKeyNot($actor->id)
            ->get()
            ->each(fn (User $admin): mixed => $admin->notify(new ExtraChargeRequiresApprovalNotification($charge, $actor)));
    }
}
