<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\Leads;

use App\Enums\AuditLogAction;
use App\Filament\Support\Admin\Leads\LeadCsvReader;
use App\Filament\Support\Admin\Leads\LeadDataNormalizer;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\LeadImportBatch;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ValidateLeadCsv
{
    private const MAX_ROWS = 5000;

    public function __construct(
        private readonly LeadCsvReader $reader,
        private readonly MapLeadCsvColumns $columnMapper,
        private readonly LeadDataNormalizer $normalizer,
        private readonly DetectLeadDuplicates $duplicateDetector,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, string|null>  $submittedMapping
     * @return array{
     *     filename: string,
     *     headers: list<string>,
     *     mapping: array<string, string>,
     *     rows_total: int,
     *     valid_rows: int,
     *     invalid_rows: int,
     *     possible_duplicates: int,
     *     missing_contact: int,
     *     errors: list<array{row: int, field: string, message: string}>,
     *     rows: list<array{row_number: int, data: array<string, mixed>, raw: array<string, string|null>, errors: list<array{field: string, message: string}>, duplicates: list<array<string, mixed>>, status: string}>,
     *     generated_at: string
     * }
     */
    public function handle(
        User $actor,
        Organization $organization,
        string|UploadedFile $file,
        array $submittedMapping = [],
    ): array {
        Gate::forUser($actor)->authorize('create', LeadImportBatch::class);

        $path = $file instanceof UploadedFile ? $file->getRealPath() : $file;
        $filename = $file instanceof UploadedFile ? $file->getClientOriginalName() : basename($file);

        if (! is_string($path) || ! is_file($path)) {
            throw ValidationException::withMessages([
                'file' => __('admin.leads.validation.file_missing'),
            ]);
        }

        $parsed = $this->reader->read($path);
        $mapping = $this->columnMapper->handle($parsed['headers'], $submittedMapping);

        $this->validateMapping($mapping);

        if (count($parsed['rows']) > self::MAX_ROWS) {
            throw ValidationException::withMessages([
                'file' => __('admin.leads.validation.row_limit', ['limit' => self::MAX_ROWS]),
            ]);
        }

        $rows = [];
        $errors = [];
        $validRows = 0;
        $invalidRows = 0;
        $possibleDuplicates = 0;
        $missingContact = 0;

        foreach ($parsed['rows'] as $index => $rawRow) {
            $rowNumber = $index + 2;
            $data = $this->mapRow($rawRow, $mapping);
            $rowErrors = $this->validateRow($data);
            $duplicates = $rowErrors === []
                ? $this->duplicateDetector->handle((int) $organization->id, $data)
                : [];

            if ($duplicates !== []) {
                $possibleDuplicates++;
            }

            if ($this->missingContact($data)) {
                $missingContact++;
            }

            foreach ($rowErrors as $rowError) {
                $errors[] = [
                    'row' => $rowNumber,
                    'field' => $rowError['field'],
                    'message' => $rowError['message'],
                ];
            }

            if ($rowErrors === []) {
                $validRows++;
            } else {
                $invalidRows++;
            }

            $rows[] = [
                'row_number' => $rowNumber,
                'data' => $data,
                'raw' => $rawRow,
                'errors' => $rowErrors,
                'duplicates' => $duplicates,
                'status' => $rowErrors === [] ? 'valid' : 'invalid',
            ];
        }

        $preview = [
            'filename' => $filename,
            'headers' => $parsed['headers'],
            'mapping' => $mapping,
            'rows_total' => count($parsed['rows']),
            'valid_rows' => $validRows,
            'invalid_rows' => $invalidRows,
            'possible_duplicates' => $possibleDuplicates,
            'missing_contact' => $missingContact,
            'errors' => $errors,
            'rows' => $rows,
            'generated_at' => now()->toIso8601String(),
        ];

        $this->auditLogger->record(
            AuditLogAction::CREATED,
            $organization,
            [
                'context' => [
                    'mutation' => 'lead.import_preview_generated',
                    'filename' => $filename,
                    'rows_total' => $preview['rows_total'],
                    'valid_rows' => $validRows,
                    'invalid_rows' => $invalidRows,
                    'possible_duplicates' => $possibleDuplicates,
                ],
            ],
            (int) $actor->id,
            'Lead import preview generated',
        );

        return $preview;
    }

    /**
     * @param  array<string, string>  $mapping
     */
    private function validateMapping(array $mapping): void
    {
        $messages = [];

        if (! isset($mapping['listing_title']) && ! isset($mapping['property_address'])) {
            $messages['mapping.listing'] = __('admin.leads.validation.required_listing_column');
        }

        if (! isset($mapping['source_url']) && ! isset($mapping['external_id'])) {
            $messages['mapping.source'] = __('admin.leads.validation.required_source_column');
        }

        if (! isset($mapping['owner_phone']) && ! isset($mapping['owner_email']) && ! isset($mapping['contact_raw'])) {
            $messages['mapping.contact'] = __('admin.leads.validation.required_contact_column');
        }

        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }
    }

    /**
     * @param  array<string, string|null>  $rawRow
     * @param  array<string, string>  $mapping
     * @return array<string, mixed>
     */
    private function mapRow(array $rawRow, array $mapping): array
    {
        $data = [];

        foreach ($mapping as $field => $header) {
            $data[$field] = $rawRow[$header] ?? null;
        }

        $data['_raw_price'] = $data['price'] ?? null;
        $data['_raw_area'] = $data['area'] ?? null;
        $data['normalized_phone'] = $this->normalizer->phone($data['owner_phone'] ?? null);
        $data['normalized_email'] = $this->normalizer->email($data['owner_email'] ?? null);
        $data['price'] = $this->normalizer->decimal($data['price'] ?? null);
        $data['area'] = $this->normalizer->decimal($data['area'] ?? null);
        $data['rooms'] = $this->normalizer->integer($data['rooms'] ?? null);
        $data['currency'] = $this->normalizer->currency($data['currency'] ?? null);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array{field: string, message: string}>
     */
    private function validateRow(array $data): array
    {
        $errors = [];

        if (! filled($data['listing_title'] ?? null) && ! filled($data['property_address'] ?? null)) {
            $errors[] = [
                'field' => 'listing_title',
                'message' => __('admin.leads.validation.row_missing_listing'),
            ];
        }

        if (! filled($data['source_url'] ?? null) && ! filled($data['external_id'] ?? null)) {
            $errors[] = [
                'field' => 'source_url',
                'message' => __('admin.leads.validation.row_missing_source'),
            ];
        }

        if ($this->missingContact($data)) {
            $errors[] = [
                'field' => 'contact',
                'message' => __('admin.leads.validation.row_missing_contact'),
            ];
        }

        if (filled($data['owner_email'] ?? null) && filter_var($data['owner_email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = [
                'field' => 'owner_email',
                'message' => __('admin.leads.validation.invalid_email'),
            ];
        }

        if (filled($data['owner_phone'] ?? null) && ! filled($data['normalized_phone'] ?? null)) {
            $errors[] = [
                'field' => 'owner_phone',
                'message' => __('admin.leads.validation.invalid_phone'),
            ];
        }

        if (filled($data['source_url'] ?? null) && filter_var($data['source_url'], FILTER_VALIDATE_URL) === false) {
            $errors[] = [
                'field' => 'source_url',
                'message' => __('admin.leads.validation.invalid_url'),
            ];
        }

        if (filled($data['_raw_price'] ?? null) && ! filled($data['price'] ?? null)) {
            $errors[] = [
                'field' => 'price',
                'message' => __('admin.leads.validation.invalid_price'),
            ];
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function missingContact(array $data): bool
    {
        return ! filled($data['normalized_phone'] ?? null)
            && ! filled($data['normalized_email'] ?? null)
            && ! filled($data['contact_raw'] ?? null);
    }
}
