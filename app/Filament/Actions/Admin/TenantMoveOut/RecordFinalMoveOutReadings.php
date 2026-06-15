<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\TenantMoveOut;

use App\Enums\AuditLogAction;
use App\Enums\MeterReadingStatus;
use App\Enums\MeterReadingSubmissionMethod;
use App\Enums\MeterReadingType;
use App\Enums\MoveOutProcessStatus;
use App\Filament\Actions\Admin\TenantMoveOut\Concerns\AuthorizesTenantMoveOut;
use App\Filament\Support\Admin\ReadingValidation\ValidateReadingValue;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\MoveOutProcess;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RecordFinalMoveOutReadings
{
    use AuthorizesTenantMoveOut;

    public function __construct(
        private readonly ValidateReadingValue $validateReadingValue,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<int|string, array<string, mixed>>  $readings
     * @return Collection<int, MeterReading>
     */
    public function handle(User $actor, MoveOutProcess $process, array $readings): Collection
    {
        $this->authorizeTenantMoveOut($actor, (int) $process->organization_id);

        if ($readings === []) {
            throw ValidationException::withMessages([
                'readings' => __('admin.move_out.messages.final_readings_required'),
            ]);
        }

        if (! $process->status instanceof MoveOutProcessStatus || ! $process->status->isOpen()) {
            throw ValidationException::withMessages([
                'move_out_process' => __('admin.move_out.messages.process_not_open'),
            ]);
        }

        return DB::transaction(function () use ($actor, $process, $readings): Collection {
            $process->loadMissing(['propertyAssignment:id,organization_id,tenant_user_id,property_id']);
            $created = collect();

            foreach ($this->normalizeReadings($readings) as $readingInput) {
                $meter = Meter::query()
                    ->select(['id', 'organization_id', 'property_id', 'name', 'identifier', 'type', 'status', 'unit'])
                    ->forOrganization((int) $process->organization_id)
                    ->forProperty((int) $process->property_id)
                    ->active()
                    ->whereKey((int) $readingInput['meter_id'])
                    ->first();

                if (! $meter instanceof Meter) {
                    throw ValidationException::withMessages([
                        'meter_id' => __('tenant.pages.readings.unauthorized_meter'),
                    ]);
                }

                $readingDate = (string) ($readingInput['reading_date'] ?? $process->move_out_date?->toDateString());
                $validation = $this->validateReadingValue->handle($meter, $readingInput['reading_value'], $readingDate);

                if ($validation->fails()) {
                    throw ValidationException::withMessages($validation->messages);
                }

                $reading = MeterReading::query()->create([
                    'organization_id' => $process->organization_id,
                    'property_id' => $process->property_id,
                    'meter_id' => $meter->id,
                    'submitted_by_user_id' => $actor->id,
                    'reading_value' => $readingInput['reading_value'],
                    'reading_date' => $readingDate,
                    'current_value' => $readingInput['reading_value'],
                    'validation_status' => $validation->status,
                    'status' => MeterReadingStatus::fromValidationStatus($validation->status),
                    'submitted_at' => now(),
                    'approved_by_user_id' => $validation->status->value === 'valid' ? $actor->id : null,
                    'approved_at' => $validation->status->value === 'valid' ? now() : null,
                    'submission_method' => MeterReadingSubmissionMethod::ADMIN_MANUAL,
                    'reading_type' => MeterReadingType::MOVE_OUT_FINAL,
                    'property_assignment_id' => $process->property_assignment_id,
                    'move_out_process_id' => $process->id,
                    'notes' => $this->mergeNotes((string) ($readingInput['notes'] ?? ''), $validation->notesAsText()),
                ]);

                $this->auditLogger->record(
                    AuditLogAction::CREATED,
                    $reading,
                    [
                        'context' => ['mutation' => 'tenant_move_out.final_reading_recorded'],
                        'move_out_process_id' => $process->id,
                    ],
                    $actor->id,
                    'Final move-out meter reading recorded',
                );

                $created->push($reading->fresh(['meter']));
            }

            $beforeProcess = $process->getOriginal();
            $process->forceFill([
                'status' => MoveOutProcessStatus::READY_FOR_FINAL_INVOICE,
                'final_readings_completed_at' => now(),
            ])->save();

            $this->auditLogger->record(
                AuditLogAction::UPDATED,
                $process,
                [
                    'context' => ['mutation' => 'tenant_move_out.final_readings_completed'],
                    'before' => $beforeProcess,
                    'after' => $process->getAttributes(),
                    'reading_ids' => $created->pluck('id')->all(),
                ],
                $actor->id,
                'Tenant move-out final readings completed',
            );

            return $created;
        });
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $readings
     * @return list<array{meter_id: int|string, reading_value: int|float|string, reading_date?: string, notes?: string|null}>
     */
    private function normalizeReadings(array $readings): array
    {
        return collect($readings)
            ->map(function (array $reading, int|string $key): array {
                if (! array_key_exists('meter_id', $reading) && is_numeric($key)) {
                    $reading['meter_id'] = (int) $key;
                }

                return $reading;
            })
            ->filter(fn (array $reading): bool => filled($reading['meter_id'] ?? null) && filled($reading['reading_value'] ?? null))
            ->values()
            ->all();
    }

    private function mergeNotes(?string ...$notes): ?string
    {
        $compiledNotes = array_values(array_filter($notes, fn (?string $note): bool => filled($note)));

        if ($compiledNotes === []) {
            return null;
        }

        return implode("\n", $compiledNotes);
    }
}
