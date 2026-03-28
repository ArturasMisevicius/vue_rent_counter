<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Actions\Admin\Invoices\CreateInvoiceDraftAction;
use App\Filament\Actions\Admin\Invoices\FinalizeInvoiceAction;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Resources\Invoices\Schemas\CreateInvoiceForm;
use App\Filament\Support\Admin\Invoices\InvoiceDraftPreviewBuilder;
use App\Filament\Support\Admin\OrganizationContext;
use App\Http\Requests\Admin\Invoices\PreviewInvoiceDraftRequest;
use App\Models\Organization;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    protected static string|array $routeMiddleware = 'manager.permission:invoices,create';

    protected static bool $canCreateAnother = false;

    private string $submissionMode = 'draft';

    public function getTitle(): string
    {
        return __('admin.invoices.titles.new');
    }

    public function form(Schema $schema): Schema
    {
        return CreateInvoiceForm::configure($schema);
    }

    public function create(bool $another = false): void
    {
        $this->submissionMode = 'draft';

        parent::create($another);
    }

    public function generateAndFinalize(): void
    {
        $this->submissionMode = 'finalized';

        parent::create();
    }

    public function generateLineItems(): void
    {
        $organization = $this->resolvedOrganization($this->form->getRawState());
        /** @var PreviewInvoiceDraftRequest $request */
        $request = new PreviewInvoiceDraftRequest;
        $validated = $request->validatePayload($this->previewPayload($organization), Auth::user());
        $preview = app(InvoiceDraftPreviewBuilder::class)->handle($organization, $validated);
        $currentState = $this->form->getRawState();

        $this->form->fill([
            ...$currentState,
            'items' => $preview['items'],
            'adjustments' => $currentState['adjustments'] ?? [],
            'line_items_generated' => true,
        ]);

        if ($preview['items'] !== []) {
            return;
        }

        Notification::make()
            ->warning()
            ->title(__('admin.invoices.messages.no_generated_items'))
            ->body(__('admin.invoices.messages.no_generated_items_help'))
            ->send();
    }

    protected function handleRecordCreation(array $data): Model
    {
        $organization = $this->resolvedOrganization($data);
        $actor = Auth::user();

        $invoice = app(CreateInvoiceDraftAction::class)->handle($organization, $data, $actor instanceof User ? $actor : null);

        if ($this->submissionMode !== 'finalized') {
            return $invoice;
        }

        return app(FinalizeInvoiceAction::class)->handle($invoice);
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            $this->getFinalizeFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label(__('admin.invoices.actions.save_as_draft'));
    }

    protected function getFinalizeFormAction(): Action
    {
        return Action::make('generateAndFinalize')
            ->label(__('admin.invoices.actions.generate_and_finalize'))
            ->action('generateAndFinalize');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()
            ->label(__('admin.invoices.actions.cancel'));
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(
                $this->submissionMode === 'finalized'
                    ? __('admin.invoices.messages.finalized_named', ['number' => (string) $this->record?->invoice_number])
                    : __('admin.invoices.messages.draft_saved_named', ['number' => (string) $this->record?->invoice_number]),
            );
    }

    protected function getRedirectUrl(): string
    {
        return InvoiceResource::getUrl('view', [
            'record' => $this->record,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolvedOrganization(array $data): Organization
    {
        $organization = app(OrganizationContext::class)->currentOrganization();

        if ($organization instanceof Organization) {
            return $organization;
        }

        $user = Auth::user();

        if (! $user instanceof User) {
            abort(403);
        }

        abort_if(! $user->isSuperadmin(), 403);

        $organizationId = (int) ($data['organization_id'] ?? 0);

        return Organization::query()->findOrFail($organizationId);
    }

    /**
     * @param  array<string, mixed>  $rawState
     * @return array<string, mixed>
     */
    private function previewPayload(Organization $organization, ?array $rawState = null): array
    {
        $state = $rawState ?? $this->form->getRawState();

        return [
            ...$state,
            'organization_id' => $state['organization_id'] ?? $organization->id,
        ];
    }
}
