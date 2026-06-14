<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Support\Admin\BillingIntegrity\BillingIntegrityIssue;
use App\Filament\Support\Admin\BillingIntegrity\DetectBillingDuplicates;
use App\Filament\Support\Admin\BillingIntegrity\DetectBillingOrphans;
use App\Filament\Support\Admin\BillingReview\BillingReviewAccess;
use App\Models\Organization;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

class BillingCleanupCenter extends Page
{
    protected static ?string $slug = 'billing-cleanup-center';

    protected static ?string $navigationLabel = null;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected string $view = 'filament.pages.billing-cleanup-center';

    #[Url(as: 'attention')]
    public ?string $attention = null;

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
    }

    public static function getNavigationGroup(): ?string
    {
        return __('shell.navigation.groups.billing');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.billing_cleanup.navigation');
    }

    public static function canAccess(): bool
    {
        return app(BillingReviewAccess::class)->canAccess(auth()->user());
    }

    public function getTitle(): string
    {
        return __('admin.billing_cleanup.title');
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('billingReview')
                ->label(__('admin.billing_review.title'))
                ->color('gray')
                ->url(route('filament.admin.pages.billing-review-center')),
            Action::make('invoices')
                ->label(__('admin.invoices.plural'))
                ->color('gray')
                ->url(route('filament.admin.resources.invoices.index')),
        ];
    }

    /**
     * @return array{
     *     duplicates: array<int, array<string, mixed>>,
     *     orphans: array<int, array<string, mixed>>,
     *     summary: array{blocking: int, warning: int, total: int}
     * }
     */
    #[Computed]
    public function integrity(): array
    {
        $organizationId = $this->organization()->id;
        $attention = $this->selectedProblemTypes();
        $duplicates = $this->filterIssues(app(DetectBillingDuplicates::class)->forOrganization($organizationId), $attention);
        $orphans = $this->filterIssues(app(DetectBillingOrphans::class)->forOrganization($organizationId), $attention);
        $issues = $duplicates->merge($orphans);

        return [
            'duplicates' => $duplicates
                ->map(fn (BillingIntegrityIssue $issue): array => $this->issueRow($issue))
                ->values()
                ->all(),
            'orphans' => $orphans
                ->map(fn (BillingIntegrityIssue $issue): array => $this->issueRow($issue))
                ->values()
                ->all(),
            'summary' => [
                'blocking' => $issues->filter(fn (BillingIntegrityIssue $issue): bool => $issue->severity === 'blocking')->count(),
                'warning' => $issues->filter(fn (BillingIntegrityIssue $issue): bool => $issue->severity === 'warning')->count(),
                'total' => $issues->count(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function issueRow(BillingIntegrityIssue $issue): array
    {
        return [
            ...$issue->toArray(),
            'label' => $issue->label(),
            'recommendation_label' => $issue->recommendationLabel(),
            'severity_label' => $issue->severityLabel(),
        ];
    }

    /**
     * @param  Collection<int, BillingIntegrityIssue>  $issues
     * @param  list<string>|null  $problemTypes
     * @return Collection<int, BillingIntegrityIssue>
     */
    private function filterIssues(Collection $issues, ?array $problemTypes): Collection
    {
        if ($problemTypes === null) {
            return $issues;
        }

        return $issues
            ->filter(fn (BillingIntegrityIssue $issue): bool => in_array($issue->problemType, $problemTypes, true))
            ->values();
    }

    /**
     * @return list<string>|null
     */
    private function selectedProblemTypes(): ?array
    {
        $attention = trim((string) $this->attention);

        if ($attention === '') {
            return null;
        }

        return match ($attention) {
            'duplicate_readings' => ['duplicate_active_readings'],
            'orphan_documents' => ['documents_without_attachable'],
            'duplicate_financial_data', 'financial_duplicates' => [
                'duplicate_invoices',
                'duplicate_invoice_items',
                'charges_included_twice',
            ],
            default => [$attention],
        };
    }

    private function organization(): Organization
    {
        $organization = app(BillingReviewAccess::class)->organizationFor($this->user());

        abort_unless($organization instanceof Organization, 403);

        return $organization;
    }

    private function user(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }
}
