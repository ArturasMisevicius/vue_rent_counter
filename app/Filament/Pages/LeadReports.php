<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\ListingLeadStatus;
use App\Filament\Actions\Admin\Leads\ExportLeadsCsv;
use App\Filament\Support\Admin\OrganizationContext;
use App\Models\LeadSource;
use App\Models\ListingLead;
use App\Models\Organization;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class LeadReports extends Page
{
    protected static ?string $slug = 'leads/reports';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected string $view = 'filament.pages.lead-reports';

    public static function getNavigationGroup(): ?string
    {
        return __('app.nav_groups.leads');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.leads.reports.navigation');
    }

    public function getTitle(): string
    {
        return __('admin.leads.reports.title');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('viewAny', ListingLead::class) ?? false;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label(__('admin.leads.actions.export_csv'))
                ->authorize(fn (): bool => auth()->user()?->can('export', ListingLead::class) ?? false)
                ->action(function (ExportLeadsCsv $exportLeadsCsv) {
                    $csv = $exportLeadsCsv->handle($this->user(), $this->organization());

                    return response()->streamDownload(
                        fn (): int|false => print $csv,
                        'leads-export-'.now()->format('Y-m-d-His').'.csv',
                        ['Content-Type' => 'text/csv'],
                    );
                }),
        ];
    }

    protected function getViewData(): array
    {
        $leads = ListingLead::query()
            ->select([
                'id',
                'organization_id',
                'lead_source_id',
                'status',
                'assigned_to_user_id',
                'last_contacted_at',
                'converted_at',
                'next_follow_up_at',
            ])
            ->forOrganization((int) $this->organization()->id)
            ->when(
                $this->user()->isManager() && ! $this->user()->isAdmin(),
                fn (Builder $query): Builder => $query->assignedTo((int) $this->user()->id),
            )
            ->with('source:id,organization_id,name')
            ->limit(10000)
            ->get();

        $sourceNames = LeadSource::query()
            ->select(['id', 'organization_id', 'name'])
            ->forOrganization((int) $this->organization()->id)
            ->ordered()
            ->pluck('name', 'id');

        return [
            'byStatus' => collect(ListingLeadStatus::cases())
                ->map(fn (ListingLeadStatus $status): array => [
                    'label' => $status->label(),
                    'count' => $leads->where('status', $status)->count(),
                ])
                ->filter(fn (array $row): bool => $row['count'] > 0)
                ->values()
                ->all(),
            'bySource' => $sourceNames
                ->map(fn (string $sourceName, mixed $sourceId): array => [
                    'source' => $sourceName,
                    'imported' => $leads->where('lead_source_id', $sourceId)->count(),
                    'contacted' => $leads->where('lead_source_id', $sourceId)->whereNotNull('last_contacted_at')->count(),
                    'interested' => $leads->where('lead_source_id', $sourceId)->where('status', ListingLeadStatus::INTERESTED)->count(),
                    'converted' => $leads->where('lead_source_id', $sourceId)->where('status', ListingLeadStatus::CONVERTED)->count(),
                ])
                ->values()
                ->all(),
            'followUpsDue' => $leads
                ->filter(fn (ListingLead $lead): bool => $lead->next_follow_up_at !== null && $lead->next_follow_up_at->lte(now()))
                ->count(),
            'duplicates' => $leads->where('status', ListingLeadStatus::DUPLICATE)->count(),
            'doNotContact' => $leads->where('status', ListingLeadStatus::DO_NOT_CONTACT)->count(),
            'converted' => $leads->where('status', ListingLeadStatus::CONVERTED)->count(),
        ];
    }

    private function organization(): Organization
    {
        $organization = app(OrganizationContext::class)->currentOrganization();

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
