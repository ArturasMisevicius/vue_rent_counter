<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\ExtraCharges;

use App\Enums\AuditLogAction;
use App\Enums\ExtraChargeStatus;
use App\Filament\Support\Audit\AuditLogger;
use App\Http\Requests\Admin\ExtraCharges\ExtraChargeRequest;
use App\Models\ExtraCharge;
use App\Models\ExtraChargeType;
use App\Models\User;
use App\Services\Billing\UniversalBillingCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateExtraChargeAction
{
    public function __construct(
        private readonly UniversalBillingCalculator $calculator,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function handle(User $actor, ExtraCharge $charge, array $data): ExtraCharge
    {
        if (! $charge->canBeSilentlyChanged()) {
            throw ValidationException::withMessages([
                'invoice_id' => __('admin.extra_charges.messages.invoice_locked'),
            ]);
        }

        $charge->loadMissing(['type']);
        $type = $charge->type instanceof ExtraChargeType
            ? $charge->type
            : ExtraChargeType::query()->findOrFail($charge->extra_charge_type_id);

        $validated = (new ExtraChargeRequest)
            ->forOrganization($charge->organization_id)
            ->validatePayload($this->withDefaults($charge, $type, $data), $actor);

        $totalAmount = $this->totalAmount($validated);
        $this->ensureAmountDirection($type, $totalAmount);

        return DB::transaction(function () use ($actor, $charge, $validated, $totalAmount): ExtraCharge {
            $before = $charge->getAttributes();

            $charge->update([
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
                'status' => ExtraChargeStatus::tryFrom((string) ($validated['status'] ?? '')) ?? $charge->status,
                'is_recurring' => $validated['is_recurring'],
                'starts_at' => $validated['starts_at'] ?? null,
                'ends_at' => $validated['ends_at'] ?? null,
            ]);

            $fresh = $charge->fresh(['tenant', 'type', 'invoice']);

            $this->auditLogger->record(
                AuditLogAction::UPDATED,
                $fresh,
                [
                    'context' => ['mutation' => 'extra_charge.updated'],
                    'before' => $before,
                    'after' => $fresh->getAttributes(),
                ],
                $actor->id,
                'Extra charge updated',
            );

            return $fresh;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function withDefaults(ExtraCharge $charge, ExtraChargeType $type, array $data): array
    {
        $amount = $data['amount'] ?? $charge->amount ?? $type->default_amount;
        $quantity = $data['quantity'] ?? $charge->quantity ?? '1';
        $unitPrice = $data['unit_price'] ?? $charge->unit_price ?? $amount;
        $taxAmount = $data['tax_amount'] ?? $charge->tax_amount ?? '0';
        $totalAmount = $data['total_amount']
            ?? $this->calculator->money(
                $this->calculator->add(
                    $this->calculator->multiply($quantity, $unitPrice, 6),
                    $taxAmount,
                    6,
                ),
            );

        return [
            'tenant_id' => $charge->tenant_id,
            'property_id' => $charge->property_id,
            'billing_period_id' => $charge->billing_period_id,
            'invoice_id' => $charge->invoice_id,
            'extra_charge_type_id' => $charge->extra_charge_type_id,
            'title' => $charge->title,
            'description_for_tenant' => $charge->description_for_tenant,
            'internal_note' => $charge->internal_note,
            'currency' => $charge->currency,
            'status' => $charge->status,
            'is_recurring' => $charge->is_recurring,
            'starts_at' => $charge->starts_at?->toDateString(),
            'ends_at' => $charge->ends_at?->toDateString(),
            ...$data,
            'amount' => $amount,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
        ];
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

    private function ensureAmountDirection(ExtraChargeType $type, string $totalAmount): void
    {
        if ($type->type?->allowsNegativeAmount() === true) {
            return;
        }

        if ($this->calculator->compare($totalAmount, '0', 2) > 0) {
            return;
        }

        throw ValidationException::withMessages([
            'total_amount' => __('admin.extra_charges.messages.positive_amount_required'),
        ]);
    }
}
