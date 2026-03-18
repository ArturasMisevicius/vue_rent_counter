<?php

namespace App\Filament\Pages;

use App\Filament\Actions\Admin\Invoices\GenerateBulkInvoicesAction;
use App\Filament\Support\Admin\Invoices\BulkInvoicePreviewBuilder;
use App\Http\Requests\Admin\Invoices\GenerateBulkInvoicesRequest;
use App\Models\Organization;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;

class GenerateBulkInvoices extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'generate-bulk-invoices';

    protected string $view = 'filament.pages.generate-bulk-invoices';

    /**
     * @var array{billing_period_start: string, billing_period_end: string, due_date: string}
     */
    public array $form = [];

    /**
     * @var array<string, mixed>|null
     */
    public ?array $preview = null;

    /**
     * @var array<int, string>
     */
    public array $selectedAssignments = [];

    public int $step = 1;

    public string $tenantSearch = '';

    /**
     * @var array{created: int, failed: int, skipped: int, total: int}|null
     */
    public ?array $summary = null;

    public function mount(): void
    {
        $this->form = [
            'billing_period_start' => now()->startOfMonth()->toDateString(),
            'billing_period_end' => now()->endOfMonth()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
        ];
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->isAdmin() || $user?->isManager();
    }

    public function getTitle(): string
    {
        return __('admin.invoices.bulk.title');
    }

    public function updated(string $name): void
    {
        if (! str_starts_with($name, 'form.')) {
            return;
        }

        $this->preview = null;
        $this->selectedAssignments = [];
        $this->summary = null;
        $this->step = 1;
    }

    public function previewInvoices(BulkInvoicePreviewBuilder $bulkInvoicePreviewBuilder): void
    {
        /** @var Organization $organization */
        $organization = auth()->user()->organization;
        $validated = $this->validatedFormAttributes();

        $this->preview = $bulkInvoicePreviewBuilder->handle($organization, $validated);
        $this->selectedAssignments = collect($this->preview['valid'] ?? [])
            ->pluck('assignment_key')
            ->filter(fn (mixed $value): bool => is_string($value) && $value !== '')
            ->values()
            ->all();
        $this->summary = null;
        $this->step = 2;
    }

    public function generateInvoices(GenerateBulkInvoicesAction $generateBulkInvoicesAction): void
    {
        /** @var Organization $organization */
        $organization = auth()->user()->organization;
        $validated = $this->validatedFormAttributes([
            'selected_assignments' => $this->selectedAssignments,
        ]);
        $result = $generateBulkInvoicesAction->handle($organization, $validated, auth()->user());
        $totalSelected = count($this->selectedAssignments);

        $this->summary = [
            'created' => $result['created']->count(),
            'failed' => max($totalSelected - $result['created']->count(), 0),
            'skipped' => count($result['skipped']),
            'total' => $totalSelected,
        ];

        $this->preview = [
            'valid' => [],
            'skipped' => $result['skipped'],
        ];
    }

    #[Computed]
    public function previewCandidates(): array
    {
        $valid = collect($this->preview['valid'] ?? [])
            ->map(fn (array $candidate): array => [
                ...$candidate,
                'disabled' => false,
                'status' => 'available',
            ]);
        $skipped = collect($this->preview['skipped'] ?? [])
            ->map(fn (array $candidate): array => [
                ...$candidate,
                'disabled' => true,
                'status' => 'already_billed',
            ]);
        $search = mb_strtolower(trim($this->tenantSearch));

        return $valid
            ->merge($skipped)
            ->filter(function (array $candidate) use ($search): bool {
                if ($search === '') {
                    return true;
                }

                $haystack = mb_strtolower(trim(
                    ($candidate['tenant_name'] ?? '').' '.($candidate['property_name'] ?? ''),
                ));

                return str_contains($haystack, $search);
            })
            ->values()
            ->all();
    }

    private function validatedFormAttributes(array $overrides = []): array
    {
        /** @var GenerateBulkInvoicesRequest $request */
        $request = new GenerateBulkInvoicesRequest;

        return $request->validatePayload([
            ...$this->form,
            ...$overrides,
        ], auth()->user());
    }
}
