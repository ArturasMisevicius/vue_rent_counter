<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\TenantMoveOut;

use App\Enums\AuditLogAction;
use App\Enums\InvoiceItemSourceType;
use App\Enums\MoveOutProcessStatus;
use App\Filament\Actions\Admin\TenantMoveOut\Concerns\AuthorizesTenantMoveOut;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\Invoice;
use App\Models\MeterReading;
use App\Models\MoveOutProcess;
use App\Models\Organization;
use App\Models\PropertyAssignment;
use App\Models\User;
use App\Services\Billing\InvoiceService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class GenerateFinalInvoice
{
    use AuthorizesTenantMoveOut;

    public function __construct(
        private readonly InvoiceService $invoiceService,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array{due_date?: string|null, allow_without_final_readings?: bool}  $data
     */
    public function handle(User $actor, MoveOutProcess $process, array $data = []): Invoice
    {
        $this->authorizeTenantMoveOut($actor, (int) $process->organization_id);

        $existingInvoice = $process->finalInvoice()->first();

        if ($existingInvoice instanceof Invoice) {
            return $existingInvoice;
        }

        if (
            (bool) $process->final_readings_required
            && $process->final_readings_completed_at === null
            && ! (bool) ($data['allow_without_final_readings'] ?? false)
        ) {
            throw ValidationException::withMessages([
                'final_readings' => __('admin.move_out.messages.final_readings_before_invoice'),
            ]);
        }

        return DB::transaction(function () use ($actor, $process, $data): Invoice {
            $process->loadMissing([
                'organization:id,name',
                'propertyAssignment:id,organization_id,property_id,tenant_user_id,assigned_at,billing_start_date',
                'finalReadings.meter:id,organization_id,property_id,name,identifier,type,unit',
            ]);

            $organization = $process->organization;
            $assignment = $process->propertyAssignment;

            if (! $organization instanceof Organization || ! $assignment instanceof PropertyAssignment) {
                throw ValidationException::withMessages([
                    'move_out_process' => __('admin.move_out.messages.assignment_required'),
                ]);
            }

            $periodStart = $this->periodStart($assignment, $process);
            $periodEnd = CarbonImmutable::parse((string) $process->move_out_date)->toDateString();
            $dueDate = filled($data['due_date'] ?? null)
                ? CarbonImmutable::parse((string) $data['due_date'])->toDateString()
                : CarbonImmutable::parse($periodEnd)->addDays(14)->toDateString();
            $readings = $process->finalReadings;
            $items = $readings->isEmpty()
                ? [$this->emptyFinalInvoiceLineItem()]
                : $readings
                    ->map(fn (MeterReading $reading, int $index): array => $this->finalReadingLineItem($reading, $index + 1))
                    ->values()
                    ->all();

            $invoice = $this->invoiceService->createDraft($organization, $assignment, [
                'billing_period_start' => $periodStart,
                'billing_period_end' => $periodEnd,
                'due_date' => $dueDate,
                'items' => $items,
                'notes' => __('admin.move_out.messages.final_invoice_note', ['date' => $periodEnd]),
            ], $actor);

            $invoice->forceFill([
                'property_assignment_id' => $assignment->id,
                'move_out_process_id' => $process->id,
                'invoice_type' => 'move_out_final',
                'is_final' => true,
                'automation_level' => 'move_out_final_invoice',
                'approval_status' => 'ready_for_review',
                'approval_metadata' => [
                    'workflow' => 'tenant_move_out',
                    'move_out_process_id' => $process->id,
                    'property_assignment_id' => $assignment->id,
                    'final_meter_reading_ids' => $readings->pluck('id')->values()->all(),
                    'generated_by_user_id' => $actor->id,
                    'generated_at' => now()->toISOString(),
                ],
            ])->save();

            MeterReading::query()
                ->whereKey($readings->pluck('id')->all())
                ->update(['invoice_id' => $invoice->id]);

            $beforeProcess = $process->getOriginal();
            $process->forceFill([
                'status' => MoveOutProcessStatus::READY_FOR_FINAL_INVOICE,
                'final_invoice_id' => $invoice->id,
            ])->save();

            $this->auditLogger->record(
                AuditLogAction::CREATED,
                $invoice,
                [
                    'context' => ['mutation' => 'tenant_move_out.final_invoice_generated'],
                    'move_out_process_id' => $process->id,
                    'final_meter_reading_ids' => $readings->pluck('id')->values()->all(),
                ],
                $actor->id,
                'Final move-out invoice generated',
            );

            $this->auditLogger->record(
                AuditLogAction::UPDATED,
                $process,
                [
                    'context' => ['mutation' => 'tenant_move_out.final_invoice_linked'],
                    'before' => $beforeProcess,
                    'after' => $process->getAttributes(),
                ],
                $actor->id,
                'Final invoice linked to tenant move-out',
            );

            return $invoice->fresh(['invoiceItems', 'moveOutProcess']) ?? $invoice;
        });
    }

    private function periodStart(PropertyAssignment $assignment, MoveOutProcess $process): string
    {
        $date = $assignment->billing_start_date
            ?? $assignment->assigned_at
            ?? $process->move_out_date;

        return CarbonImmutable::parse((string) $date)->toDateString();
    }

    /**
     * @return array<string, mixed>
     */
    private function finalReadingLineItem(MeterReading $reading, int $sortOrder): array
    {
        $meter = $reading->meter;
        $meterName = (string) ($meter?->identifier ?: $meter?->name ?: "#{$reading->meter_id}");
        $unit = $meter?->unit;

        return [
            'source_type' => InvoiceItemSourceType::METER_READING->value,
            'source_id' => $reading->id,
            'title' => __('admin.move_out.messages.final_reading_line_title', ['meter' => $meterName]),
            'description' => __('admin.move_out.messages.final_reading_line_description', [
                'meter' => $meterName,
                'value' => (string) $reading->reading_value,
                'unit' => $unit ?? '',
            ]),
            'quantity' => '1',
            'unit' => null,
            'unit_price' => '0',
            'rate' => '0',
            'subtotal' => '0',
            'tax_amount' => '0',
            'discount_amount' => '0',
            'total' => '0',
            'currency' => 'EUR',
            'formula_label' => __('admin.invoices.formulas.manual_amount'),
            'sort_order' => $sortOrder,
            'meter_reading_snapshot' => [
                'end' => [
                    'id' => $reading->id,
                    'meter_id' => $reading->meter_id,
                    'value' => (string) $reading->reading_value,
                    'date' => $reading->reading_date?->toDateString(),
                    'unit' => $unit,
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyFinalInvoiceLineItem(): array
    {
        return [
            'source_type' => InvoiceItemSourceType::MANUAL_ADJUSTMENT->value,
            'title' => __('admin.move_out.messages.final_invoice_lifecycle_line_title'),
            'description' => __('admin.move_out.messages.final_invoice_lifecycle_line_description'),
            'quantity' => '1',
            'unit' => null,
            'unit_price' => '0',
            'rate' => '0',
            'subtotal' => '0',
            'tax_amount' => '0',
            'discount_amount' => '0',
            'total' => '0',
            'currency' => 'EUR',
            'formula_label' => __('admin.invoices.formulas.manual_amount'),
            'sort_order' => 1,
        ];
    }
}
