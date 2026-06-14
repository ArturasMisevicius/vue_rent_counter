<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\LeadImportBatchStatus;
use App\Models\LeadImportBatch;
use App\Models\LeadSource;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeadImportBatch>
 */
class LeadImportBatchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'lead_source_id' => LeadSource::factory(),
            'filename' => 'aruodas.csv',
            'uploaded_by_user_id' => User::factory()->admin(),
            'rows_total' => 0,
            'rows_imported' => 0,
            'rows_skipped' => 0,
            'rows_duplicates' => 0,
            'rows_failed' => 0,
            'status' => LeadImportBatchStatus::PREVIEWED,
            'mapping_config' => [],
            'error_summary' => [],
            'finished_at' => null,
        ];
    }
}
