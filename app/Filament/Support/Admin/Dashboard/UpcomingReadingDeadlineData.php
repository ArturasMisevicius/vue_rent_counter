<?php

namespace App\Filament\Support\Admin\Dashboard;

use App\Models\Meter;
use App\Models\User;
use Carbon\CarbonInterface;

class UpcomingReadingDeadlineData
{
    /**
     * @return array<int, array{
     *     meter_name: string,
     *     property_name: string,
     *     due_label: string
     * }>
     */
    public function for(User $user, int $limit = 5): array
    {
        if ($user->organization_id === null) {
            return [];
        }

        return Meter::query()
            ->forOrganizationWorkspace($user->organization_id)
            ->active()
            ->get()
            ->map(function (Meter $meter): array {
                $baseDate = $meter->latestReading?->reading_date
                    ?? $meter->installed_at
                    ?? $meter->created_at;

                $dueDate = $baseDate->copy()->addDays(30);
                $unitNumber = $meter->property?->unit_number;
                $propertyName = (string) ($meter->property?->name ?? __('dashboard.not_available'));

                return [
                    'meter_name' => (string) $meter->name,
                    'property_name' => filled($unitNumber)
                        ? $propertyName.' · '.$unitNumber
                        : $propertyName,
                    'due_label' => $this->formatDueLabel($dueDate),
                    'due_sort' => $dueDate->timestamp,
                ];
            })
            ->filter(fn (array $deadline): bool => $deadline['due_sort'] <= now()->addDays(14)->timestamp)
            ->sortBy('due_sort')
            ->take($limit)
            ->map(fn (array $deadline): array => [
                'meter_name' => $deadline['meter_name'],
                'property_name' => $deadline['property_name'],
                'due_label' => $deadline['due_label'],
            ])
            ->values()
            ->all();
    }

    private function formatDueLabel(CarbonInterface $dueDate): string
    {
        $days = (int) now()->startOfDay()->diffInDays($dueDate->startOfDay(), false);

        if ($days < 0) {
            return __('dashboard.organization_deadlines.overdue_by_days', [
                'days' => abs($days),
            ]);
        }

        if ($days === 0) {
            return __('dashboard.organization_deadlines.due_today');
        }

        return __('dashboard.organization_deadlines.due_in_days', [
            'days' => $days,
        ]);
    }
}
