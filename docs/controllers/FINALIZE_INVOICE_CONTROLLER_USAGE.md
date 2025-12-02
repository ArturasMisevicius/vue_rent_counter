# FinalizeInvoiceController Usage Guide

## Overview

This guide provides practical examples for using the `FinalizeInvoiceController` in various contexts within the Vilnius Utilities Billing Platform.

**Controller**: `App\Http\Controllers\FinalizeInvoiceController`  
**Route**: `POST /manager/invoices/{invoice}/finalize`  
**Route Name**: `manager.invoices.finalize`

## Basic Usage

### From Blade Template

```blade
@can('finalize', $invoice)
    <form method="POST" action="{{ route('manager.invoices.finalize', $invoice) }}">
        @csrf
        <button type="submit" class="btn btn-primary">
            {{ __('invoices.actions.finalize') }}
        </button>
    </form>
@endcan
```

### With Confirmation Dialog

```blade
@can('finalize', $invoice)
    <form method="POST" 
          action="{{ route('manager.invoices.finalize', $invoice) }}"
          onsubmit="return confirm('{{ __('invoices.confirm.finalize') }}')">
        @csrf
        <button type="submit" class="btn btn-primary">
            {{ __('invoices.actions.finalize') }}
        </button>
    </form>
@endcan
```

### With Alpine.js

```blade
<div x-data="{ finalizing: false }">
    @can('finalize', $invoice)
        <form method="POST" 
              action="{{ route('manager.invoices.finalize', $invoice) }}"
              @submit="finalizing = true">
            @csrf
            <button type="submit" 
                    class="btn btn-primary"
                    :disabled="finalizing"
                    x-text="finalizing ? '{{ __('invoices.actions.finalizing') }}' : '{{ __('invoices.actions.finalize') }}'">
            </button>
        </form>
    @endcan
</div>
```

## Livewire Integration

### Livewire Component

```php
<?php

namespace App\Livewire\Manager;

use App\Models\Invoice;
use Livewire\Component;

class InvoiceActions extends Component
{
    public Invoice $invoice;

    public function finalize()
    {
        $this->authorize('finalize', $this->invoice);
        
        return redirect()->route('manager.invoices.finalize', $this->invoice);
    }

    public function render()
    {
        return view('livewire.manager.invoice-actions');
    }
}
```

### Livewire View

```blade
<div>
    @can('finalize', $invoice)
        <button wire:click="finalize" 
                class="btn btn-primary"
                wire:loading.attr="disabled">
            <span wire:loading.remove>{{ __('invoices.actions.finalize') }}</span>
            <span wire:loading>{{ __('invoices.actions.finalizing') }}</span>
        </button>
    @endcan
</div>
```

## Filament Integration

### Filament Resource Action

```php
use Filament\Tables\Actions\Action;

Action::make('finalize')
    ->label(__('invoices.actions.finalize'))
    ->icon('heroicon-o-lock-closed')
    ->color('success')
    ->requiresConfirmation()
    ->visible(fn (Invoice $record) => $record->isDraft())
    ->authorize('finalize')
    ->url(fn (Invoice $record) => route('manager.invoices.finalize', $record))
    ->openUrlInNewTab(false)
```

### Filament Bulk Action

```php
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;

BulkAction::make('finalize')
    ->label(__('invoices.actions.finalize_selected'))
    ->icon('heroicon-o-lock-closed')
    ->color('success')
    ->requiresConfirmation()
    ->action(function (Collection $records) {
        foreach ($records as $invoice) {
            if ($invoice->isDraft() && auth()->user()->can('finalize', $invoice)) {
                app(\App\Services\BillingService::class)->finalizeInvoice($invoice);
            }
        }
    })
```

## API Integration

### JavaScript Fetch

```javascript
async function finalizeInvoice(invoiceId) {
    const response = await fetch(`/manager/invoices/${invoiceId}/finalize`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    });

    if (response.redirected) {
        window.location.href = response.url;
    }
}
```

### Axios

```javascript
import axios from 'axios';

async function finalizeInvoice(invoiceId) {
    try {
        const response = await axios.post(`/manager/invoices/${invoiceId}/finalize`);
        window.location.href = response.request.responseURL;
    } catch (error) {
        console.error('Finalization failed:', error);
    }
}
```

## Error Handling

### Display Flash Messages

```blade
@if (session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div class="alert alert-error">
        {{ session('error') }}
    </div>
@endif
```

### With Alpine.js Toast

```blade
<div x-data="{ 
    show: false, 
    message: '', 
    type: 'success' 
}" 
     @flash-message.window="
        show = true; 
        message = $event.detail.message; 
        type = $event.detail.type;
        setTimeout(() => show = false, 3000)
     ">
    <div x-show="show" 
         x-transition
         :class="type === 'success' ? 'bg-green-500' : 'bg-red-500'"
         class="fixed top-4 right-4 p-4 rounded text-white">
        <span x-text="message"></span>
    </div>
</div>

@if (session('success'))
    <script>
        window.dispatchEvent(new CustomEvent('flash-message', {
            detail: { message: '{{ session('success') }}', type: 'success' }
        }));
    </script>
@endif

@if (session('error'))
    <script>
        window.dispatchEvent(new CustomEvent('flash-message', {
            detail: { message: '{{ session('error') }}', type: 'error' }
        }));
    </script>
@endif
```

## Authorization Examples

### Check Authorization Before Display

```blade
@can('finalize', $invoice)
    <!-- Show finalize button -->
@else
    <span class="text-gray-500">
        {{ __('invoices.messages.cannot_finalize') }}
    </span>
@endcan
```

### Check Multiple Conditions

```blade
@if($invoice->isDraft() && auth()->user()->can('finalize', $invoice))
    <!-- Show finalize button -->
@elseif($invoice->isFinalized())
    <span class="badge badge-success">
        {{ __('invoices.status.finalized') }}
    </span>
@else
    <span class="text-gray-500">
        {{ __('invoices.messages.cannot_finalize') }}
    </span>
@endif
```

## Testing Examples

### Feature Test

```php
public function test_manager_can_finalize_draft_invoice(): void
{
    $manager = User::factory()->create(['role' => UserRole::MANAGER]);
    $invoice = Invoice::factory()->create([
        'tenant_id' => $manager->tenant_id,
        'status' => InvoiceStatus::DRAFT,
    ]);

    $response = $this->actingAs($manager)
        ->post(route('manager.invoices.finalize', $invoice));

    $response->assertRedirect();
    $response->assertSessionHas('success');
    
    $invoice->refresh();
    $this->assertEquals(InvoiceStatus::FINALIZED, $invoice->status);
}
```

### Browser Test (Dusk)

```php
public function test_manager_can_finalize_invoice_via_browser(): void
{
    $manager = User::factory()->create(['role' => UserRole::MANAGER]);
    $invoice = Invoice::factory()->create([
        'tenant_id' => $manager->tenant_id,
        'status' => InvoiceStatus::DRAFT,
    ]);

    $this->browse(function (Browser $browser) use ($manager, $invoice) {
        $browser->loginAs($manager)
                ->visit(route('manager.invoices.show', $invoice))
                ->press(__('invoices.actions.finalize'))
                ->assertSee(__('notifications.invoice.finalized_locked'));
    });
}
```

## Common Patterns

### Invoice List with Finalize Action

```blade
<table class="table">
    <thead>
        <tr>
            <th>{{ __('invoices.fields.number') }}</th>
            <th>{{ __('invoices.fields.tenant') }}</th>
            <th>{{ __('invoices.fields.total') }}</th>
            <th>{{ __('invoices.fields.status') }}</th>
            <th>{{ __('common.actions') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($invoices as $invoice)
            <tr>
                <td>{{ $invoice->invoice_number }}</td>
                <td>{{ $invoice->tenant->name }}</td>
                <td>{{ $invoice->formatted_total }}</td>
                <td>
                    <span class="badge badge-{{ $invoice->status->color() }}">
                        {{ $invoice->status->label() }}
                    </span>
                </td>
                <td>
                    <a href="{{ route('manager.invoices.show', $invoice) }}" 
                       class="btn btn-sm btn-secondary">
                        {{ __('common.view') }}
                    </a>
                    
                    @can('finalize', $invoice)
                        <form method="POST" 
                              action="{{ route('manager.invoices.finalize', $invoice) }}"
                              class="inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-primary">
                                {{ __('invoices.actions.finalize') }}
                            </button>
                        </form>
                    @endcan
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
```

### Invoice Detail with Finalize Button

```blade
<div class="invoice-detail">
    <div class="invoice-header">
        <h1>{{ __('invoices.title.detail') }} #{{ $invoice->invoice_number }}</h1>
        
        <div class="invoice-actions">
            @can('finalize', $invoice)
                <form method="POST" 
                      action="{{ route('manager.invoices.finalize', $invoice) }}"
                      onsubmit="return confirm('{{ __('invoices.confirm.finalize') }}')">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        <i class="icon-lock"></i>
                        {{ __('invoices.actions.finalize') }}
                    </button>
                </form>
            @endcan
        </div>
    </div>
    
    <!-- Invoice details -->
</div>
```

## Translation Keys

### Required Translation Keys

```php
// lang/en/invoices.php
return [
    'actions' => [
        'finalize' => 'Finalize Invoice',
        'finalizing' => 'Finalizing...',
        'finalize_selected' => 'Finalize Selected',
    ],
    'confirm' => [
        'finalize' => 'Are you sure you want to finalize this invoice? This action cannot be undone.',
    ],
    'messages' => [
        'cannot_finalize' => 'This invoice cannot be finalized.',
    ],
    'errors' => [
        'already_finalized' => 'Invoice is already finalized.',
        'finalization_failed' => 'Invoice finalization failed. Please try again.',
    ],
];

// lang/en/notifications.php
return [
    'invoice' => [
        'finalized_locked' => 'Invoice finalized and locked successfully.',
    ],
];
```

## Related Documentation

- [FinalizeInvoiceController API Reference](../api/FINALIZE_INVOICE_CONTROLLER_API.md)
- [FinalizeInvoiceController Implementation](FINALIZE_INVOICE_CONTROLLER_REFACTORING_COMPLETE.md)
- [Invoice Controller Implementation](INVOICE_CONTROLLER_IMPLEMENTATION_COMPLETE.md)
- [BillingService API](../api/BILLING_SERVICE_API.md)
- [InvoicePolicy Documentation](../api/INVOICE_POLICY_API.md)
