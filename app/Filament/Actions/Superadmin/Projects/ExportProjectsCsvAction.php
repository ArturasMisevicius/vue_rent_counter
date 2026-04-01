<?php

declare(strict_types=1);

namespace App\Filament\Actions\Superadmin\Projects;

use App\Models\Project;
use Illuminate\Support\Collection;

final class ExportProjectsCsvAction
{
    public function handle(Collection $projects): string
    {
        $path = tempnam(sys_get_temp_dir(), 'projects-export-');

        if ($path === false) {
            abort(500, 'Unable to prepare the project export file.');
        }

        $handle = fopen($path, 'wb');

        if ($handle === false) {
            abort(500, 'Unable to write the project export file.');
        }

        $hydratedProjects = Project::query()
            ->forSuperadminIndex()
            ->whereKey($projects->modelKeys())
            ->get();

        fputcsv($handle, [
            'Project name',
            'Reference number',
            'Organization',
            'Building',
            'Property',
            'Status',
            'Priority',
            'Type',
            'Manager',
            'Budget amount',
            'Actual cost',
            'Budget variance',
            'Completion percentage',
            'Estimated end date',
            'Schedule variance days',
            'Created at',
        ]);

        foreach ($hydratedProjects as $project) {
            fputcsv($handle, [
                $project->name,
                $project->reference_number,
                $project->organization?->name,
                $project->building?->name,
                $project->property?->name,
                $project->status?->getLabel(),
                $project->priority?->getLabel(),
                $project->type?->getLabel(),
                $project->manager?->name ?? 'Unassigned',
                $this->decimal($project->budget_amount),
                $this->decimal($project->actual_cost),
                $this->decimal($project->budgetVarianceAmount()),
                (string) ((int) $project->completion_percentage),
                $project->estimated_end_date?->toDateString(),
                $project->scheduleVarianceDays(),
                $project->created_at?->toDateTimeString(),
            ]);
        }

        fclose($handle);

        return $path;
    }

    private function decimal(float|int|string|null $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return number_format((float) $value, 2, '.', '');
    }
}
