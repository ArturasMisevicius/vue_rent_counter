# Meter Reading Authorization Usage Guide

## Overview

This guide demonstrates how to use the `MeterReadingPolicy` in various contexts within the utilities billing platform. The policy implements a configurable workflow system that balances security with operational efficiency.

## Quick Start

### Basic Authorization Checks

```php
// Check if user can view meter readings
if (auth()->user()->can('viewAny', MeterReading::class)) {
    // Show meter readings list
}

// Check if user can create meter readings
if (auth()->user()->can('create', MeterReading::class)) {
    // Show create form
}

// Check if user can update a specific reading
if (auth()->user()->can('update', $meterReading)) {
    // Show edit form
}
```

### Workflow-Aware Checks

```php
// Check if tenant can modify their own reading
$canModify = auth()->user()->can('update', $meterReading);

if ($canModify && $meterReading->validation_status === ValidationStatus::PENDING) {
    // Tenant can edit pending readings in Permissive workflow
}
```

## Controller Integration

### REST API Controllers

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateMeterReadingRequest;
use App\Models\MeterReading;
use Illuminate\Http\JsonResponse;

class MeterReadingController extends Controller
{
    public function update(
        UpdateMeterReadingRequest $request, 
        MeterReading $meterReading
    ): JsonResponse {
        // Policy automatically enforces workflow rules
        $this->authorize('update', $meterReading);
        
        $meterReading->update($request->validated());
        
        return response()->json([
            'data' => $meterReading,
            'message' => 'Meter reading updated successfully',
        ]);
    }
    
    public function destroy(MeterReading $meterReading): JsonResponse
    {
        $this->authorize('delete', $meterReading);
        
        $meterReading->delete();
        
        return response()->json([
            'message' => 'Meter reading deleted successfully',
        ]);
    }
    
    public function approve(MeterReading $meterReading): JsonResponse
    {
        $this->authorize('approve', $meterReading);
        
        $meterReading->update([
            'validation_status' => ValidationStatus::VALIDATED,
            'validated_by' => auth()->id(),
            'validated_at' => now(),
        ]);
        
        return response()->json([
            'data' => $meterReading,
            'message' => 'Meter reading approved successfully',
        ]);
    }
}
```

### Manager Dashboard Controller

```php
<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\MeterReading;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MeterReadingController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', MeterReading::class);
        
        $readings = MeterReading::query()
            ->with(['meter', 'property', 'enteredBy'])
            ->when($request->status, fn($q) => $q->where('validation_status', $request->status))
            ->latest()
            ->paginate(25);
        
        return view('manager.meter-readings.index', compact('readings'));
    }
    
    public function edit(MeterReading $meterReading): View
    {
        $this->authorize('update', $meterReading);
        
        return view('manager.meter-readings.edit', compact('meterReading'));
    }
}
```

## Filament Resource Integration

### Resource Authorization

```php
<?php

namespace App\Filament\Resources;

use App\Models\MeterReading;
use Filament\Resources\Resource;

class MeterReadingResource extends Resource
{
    protected static ?string $model = MeterReading::class;
    
    // Automatic policy integration
    public static function canViewAny(): bool
    {
        return auth()->user()->can('viewAny', static::getModel());
    }
    
    public static function canCreate(): bool
    {
        return auth()->user()->can('create', static::getModel());
    }
    
    public static function canEdit(Model $record): bool
    {
        return auth()->user()->can('update', $record);
    }
    
    public static function canDelete(Model $record): bool
    {
        return auth()->user()->can('delete', $record);
    }
}
```

### Table Actions with Workflow Awareness

```php
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;

public static function table(Table $table): Table
{
    return $table
        ->actions([
            EditAction::make()
                ->visible(fn (MeterReading $record) => 
                    auth()->user()->can('update', $record)
                ),
            
            DeleteAction::make()
                ->visible(fn (MeterReading $record) => 
                    auth()->user()->can('delete', $record)
                ),
            
            Action::make('approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (MeterReading $record) => 
                    auth()->user()->can('approve', $record)
                )
                ->action(function (MeterReading $record) {
                    $record->update([
                        'validation_status' => ValidationStatus::VALIDATED,
                        'validated_by' => auth()->id(),
                        'validated_at' => now(),
                    ]);
                    
                    Notification::make()
                        ->title('Reading approved successfully')
                        ->success()
                        ->send();
                }),
            
            Action::make('reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (MeterReading $record) => 
                    auth()->user()->can('reject', $record)
                )
                ->requiresConfirmation()
                ->action(function (MeterReading $record) {
                    $record->update([
                        'validation_status' => ValidationStatus::REJECTED,
                        'validated_by' => auth()->id(),
                        'validated_at' => now(),
                    ]);
                    
                    Notification::make()
                        ->title('Reading rejected')
                        ->success()
                        ->send();
                }),
        ]);
}
```

### Form Customization Based on Permissions

```php
public static function form(Form $form): Form
{
    return $form
        ->schema([
            TextInput::make('value')
                ->numeric()
                ->required()
                ->disabled(fn (?MeterReading $record) => 
                    $record && !auth()->user()->can('update', $record)
                ),
            
            Select::make('validation_status')
                ->options(ValidationStatus::class)
                ->visible(fn () => 
                    auth()->user()->can('approve', $form->getRecord() ?? new MeterReading())
                ),
            
            Textarea::make('notes')
                ->disabled(fn (?MeterReading $record) => 
                    $record && !auth()->user()->can('update', $record)
                ),
        ]);
}
```

## Livewire Component Integration

### Meter Reading Form Component

```php
<?php

namespace App\Livewire\MeterReading;

use App\Models\MeterReading;
use Livewire\Component;

class EditForm extends Component
{
    public MeterReading $meterReading;
    public $value;
    public $notes;
    
    public function mount(MeterReading $meterReading)
    {
        $this->authorize('update', $meterReading);
        
        $this->meterReading = $meterReading;
        $this->value = $meterReading->value;
        $this->notes = $meterReading->notes;
    }
    
    public function save()
    {
        $this->authorize('update', $this->meterReading);
        
        $this->validate([
            'value' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);
        
        $this->meterReading->update([
            'value' => $this->value,
            'notes' => $this->notes,
        ]);
        
        session()->flash('message', 'Meter reading updated successfully');
    }
    
    public function render()
    {
        return view('livewire.meter-reading.edit-form', [
            'canEdit' => auth()->user()->can('update', $this->meterReading),
            'canDelete' => auth()->user()->can('delete', $this->meterReading),
        ]);
    }
}
```

## Blade Template Integration

### Conditional UI Elements

```blade
{{-- resources/views/meter-readings/show.blade.php --}}
<div class="meter-reading-card">
    <h3>Meter Reading #{{ $meterReading->id }}</h3>
    
    <div class="reading-details">
        <p><strong>Value:</strong> {{ $meterReading->value }}</p>
        <p><strong>Status:</strong> {{ $meterReading->validation_status->getLabel() }}</p>
        <p><strong>Entered By:</strong> {{ $meterReading->enteredBy->name }}</p>
    </div>
    
    @can('update', $meterReading)
        <div class="actions">
            <a href="{{ route('meter-readings.edit', $meterReading) }}" 
               class="btn btn-primary">
                Edit Reading
            </a>
        </div>
    @endcan
    
    @can('delete', $meterReading)
        <form method="POST" action="{{ route('meter-readings.destroy', $meterReading) }}" 
              class="inline">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger" 
                    onclick="return confirm('Are you sure?')">
                Delete Reading
            </button>
        </form>
    @endcan
    
    @can('approve', $meterReading)
        <div class="approval-actions">
            <form method="POST" action="{{ route('meter-readings.approve', $meterReading) }}" 
                  class="inline">
                @csrf
                <button type="submit" class="btn btn-success">
                    Approve Reading
                </button>
            </form>
            
            <form method="POST" action="{{ route('meter-readings.reject', $meterReading) }}" 
                  class="inline">
                @csrf
                <button type="submit" class="btn btn-warning" 
                        onclick="return confirm('Are you sure you want to reject this reading?')">
                    Reject Reading
                </button>
            </form>
        </div>
    @endcan
</div>
```

### Tenant Self-Service Interface

```blade
{{-- resources/views/tenant/meter-readings/index.blade.php --}}
<div class="tenant-readings">
    <h2>My Meter Readings</h2>
    
    @foreach($meterReadings as $reading)
        <div class="reading-item">
            <div class="reading-info">
                <span class="value">{{ $reading->value }}</span>
                <span class="status badge badge-{{ $reading->validation_status->getColor() }}">
                    {{ $reading->validation_status->getLabel() }}
                </span>
                <span class="date">{{ $reading->created_at->format('M j, Y') }}</span>
            </div>
            
            @if($reading->validation_status === \App\Enums\ValidationStatus::PENDING)
                <div class="pending-actions">
                    @can('update', $reading)
                        <a href="{{ route('tenant.meter-readings.edit', $reading) }}" 
                           class="btn btn-sm btn-primary">
                            Edit
                        </a>
                    @endcan
                    
                    @can('delete', $reading)
                        <form method="POST" 
                              action="{{ route('tenant.meter-readings.destroy', $reading) }}" 
                              class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" 
                                    onclick="return confirm('Are you sure?')">
                                Delete
                            </button>
                        </form>
                    @endcan
                </div>
            @endif
        </div>
    @endforeach
</div>
```

## API Response Examples

### Successful Authorization

```json
{
    "data": {
        "id": 123,
        "value": 1500.5,
        "validation_status": "pending",
        "entered_by": 456,
        "created_at": "2024-01-15T10:30:00Z",
        "updated_at": "2024-01-15T14:20:00Z"
    },
    "message": "Meter reading updated successfully"
}
```

### Authorization Failure

```json
{
    "message": "This action is unauthorized.",
    "errors": {
        "authorization": [
            "You can only edit your own pending meter readings."
        ]
    }
}
```

### Workflow-Specific Error

```json
{
    "message": "This action is unauthorized.",
    "errors": {
        "workflow": [
            "Cannot edit validated meter readings. Contact your manager for changes."
        ]
    }
}
```

## Common Patterns

### Bulk Operations with Authorization

```php
public function bulkApprove(Request $request)
{
    $readingIds = $request->validate([
        'reading_ids' => 'required|array',
        'reading_ids.*' => 'exists:meter_readings,id',
    ])['reading_ids'];
    
    $readings = MeterReading::whereIn('id', $readingIds)->get();
    
    $authorized = $readings->filter(function ($reading) {
        return auth()->user()->can('approve', $reading);
    });
    
    $authorized->each(function ($reading) {
        $reading->update([
            'validation_status' => ValidationStatus::VALIDATED,
            'validated_by' => auth()->id(),
            'validated_at' => now(),
        ]);
    });
    
    return response()->json([
        'approved' => $authorized->count(),
        'total' => $readings->count(),
        'message' => "Approved {$authorized->count()} of {$readings->count()} readings",
    ]);
}
```

### Conditional Navigation

```php
// In NavigationComposer or similar
public function compose(View $view)
{
    $user = auth()->user();
    
    $navigation = [
        'meter_readings' => [
            'label' => 'Meter Readings',
            'url' => route('meter-readings.index'),
            'can_access' => $user->can('viewAny', MeterReading::class),
            'badge' => $user->can('approve', MeterReading::class) 
                ? MeterReading::where('validation_status', ValidationStatus::PENDING)->count()
                : null,
        ],
    ];
    
    $view->with('navigation', $navigation);
}
```

## Troubleshooting

### Common Issues

1. **Policy Not Applied**: Ensure the policy is registered in `AuthServiceProvider`
2. **Workflow Not Working**: Check that the correct workflow strategy is injected
3. **Tenant Isolation Failing**: Verify `TenantBoundaryService` is properly configured
4. **Authorization Logging Missing**: Ensure audit logging is enabled in the policy

### Debug Authorization

```php
// Add to controller for debugging
public function debugAuthorization(MeterReading $meterReading)
{
    $user = auth()->user();
    
    return response()->json([
        'user_id' => $user->id,
        'user_role' => $user->role->value,
        'reading_id' => $meterReading->id,
        'reading_status' => $meterReading->validation_status->value,
        'entered_by' => $meterReading->entered_by,
        'can_view' => $user->can('view', $meterReading),
        'can_update' => $user->can('update', $meterReading),
        'can_delete' => $user->can('delete', $meterReading),
        'can_approve' => $user->can('approve', $meterReading),
        'workflow' => app(MeterReadingPolicy::class)->getWorkflowName(),
    ]);
}
```

## Related Documentation

- [MeterReadingPolicy API Reference](../api/policies/meter-reading-policy.md)
- [Workflow Strategies Guide](workflow-strategies.md)
- [Multi-Tenant Authorization](multi-tenant-authorization.md)
- [Filament Authorization Patterns](filament-authorization.md)