<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Actions\Admin\Invoices\GenerateBulkInvoicesAction;
use App\Filament\Support\Admin\Invoices\BulkInvoicePagePresenter;
use App\Filament\Support\Admin\Invoices\BulkInvoicePreviewBuilder;
use App\Http\Requests\Admin\Invoices\GenerateBulkInvoicesRequest;
use App\Models\Organization;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Validation\ValidationException;
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
     * @var array{valid?: array<int, array<string, mixed>>, skipped?: array<int, array<string, mixed>>}|null
     */
    public ?array $preview = null;

    /**
     * @var array<int, string>
     */
    public array $selectedAssignments = [];

    public string $tenantSearch = '';

    /**
     * @var array{
     *     created: int,
     *     failed: int,
     *     skipped: int,
     *     total: int,
     *     errors: array<int, string>,
     *     view_url: string|null
     * }|null
     */
    public ?array $summary = null;

    public function mount(): void
    {
        $this->form = [
            'billing_period_start' => now()->startOfMonth()->toDateString(),
            'billing_period_end' => now()->endOfMonth()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
        ];

        $this->refreshPreview();
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

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('generateInvoices')
                ->label(__('admin.invoices.bulk.actions.generate'))
                ->color('primary')
                ->action('generateInvoices')
                ->disabled(fn (): bool => $this->selectedAssignments === []),
            Action::make('cancel')
                ->label(__('admin.invoices.actions.cancel'))
                ->color('gray')
                ->url(route('filament.admin.resources.invoices.index')),
        ];
    }

    public function updated(string $name): void
    {
        if (! str_starts_with($name, 'form.')) {
            return;
        }

        $this->refreshPreview();
    }

    public function updatedSelectedAssignments(): void
    {
        $this->selectedAssignments = collect($this->selectedAssignments)
            ->filter(fn (mixed $value): bool => is_string($value) && $value !== '')
            ->unique()
            ->values()
            ->all();
    }

    public function toggleSelectAll(): void
    {
        $this->selectedAssignments = $this->allSelectableCandidatesSelected()
            ? []
            : $this->selectableAssignmentKeys();
    }

    public function generateInvoices(GenerateBulkInvoicesAction $generateBulkInvoicesAction): void
    {
        if ($this->selectedAssignments === []) {
            throw ValidationException::withMessages([
                'selected_assignments' => __('validation.required', [
                    'attribute' => __('requests.attributes.selected_assignments'),
                ]),
            ]);
        }

        $selectedAssignments = $this->selectedAssignments;
        $validated = $this->validatedFormAttributes([
            'selected_assignments' => $selectedAssignments,
        ]);
        $result = $generateBulkInvoicesAction->handle($this->organization(), $validated, $this->user());

        $this->summary = BulkInvoicePagePresenter::generationSummary($result, $selectedAssignments);

        $this->refreshPreview(resetSummary: false);
    }

    #[Computed]
    public function previewCandidates(): array
    {
        return BulkInvoicePagePresenter::candidates($this->preview, $this->tenantSearch);
    }

    /**
     * @return array{
     *     selected_count: int,
     *     estimated_total: string,
     *     missing_readings: array<int, array{tenant_name: string, property_name: string}>,
     *     already_billed: array<int, array{tenant_name: string, property_name: string}>
     * }
     */
    #[Computed]
    public function previewSummary(): array
    {
        return BulkInvoicePagePresenter::previewSummary($this->preview, $this->selectedAssignments);
    }

    #[Computed]
    public function allSelectableCandidatesSelected(): bool
    {
        $selectableKeys = $this->selectableAssignmentKeys();

        return $selectableKeys !== []
            && count($this->selectedAssignments) === count($selectableKeys);
    }

    private function refreshPreview(bool $resetSummary = true): void
    {
        $previousSelection = $this->selectedAssignments;

        $this->resetErrorBag();

        if ($resetSummary) {
            $this->summary = null;
        }

        try {
            $validated = $this->validatedFormAttributes();
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->getMessageBag());
            $this->preview = [
                'valid' => [],
                'skipped' => [],
            ];
            $this->selectedAssignments = [];

            return;
        }

        $this->preview = app(BulkInvoicePreviewBuilder::class)->handle($this->organization(), $validated);

        $selectableKeys = $this->selectableAssignmentKeys();
        $selected = collect($previousSelection)
            ->filter(fn (string $value): bool => in_array($value, $selectableKeys, true))
            ->values()
            ->all();

        $this->selectedAssignments = $selected !== []
            ? $selected
            : $selectableKeys;
    }

    /**
     * @return array<int, string>
     */
    private function selectableAssignmentKeys(): array
    {
        return collect($this->preview['valid'] ?? [])
            ->pluck('assignment_key')
            ->filter(fn (mixed $value): bool => is_string($value) && $value !== '')
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validatedFormAttributes(array $overrides = []): array
    {
        /** @var GenerateBulkInvoicesRequest $request */
        $request = new GenerateBulkInvoicesRequest;

        return $request->validatePayload([
            ...$this->form,
            ...$overrides,
        ], $this->user());
    }

    private function organization(): Organization
    {
        /** @var Organization $organization */
        $organization = $this->user()->organization;

        return $organization;
    }

    private function user(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }
}
