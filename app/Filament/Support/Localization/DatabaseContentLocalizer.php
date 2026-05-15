<?php

declare(strict_types=1);

namespace App\Filament\Support\Localization;

use App\Enums\MeterType;
use App\Enums\PropertyType;
use App\Enums\ServiceType;
use App\Filament\Support\Billing\InvoiceContentLocalizer;
use App\Models\Translation;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final class DatabaseContentLocalizer
{
    private const GROUP = 'database_content';

    /**
     * @var array<string, array<string, array<string, string|null>>>
     */
    private array $translationsByGroup = [];

    public function meterName(?string $name, MeterType|string|null $type): string
    {
        $normalizedName = $this->normalized($name);

        if ($normalizedName === '') {
            return __('dashboard.not_available');
        }

        if (preg_match('/^Meter (?<number>[A-Za-z0-9-]+)$/', $normalizedName, $matches) === 1) {
            return __('tenant.pages.property.generic_meter_label', [
                'number' => $matches['number'],
            ]);
        }

        $typeLabel = $this->meterTypeLabel($type);

        if ($this->isGeneratedDemoMeterName($normalizedName, $type)) {
            return $this->translate('meters.generated_demo_meter.name', $normalizedName, [
                'type' => $typeLabel,
            ]);
        }

        if ($this->isOperationsDemoMeterName($normalizedName, $type)) {
            return $this->translate('meters.operations_demo_meter.name', $normalizedName, [
                'type' => $typeLabel,
            ]);
        }

        return $normalizedName;
    }

    public function meterReadingNotes(?string $notes): ?string
    {
        $normalizedNotes = $this->normalized($notes);

        if ($normalizedNotes === '') {
            return $notes;
        }

        if ($this->sameText($normalizedNotes, 'Seeded legacy operations reading.')) {
            return $this->translate('meter_readings.notes.seeded_legacy_operations_reading', $normalizedNotes);
        }

        return $notes;
    }

    public function meterReadingChangeReason(?string $reason): ?string
    {
        $normalizedReason = $this->normalized($reason);

        if ($normalizedReason === '') {
            return $reason;
        }

        if ($this->sameText($normalizedReason, 'Seeded baseline validation check')) {
            return $this->translate('meter_readings.change_reasons.seeded_baseline_validation_check', $normalizedReason);
        }

        return $reason;
    }

    public function buildingName(?string $name): string
    {
        $normalizedName = $this->normalized($name);

        if ($normalizedName === '') {
            return '';
        }

        if (preg_match('/^Demo Building (?<number>\d{2}-\d{2})$/', $normalizedName, $matches) === 1) {
            return $this->translate('buildings.demo_building.name', $normalizedName, [
                'number' => $matches['number'],
            ]);
        }

        return $normalizedName;
    }

    public function propertyName(
        ?string $name,
        PropertyType|string|null $type = null,
        int|string|null $unitNumber = null,
    ): string {
        $normalizedName = $this->normalized($name);

        if ($normalizedName === '') {
            return '';
        }

        if (preg_match('/^Demo Unit (?<number>\d{2}-\d{2})$/', $normalizedName, $matches) === 1) {
            return $this->translate('properties.demo_unit.name', $normalizedName, [
                'number' => $matches['number'],
            ]);
        }

        $unit = $this->normalized(is_scalar($unitNumber) ? (string) $unitNumber : null);
        $typeLabel = $this->generatedPropertyTypeLabel($normalizedName, $type, $unit);

        if ($typeLabel !== null && $unit !== '') {
            return __('tenant.pages.property.property_unit_label', [
                'type' => $typeLabel,
                'unit' => $unit,
            ]);
        }

        $generatedParts = $this->generatedPropertyPartsFromName($normalizedName, $type);

        if ($generatedParts !== null) {
            return __('tenant.pages.property.property_unit_label', $generatedParts);
        }

        return $normalizedName;
    }

    public function invoiceNotes(?string $notes): ?string
    {
        $normalizedNotes = $this->normalized($notes);

        if ($normalizedNotes === '') {
            return $notes;
        }

        if (preg_match('/^Demo invoice (?<number>\d+) for (?<property>.+)$/', $normalizedNotes, $matches) === 1) {
            return $this->translate('invoices.demo_invoice.notes', $normalizedNotes, [
                'number' => $matches['number'],
                'property' => $this->propertyName($matches['property']),
            ]);
        }

        if (preg_match('/^Seeded login demo invoice (?<number>\d+)$/', $normalizedNotes, $matches) === 1) {
            return $this->translate('invoices.seeded_login_demo_invoice.notes', $normalizedNotes, [
                'number' => $matches['number'],
            ]);
        }

        if ($this->sameText($normalizedNotes, 'Legacy operations foundation demo invoice.')) {
            return $this->translate('invoices.legacy_operations_foundation_demo_invoice.notes', $normalizedNotes);
        }

        return $notes;
    }

    public function billingRecordNotes(?string $notes): ?string
    {
        $normalizedNotes = $this->normalized($notes);

        if ($normalizedNotes === '') {
            return $notes;
        }

        if ($this->sameText($normalizedNotes, 'Seeded billing record for demo invoice.')) {
            return $this->translate('billing_records.seeded_demo_invoice_record.notes', $normalizedNotes);
        }

        if (preg_match('/^Seeded (?<description>.+) billing record$/', $normalizedNotes, $matches) === 1) {
            $description = app(InvoiceContentLocalizer::class)->lineItemDescription($matches['description']);

            return $this->translate('billing_records.seeded_billing_record.notes', $normalizedNotes, [
                'description' => $description,
            ]);
        }

        return $notes;
    }

    public function projectName(?string $name): string
    {
        $normalizedName = $this->normalized($name);

        if ($normalizedName === '') {
            return '';
        }

        if ($this->sameText($normalizedName, 'Legacy Collaboration Demo Project')) {
            return $this->translate('projects.legacy_collaboration_demo_project.name', $normalizedName);
        }

        if (preg_match('/^(?<name>.+) Modernization Program$/', $normalizedName, $matches) === 1) {
            return $this->translate('projects.modernization_program.name', $normalizedName, [
                'name' => $matches['name'],
            ]);
        }

        return $normalizedName;
    }

    public function projectDescription(?string $description): ?string
    {
        $normalizedDescription = $this->normalized($description);

        if ($normalizedDescription === '') {
            return $description;
        }

        if ($this->sameText($normalizedDescription, 'Imported collaboration foundation demo project.')) {
            return $this->translate('projects.legacy_collaboration_demo_project.description', $normalizedDescription);
        }

        if (preg_match('/^Operational improvement plan for (?<name>.+)\\.$/', $normalizedDescription, $matches) === 1) {
            return $this->translate('projects.modernization_program.description', $normalizedDescription, [
                'name' => $matches['name'],
            ]);
        }

        return $description;
    }

    public function taskTitle(?string $title): string
    {
        $normalizedTitle = $this->normalized($title);

        if ($normalizedTitle === '') {
            return '';
        }

        if ($this->sameText($normalizedTitle, 'Inspect shared utility setup')) {
            return $this->translate('tasks.inspect_shared_utility_setup.title', $normalizedTitle);
        }

        if ($this->sameText($normalizedTitle, 'Review imported collaboration layer')) {
            return $this->translate('tasks.review_imported_collaboration_layer.title', $normalizedTitle);
        }

        if (preg_match('/^Demo Task (?<number>\\d{2}-\\d{2})$/', $normalizedTitle, $matches) === 1) {
            return $this->translate('tasks.demo_task.title', $normalizedTitle, [
                'number' => $matches['number'],
            ]);
        }

        return $normalizedTitle;
    }

    public function taskDescription(?string $description): ?string
    {
        $normalizedDescription = $this->normalized($description);

        if ($normalizedDescription === '') {
            return $description;
        }

        $key = match (true) {
            $this->sameText($normalizedDescription, 'Inspect shared systems.') => 'tasks.inspect_shared_systems.description',
            $this->sameText($normalizedDescription, 'Coordinate resident communication.') => 'tasks.coordinate_resident_communication.description',
            $this->sameText($normalizedDescription, 'Review imported utility service configuration data.') => 'tasks.inspect_shared_utility_setup.description',
            $this->sameText($normalizedDescription, 'Higher-fidelity task imported from legacy collaboration domain.') => 'tasks.review_imported_collaboration_layer.description',
            default => null,
        };

        return $key === null ? $description : $this->translate($key, $normalizedDescription);
    }

    public function taskAssignmentNotes(?string $notes): ?string
    {
        $normalizedNotes = $this->normalized($notes);

        if ($normalizedNotes === '') {
            return $notes;
        }

        $key = match (true) {
            $this->sameText($normalizedNotes, 'Seeded operational assignment.') => 'task_assignments.seeded_operational_assignment.notes',
            $this->sameText($normalizedNotes, 'Demo tenant assignment for collaboration foundation.') => 'task_assignments.demo_tenant_assignment.notes',
            default => null,
        };

        return $key === null ? $notes : $this->translate($key, $normalizedNotes);
    }

    public function utilityServiceName(?string $name, ServiceType|string|null $type = null): string
    {
        $normalizedName = $this->normalized($name);

        if ($normalizedName === '') {
            return '';
        }

        if (preg_match('/^Org (?<number>\d{2}) (?<service>Electricity|Water|Heating)$/', $normalizedName, $matches) === 1) {
            $serviceType = ServiceType::tryFrom(Str::of($matches['service'])->snake()->value());

            return $this->translate('utility_services.organization_service.name', $normalizedName, [
                'number' => $matches['number'],
                'service' => $this->serviceTypeLabel($serviceType ?? $type),
            ]);
        }

        foreach ($this->candidateServiceTypes($type) as $serviceType) {
            foreach ($this->supportedLocales() as $locale) {
                if ($this->sameText($normalizedName, $this->serviceTypeLabel($serviceType, $locale))) {
                    return $this->serviceTypeLabel($serviceType);
                }
            }
        }

        return $normalizedName;
    }

    public function utilityServiceDescription(?string $description): ?string
    {
        $normalizedDescription = $this->normalized($description);

        if ($normalizedDescription === '') {
            return $description;
        }

        $key = match (true) {
            $this->sameText($normalizedDescription, 'Electricity consumption charges for residential properties.') => 'utility_services.global.electricity.description',
            $this->sameText($normalizedDescription, 'Water supply and sewage charges with a fixed and variable component.') => 'utility_services.global.water.description',
            $this->sameText($normalizedDescription, 'District heating utility charges.') => 'utility_services.global.heating.description',
            $this->sameText($normalizedDescription, 'Electricity utility for tenant consumption billing.') => 'utility_services.organization.electricity.description',
            $this->sameText($normalizedDescription, 'Water utility with fixed and variable components.') => 'utility_services.organization.water.description',
            $this->sameText($normalizedDescription, 'Heating utility for seasonal usage.') => 'utility_services.organization.heating.description',
            default => null,
        };

        return $key === null ? $description : $this->translate($key, $normalizedDescription);
    }

    public function tagDescription(?string $description): ?string
    {
        $normalizedDescription = $this->normalized($description);

        if ($normalizedDescription === '') {
            return $description;
        }

        if ($this->sameText($normalizedDescription, 'Imported from the legacy collaboration foundation.')) {
            return $this->translate('tags.legacy_foundation.description', $normalizedDescription);
        }

        return $description;
    }

    public function commentBody(?string $body): ?string
    {
        $normalizedBody = $this->normalized($body);

        if ($normalizedBody === '') {
            return $body;
        }

        if ($this->sameText($normalizedBody, 'Legacy collaboration layer imported successfully.')) {
            return $this->translate('comments.legacy_collaboration_imported.body', $normalizedBody);
        }

        return $body;
    }

    public function attachmentDescription(?string $description): ?string
    {
        $normalizedDescription = $this->normalized($description);

        if ($normalizedDescription === '') {
            return $description;
        }

        if ($this->sameText($normalizedDescription, 'Seeded demo collaboration attachment.')) {
            return $this->translate('attachments.seeded_demo_collaboration_attachment.description', $normalizedDescription);
        }

        return $description;
    }

    public function subscriptionRenewalNotes(?string $notes): ?string
    {
        $normalizedNotes = $this->normalized($notes);

        if ($normalizedNotes === '') {
            return $notes;
        }

        if ($this->sameText($normalizedNotes, 'Seeded renewal history for legacy operations foundation.')) {
            return $this->translate('subscription_renewals.seeded_legacy_operations_history.notes', $normalizedNotes);
        }

        return $notes;
    }

    public function systemConfigurationDescription(?string $description): ?string
    {
        $normalizedDescription = $this->normalized($description);

        if ($normalizedDescription === '') {
            return $description;
        }

        if ($this->sameText($normalizedDescription, 'Default billing currency for platform-level operations.')) {
            return $this->translate('system_configurations.platform_default_currency.description', $normalizedDescription);
        }

        return $description;
    }

    public function timeEntryDescription(?string $description): ?string
    {
        $normalizedDescription = $this->normalized($description);

        if ($normalizedDescription === '') {
            return $description;
        }

        if ($this->sameText($normalizedDescription, 'Seeded progress update.')) {
            return $this->translate('time_entries.seeded_progress_update.description', $normalizedDescription);
        }

        if ($this->sameText($normalizedDescription, 'Seeded demo time entry for imported collaboration task.')) {
            return $this->translate('time_entries.seeded_imported_collaboration_task.description', $normalizedDescription);
        }

        return $description;
    }

    public function activityDescription(?string $description): ?string
    {
        $normalizedDescription = $this->normalized($description);

        if ($normalizedDescription === '') {
            return $description;
        }

        $key = match (true) {
            $this->sameText($normalizedDescription, 'Seeded collaboration foundation activity') => 'activity_logs.seeded_collaboration_foundation.description',
            $this->sameText($normalizedDescription, 'Imported from the legacy collaboration foundation.') => 'activity_logs.imported_from_legacy_collaboration_foundation.description',
            default => null,
        };

        if ($key !== null) {
            return $this->translate($key, $normalizedDescription);
        }

        return $description;
    }

    /**
     * @param  array<string, string>  $replace
     */
    public function translate(string $key, string $fallback, array $replace = []): string
    {
        $locale = app()->getLocale();
        $fallbackLocale = config('app.fallback_locale', 'en');
        $values = $this->translationsFor(self::GROUP)[$key] ?? [];
        $value = $values[$locale] ?? $values[$fallbackLocale] ?? null;

        if (filled($value)) {
            return $this->replace((string) $value, $replace);
        }

        $translationKey = self::GROUP.'.'.$key;

        if (trans()->has($translationKey, $locale)) {
            return __($translationKey, $replace, $locale);
        }

        if (is_string($fallbackLocale) && trans()->has($translationKey, $fallbackLocale)) {
            return __($translationKey, $replace, $fallbackLocale);
        }

        return $fallback;
    }

    private function isGeneratedDemoMeterName(string $name, MeterType|string|null $type): bool
    {
        return collect($this->supportedLocales())
            ->contains(function (string $locale) use ($name, $type): bool {
                $typeLabel = $this->meterTypeLabel($type, $locale);

                return $this->sameText(
                    $name,
                    __('tenant.pages.property.demo_meter_label', ['type' => $typeLabel], $locale),
                ) || $this->sameText($name, sprintf('Demo %s Meter', $typeLabel));
            });
    }

    private function isOperationsDemoMeterName(string $name, MeterType|string|null $type): bool
    {
        if ($this->sameText($name, 'Operations Demo Meter')) {
            return true;
        }

        return collect($this->supportedLocales())
            ->contains(fn (string $locale): bool => $this->sameText(
                $name,
                __('tenant.pages.property.operations_demo_meter_label', [
                    'type' => $this->meterTypeLabel($type, $locale),
                ], $locale),
            ));
    }

    private function meterTypeLabel(MeterType|string|null $type, ?string $locale = null): string
    {
        $meterType = $type instanceof MeterType ? $type : MeterType::tryFrom((string) $type);

        if (! $meterType instanceof MeterType) {
            return __('tenant.pages.property.meter_label', [], $locale);
        }

        return (string) __($meterType->translationKey(), [], $locale);
    }

    private function propertyTypeLabel(PropertyType|string|null $type, ?string $locale = null): ?string
    {
        $propertyType = $type instanceof PropertyType ? $type : PropertyType::tryFrom((string) $type);

        if (! $propertyType instanceof PropertyType) {
            return null;
        }

        return (string) __($propertyType->translationKey(), [], $locale);
    }

    private function serviceTypeLabel(ServiceType|string|null $type, ?string $locale = null): string
    {
        $serviceType = $type instanceof ServiceType ? $type : ServiceType::tryFrom((string) $type);

        if (! $serviceType instanceof ServiceType) {
            return __('admin.utility_services.singular', [], $locale);
        }

        return (string) __($serviceType->translationKey(), [], $locale);
    }

    private function generatedPropertyTypeLabel(
        string $name,
        PropertyType|string|null $type,
        string $unitNumber,
    ): ?string {
        if ($unitNumber === '') {
            return null;
        }

        foreach ($this->supportedLocales() as $locale) {
            foreach ($this->candidatePropertyTypes($type) as $propertyType) {
                $typeLabel = $this->propertyTypeLabel($propertyType, $locale);

                if ($typeLabel !== null && $this->sameText($name, trim($typeLabel.' '.$unitNumber))) {
                    return $this->propertyTypeLabel($propertyType);
                }
            }
        }

        return null;
    }

    /**
     * @return array{type: string, unit: string}|null
     */
    private function generatedPropertyPartsFromName(string $name, PropertyType|string|null $type): ?array
    {
        foreach ($this->supportedLocales() as $locale) {
            foreach ($this->candidatePropertyTypes($type) as $propertyType) {
                $typeLabel = $this->propertyTypeLabel($propertyType, $locale);

                if ($typeLabel === null || ! Str::of($name)->lower()->startsWith(Str::of($typeLabel.' ')->lower()->value())) {
                    continue;
                }

                $unit = trim(mb_substr($name, mb_strlen($typeLabel)));

                if ($unit === '' || preg_match('/\d/', $unit) !== 1) {
                    continue;
                }

                return [
                    'type' => (string) $this->propertyTypeLabel($propertyType),
                    'unit' => $unit,
                ];
            }
        }

        return null;
    }

    /**
     * @return list<PropertyType>
     */
    private function candidatePropertyTypes(PropertyType|string|null $type): array
    {
        $propertyType = $type instanceof PropertyType ? $type : PropertyType::tryFrom((string) $type);

        return $propertyType instanceof PropertyType ? [$propertyType] : PropertyType::cases();
    }

    /**
     * @return list<ServiceType>
     */
    private function candidateServiceTypes(ServiceType|string|null $type): array
    {
        $serviceType = $type instanceof ServiceType ? $type : ServiceType::tryFrom((string) $type);

        return $serviceType instanceof ServiceType ? [$serviceType] : ServiceType::cases();
    }

    /**
     * @return list<string>
     */
    private function supportedLocales(): array
    {
        $supportedLocales = config('app.supported_locales', ['en' => 'English']);
        $locales = is_array($supportedLocales) ? array_keys($supportedLocales) : [];

        return array_values(array_unique(array_merge(['en'], $locales)));
    }

    /**
     * @return array<string, array<string, string|null>>
     */
    private function translationsFor(string $group): array
    {
        if (array_key_exists($group, $this->translationsByGroup)) {
            return $this->translationsByGroup[$group];
        }

        if (! Schema::hasTable('translations')) {
            return $this->translationsByGroup[$group] = [];
        }

        return $this->translationsByGroup[$group] = Translation::query()
            ->select(['key', 'values'])
            ->where('group', $group)
            ->get()
            ->mapWithKeys(fn (Translation $translation): array => [
                $translation->key => $translation->values ?? [],
            ])
            ->all();
    }

    /**
     * @param  array<string, string>  $replace
     */
    private function replace(string $value, array $replace): string
    {
        return strtr($value, collect($replace)
            ->mapWithKeys(fn (string $item, string $key): array => [':'.$key => $item])
            ->all());
    }

    private function normalized(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        return (string) preg_replace('/\s+/u', ' ', trim($value));
    }

    private function sameText(string $left, string $right): bool
    {
        return Str::of($left)->squish()->lower()->exactly(
            Str::of($right)->squish()->lower()->value(),
        );
    }
}
