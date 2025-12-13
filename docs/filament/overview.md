# Filament v4.3+ Documentation

## Overview

CFlow uses Filament v4.3+ for the admin panel with dual-panel architecture (admin + user panels) and comprehensive RBAC using Filament Shield.

## Core Architecture Changes in v4

### Unified Schema System
- All components now use `Filament\Schemas\Schema`
- Form, Infolist, and Layout components live in the same namespace
- Mix and match Form and Infolist components in the same schema
- Page layouts now use schemas instead of Blade views

### Unified Actions
- All Actions extend from `Filament\Actions\Action`
- Single namespace: `use Filament\Actions\Action;`
- Create portable Actions reusable across Forms, Tables, Infolists

### Directory Structure
```
app/Filament/
├── Admin/                    # Admin panel
│   ├── Resources/
│   ├── Pages/
│   └── Widgets/
├── User/                     # User panel
│   ├── Resources/
│   ├── Pages/
│   └── Widgets/
├── Clusters/
│   └── Settings/
└── Support/                  # Shared components
    ├── Actions/
    ├── Filters/
    └── Widgets/
```

## Resource Organization

### JSON Field Handling (CRITICAL)
All `formatStateUsing()` closures that handle JSON fields MUST accept `mixed` type:

```php
TextColumn::make('segments')
    ->formatStateUsing(function (mixed $state): string {
        if (in_array($state, [null, '', []], true)) {
            return '—';
        }
        
        // Handle JSON string
        if (is_string($state)) {
            $decoded = json_decode($state, true);
            $state = is_array($decoded) ? $decoded : [$state];
        }
        
        // Handle array
        if (is_array($state)) {
            return implode(', ', $state);
        }
        
        return (string) $state;
    })
```

### Translations
- No inline strings - use PHP lang keys for all UI text
- Ensure enums expose translated labels (`getLabel()` -> `__()`)
- Add enum translation entries in `lang/*/enums.php`

### Schema Files (NEW in v4)
Extract form schemas to separate files:

```php
// app/Filament/Resources/Users/Schemas/UserForm.php
namespace App\Filament\Resources\Users\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\TextInput;
use Filament\Schemas\Components\Section;

final class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('app.sections.personal_information'))
                ->schema([
                    TextInput::make('name')
                        ->label(__('app.labels.name'))
                        ->required(),
                    TextInput::make('email')
                        ->label(__('app.labels.email'))
                        ->email()
                        ->required(),
                ]),
        ]);
    }
}
```

### Table Files (NEW in v4)
Extract table configurations:

```php
// app/Filament/Resources/Users/Tables/UsersTable.php
namespace App\Filament\Resources\Users\Tables;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

final class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('app.labels.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label(__('app.labels.email'))
                    ->searchable()
                    ->copyable(),
            ])
            ->filters([
                // filters
            ])
            ->actions([
                // actions
            ]);
    }
}
```

## Performance Best Practices

### Table Optimization
- Use `->searchable()` sparingly (only on needed columns)
- Implement custom queries for complex filters
- Use `->toggleable()` for optional columns
- Lazy load relationships with `->lazy()`

```php
public static function table(Table $table): Table
{
    return $table
        ->columns([
            TextColumn::make('name')
                ->searchable()
                ->sortable(),
            TextColumn::make('tenant.name')
                ->label(__('app.labels.tenant'))
                ->toggleable(),
        ])
        ->paginate([25, 50, 100])
        ->deferLoading();
}
```

### Large Dataset Handling
```php
// Use chunk loading for large tables
->paginate([25, 50, 100, 200])

// Defer loading of heavy columns
->deferLoading()

// Use simple pagination for better performance
->simplePagination()
```

### File Upload Handling
Always generate filenames using `Onym`:

```php
use Blaspsoft\Onym\Facades\Onym;

FileUpload::make('attachment')
    ->getUploadedFileNameForStorageUsing(
        fn ($file) => Onym::make(
            defaultFilename: '',
            extension: $file->getClientOriginalExtension(),
            strategy: 'uuid'
        )
    );
```

## Form Best Practices

### Field Organization
```php
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;

Schema::make()
    ->components([
        Tabs::make(__('app.tabs.details'))
            ->tabs([
                Tabs\Tab::make(__('app.tabs.basic_info'))
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->label(__('app.labels.name'))
                                ->required(),
                            TextInput::make('email')
                                ->label(__('app.labels.email'))
                                ->email(),
                        ]),
                    ]),
                Tabs\Tab::make(__('app.tabs.advanced'))
                    ->schema([
                        // Advanced fields
                    ]),
            ]),
    ]);
```

### Vertical Tabs (NEW in v4)
```php
Tabs::make(__('app.tabs.sections'))
    ->vertical() // NEW: vertical layout
    ->tabs([...]);
```

### Container Queries (NEW in v4)
```php
// Responsive layouts based on container size
Section::make()
    ->schema([...])
    ->containerQuery('min-width: 600px');
```

## Navigation & Clustering

### Using Clusters
```php
// app/Filament/Clusters/Settings.php
namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

final class Settings extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Settings';
    protected static ?int $navigationSort = 100;
    
    public static function getNavigationLabel(): string
    {
        return __('app.navigation.settings');
    }
}

// In Resource
protected static ?string $cluster = Settings::class;
```

### Global Search
```php
// In resource
protected static ?string $recordTitleAttribute = 'name';
protected static int $globalSearchResultsLimit = 20;

public static function getGlobalSearchResultDetails(Model $record): array
{
    return [
        __('app.labels.email') => $record->email,
        __('app.labels.phone') => $record->phone,
    ];
}
```

## Rich Editor (Tiptap)

### Basic Usage
```php
use Filament\Forms\Components\RichEditor;

RichEditor::make('content')
    ->label(__('app.labels.content'))
    ->toolbarButtons([
        'bold',
        'italic',
        'link',
        'bulletList',
        'orderedList',
    ]);
```

### JSON Storage (NEW in v4)
```php
RichEditor::make('content')
    ->json() // Store as JSON instead of HTML
    ->toolbarButtons([...]);
```

## Multi-Factor Authentication (NEW in v4)

### Enable MFA
```php
// In PanelProvider
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        ->mfa(); // Enable MFA
}
```

### Customize MFA
```php
->mfa(
    requireMfa: true, // Force all users to use MFA
    enforceMfa: fn () => auth()->user()->is_admin,
)
```

## Color System (Tailwind v4 + OKLCH)

### Theme Colors
```php
// In PanelProvider
->colors([
    'primary' => Color::Amber,
    'danger' => Color::Rose,
    'info' => Color::Blue,
    'success' => Color::Green,
    'warning' => Color::Orange,
])
```

## Authorization with Filament Shield

### Resource Authorization
```php
public static function canViewAny(): bool
{
    return auth()->user()->can('view_any::User');
}

public static function canCreate(): bool
{
    return auth()->user()->can('create::User');
}

public static function canEdit(Model $record): bool
{
    return auth()->user()->can('update::User');
}

public static function canDelete(Model $record): bool
{
    return auth()->user()->can('delete::User');
}
```

### Multi-Tenancy with Shield
```php
// Roles are automatically scoped to teams
$user->assignRole('admin', $team);

// Permissions respect current tenant
auth()->user()->can('view_any::Invoice'); // Scoped to current team
```

## Tenancy (Improved in v4)

### Automatic Scoping
- v4 automatically scopes ALL queries to current tenant
- Auto-associates new records with current tenant
- No manual scoping needed in most cases

```php
// In PanelProvider
use App\Models\Team;

->tenant(Team::class)
->tenantBillingProvider(new BillingProvider())
```

## Testing Patterns

### Resource Testing
```php
use function Pest\Livewire\livewire;

it('can list users', function () {
    $users = User::factory()->count(10)->create();
    
    livewire(ListUsers::class)
        ->assertCanSeeTableRecords($users);
});

it('can create user', function () {
    livewire(CreateUser::class)
        ->fillForm([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ])
        ->call('create')
        ->assertHasNoFormErrors();
    
    $this->assertDatabaseHas('users', [
        'name' => 'John Doe',
    ]);
});
```

### Table Testing
```php
it('can search users', function () {
    User::factory()->create(['name' => 'John Doe']);
    User::factory()->create(['name' => 'Jane Smith']);
    
    livewire(ListUsers::class)
        ->searchTable('John')
        ->assertCanSeeTableRecords(
            User::where('name', 'like', '%John%')->get()
        )
        ->assertCanNotSeeTableRecords(
            User::where('name', 'like', '%Jane%')->get()
        );
});
```

## Common Patterns

### Modal Forms
```php
use Filament\Actions\CreateAction;

protected function getHeaderActions(): array
{
    return [
        CreateAction::make()
            ->label(__('app.actions.create_user'))
            ->form([
                TextInput::make('name')
                    ->label(__('app.labels.name'))
                    ->required(),
            ]),
    ];
}
```

### Relation Managers
```php
php artisan make:filament-relation-manager UserResource invoices number

// In resource
public static function getRelations(): array
{
    return [
        RelationManagers\InvoicesRelationManager::class,
    ];
}
```

## Code Quality Rules

### DO:
- ✅ Extract schemas to separate files for complex forms
- ✅ Use clusters to organize related resources
- ✅ Implement proper authorization on all resources
- ✅ Write tests for all CRUD operations
- ✅ Use translation keys for all UI text
- ✅ Handle JSON fields with mixed type
- ✅ Use Onym for file uploads

### DON'T:
- ❌ Use hardcoded strings in UI
- ❌ Skip authorization checks
- ❌ Ignore performance optimization for large tables
- ❌ Mix v3 and v4 import patterns
- ❌ Forget to handle JSON field edge cases

## Migration from v3 to v4

### Run Upgrade Script
```bash
composer require filament/upgrade:"^4.0" -W --dev
vendor/bin/filament-v4
composer require filament/filament:"^4.0" -W
```

### Update Directory Structure
```bash
php artisan filament:upgrade-directory-structure-to-v4 --dry-run
php artisan filament:upgrade-directory-structure-to-v4
```

### Key Breaking Changes
- Form/Infolist components moved to Schema namespace
- Actions unified under single namespace
- Radio inline() behavior changed
- Tenancy now auto-scopes all queries
- Rich editor switched to Tiptap

## Related Documentation

- [Filament Shield Integration](./shield.md)
- [Filament Testing](./testing.md)
- [Filament Performance](./performance.md)
- [Multi-Tenancy Setup](./tenancy.md)