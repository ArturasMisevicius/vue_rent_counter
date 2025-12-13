---
inclusion: always
---

### **1. filament-v4-conventions.md**

```markdown
---
inclusion_mode: "conditional"
file_patterns:
  - "app/Filament/**/*"
  - "app/Models/**/*.php"
---

# Filament v4.3+ Conventions & Best Practices

## Core Architecture Changes in v4

### Unified Schema System
- **All components now use `Filament\Schemas\Schema`**
- Override `infolist()` methods with `Filament\Schemas\Schema` (the old `Filament\Infolists\Infolist` class is removed and will fatal).
- Form, Infolist, and Layout components live in the same namespace
- Mix and match Form and Infolist components in the same schema
- Page layouts now use schemas instead of Blade views
- Do not import `Filament\Infolists\Infolist` in page classes; use `Schema $schema` and schema components (e.g., `Filament\Schemas\Components\Section`) instead.

### Unified Actions
- All Actions extend from `Filament\Actions\Action`
- No more importing wrong Action classes
- Create portable Actions reusable across Forms, Tables, Infolists
- Single namespace: `use Filament\Actions\Action;`

### Directory Structure (v4 Default)
```
app/Filament/
├── Clusters/
│   └── Settings/
│       ├── Settings.php
│       ├── Pages/
│       └── Resources/
├── Resources/
│   └── CustomerResource/
│       ├── CustomerResource.php
│       ├── Pages/
│       │   ├── CreateCustomer.php
│       │   ├── EditCustomer.php
│       │   └── ListCustomers.php
│       ├── Schemas/
│       │   └── CustomerForm.php
│       └── Tables/
│           └── CustomersTable.php
└── Pages/
    └── Dashboard.php
```

## Resource Organization

### JSON Field Handling (CRITICAL)
**All `formatStateUsing()` closures that handle JSON fields MUST accept `mixed` type, not `?array`.**

JSON fields stored in the database may be returned as either strings or arrays depending on the model's cast configuration. Always handle both cases to prevent type errors.

**Standard Pattern:**
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

Prefer `App\Support\Helpers\ArrayHelper::joinList()` for these cases so JSON strings/arrays/collections share one normalization path (trimming blanks, optional placeholders, optional final separator) instead of repeating `implode()` logic in each resource.

**Why This Matters:**
- Model casts may not always be applied (e.g., in exports, raw queries)
- Database may store JSON as string even with array cast
- Prevents "Argument #1 ($state) must be of type ?array, string given" errors

**Apply to:**
- Table columns (`TextColumn::make()->formatStateUsing()`)
- Infolist entries (`TextEntry::make()->formatStateUsing()`)
- Export columns (`ExportColumn::make()->formatStateUsing()`)

### Translations
- No inline strings. Use PHP lang keys for all labels/headings/descriptions/empty states/actions/navigation (e.g., `__('app.labels.name')`, `__('app.actions.save')`, `__('app.navigation.workspace')`).
- Ensure enums expose translated labels (`getLabel()` -> `__()`), so tables/forms inherit translations.
- Add enum translation entries (e.g., `account_type`, `lead_status`, `invoice_status`, `lead_grade`, `lead_nurture_status`) in `lang/*/enums.php` when introducing new enums so human-readable labels resolve during tests.
- Add new strings to `resources/lang/{locale}/*.php`; keep structure mirrored across locales.
- For enums implementing `HasLabel`/`HasColor`, either call `getLabel()`/`getColor()` or provide `label()`/`color()` wrappers on the enum itself (e.g., `NoteCategory`, `LeadStatus`, `LeadGrade`, `LeadNurtureStatus`, `AccountTeamAccessLevel`) so Blade/table callbacks don’t call missing methods.

### Schema Files (NEW in v4)
- Extract form schemas to `Schemas/[Model]Form.php`
- Extract table configurations to `Tables/[Model]Table.php`
- Keeps resource classes clean and maintainable

```php
// In CustomerResource.php
use App\Filament\Resources\Customers\Schemas\CustomerForm;
use Filament\Schemas\Schema;

public static function form(Schema $schema): Schema
{
    return CustomerForm::configure($schema);
}
```

### Table Files (NEW in v4)
```php
// In Tables/CustomersTable.php
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

public static function configure(Table $table): Table
{
    return $table
        ->columns([
            TextColumn::make('name'),
            TextColumn::make('email'),
        ])
        ->filters([
            // filters
        ])
        ->actions([
            // actions
        ]);
}
```

## Performance Best Practices

### Table Optimization
- v4 tables render 2-3x faster than v3
- Use `->searchable()` sparingly (only on needed columns)
- Implement custom queries for complex filters
- Use `->toggleable()` for optional columns
- Lazy load relationships with `->lazy()`

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
- **Always** generate filenames using `Onym` to prevent collisions and sanitize inputs.
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
        Tabs::make('Details')
            ->tabs([
                Tabs\Tab::make('Basic Info')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')->required(),
                            TextInput::make('email')->email(),
                        ]),
                    ]),
                Tabs\Tab::make('Advanced')
                    ->schema([
                        // Advanced fields
                    ]),
            ]),
    ]);
```

### Vertical Tabs (NEW in v4)
```php
Tabs::make('Sections')
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
// Settings.php cluster
namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class Settings extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Settings';
    protected static ?int $navigationSort = 100;
}

// In Resource
protected static ?string $cluster = Settings::class;
```

### Nested Resources (NEW in v4)
```php
// Access nested resources in context of parent
// Example: /courses/1/lessons
use Filament\Resources\Resource;

class LessonResource extends Resource
{
    public static function getParentResource(): ?string
    {
        return CourseResource::class;
    }
    
    public static function getRelationship(): string
    {
        return 'lessons';
    }
}
```

## Rich Editor (Tiptap)

### Basic Usage
```php
use Filament\Forms\Components\RichEditor;

RichEditor::make('content')
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

### Custom Blocks (NEW in v4)
```php
RichEditor::make('content')
    ->blocks([
        'call-to-action',
        'testimonial',
        'code-block',
    ]);
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
- v4 uses OKLCH color space (P3 gamut)
- More vivid, accurate colors
- Better accessibility with auto-calculated contrast

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

### Custom Colors
```php
use Filament\Support\Colors\Color;

->colors([
    'primary' => Color::hex('#ff6b35'),
])
```

## Testing Patterns

### Resource Testing
```php
use function Pest\Livewire\livewire;

it('can list customers', function () {
    $customers = Customer::factory()->count(10)->create();
    
    livewire(ListCustomers::class)
        ->assertCanSeeTableRecords($customers);
});

it('can create customer', function () {
    livewire(CreateCustomer::class)
        ->fillForm([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ])
        ->call('create')
        ->assertHasNoFormErrors();
    
    $this->assertDatabaseHas('customers', [
        'name' => 'John Doe',
    ]);
});
```

### Table Testing
```php
it('can search customers', function () {
    Customer::factory()->create(['name' => 'John Doe']);
    Customer::factory()->create(['name' => 'Jane Smith']);
    
    livewire(ListCustomers::class)
        ->searchTable('John')
        ->assertCanSeeTableRecords(
            Customer::where('name', 'like', '%John%')->get()
        )
        ->assertCanNotSeeTableRecords(
            Customer::where('name', 'like', '%Jane%')->get()
        );
});
```

## Authorization

### Resource Authorization
```php
public static function canViewAny(): bool
{
    return auth()->user()->can('view_customers');
}

public static function canCreate(): bool
{
    return auth()->user()->can('create_customers');
}

public static function canEdit(Model $record): bool
{
    return auth()->user()->can('edit_customers');
}

public static function canDelete(Model $record): bool
{
    return auth()->user()->can('delete_customers');
}
```

### Page Authorization
```php
// In resource page
public function mount(): void
{
    abort_unless(auth()->user()->can('manage_settings'), 403);
}
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

### Tenant-Specific Resources
```php
// Automatically scoped by v4
public static function getEloquentQuery(): Builder
{
    // No need for manual tenant scoping in v4!
    return parent::getEloquentQuery();
}
```

## Code Quality Rules

### DO:
- ✅ Extract schemas to separate files for complex forms
- ✅ Use clusters to organize related resources
- ✅ Implement proper authorization on all resources
- ✅ Write tests for all CRUD operations
- ✅ Use the new unified Action namespace
- ✅ Leverage schema system for page layouts
- ✅ Use container queries for responsive designs
- ✅ Store rich content as JSON when appropriate

### DON'T:
- ❌ Mix v3 and v4 import patterns
- ❌ Publish and edit Filament core Blade views
- ❌ Skip authorization checks
- ❌ Ignore performance optimization for large tables
- ❌ Use old directory structure for new projects
- ❌ Hardcode tenant scoping (v4 handles it)

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

## Common Patterns

### Modal Forms
```php
use Filament\Actions\CreateAction;

protected function getHeaderActions(): array
{
    return [
        CreateAction::make()
            ->form([
                TextInput::make('name')->required(),
            ]),
    ];
}
```

### Relation Managers
```php
php artisan make:filament-relation-manager CustomerResource orders number

// In resource
public static function getRelations(): array
{
    return [
        RelationManagers\OrdersRelationManager::class,
    ];
}
```

### Global Search
```php
// In resource
protected static ?string $recordTitleAttribute = 'name';

protected static int $globalSearchResultsLimit = 20;

public static function getGlobalSearchResultDetails(Model $record): array
{
    return [
        'Email' => $record->email,
        'Phone' => $record->phone,
    ];
}
```

## Remember
- v4 is designed to be the "ultimate implementation"
- Focus on performance, especially for large tables
- Leverage new schema system for consistent layouts
- Use unified Actions for portable code
- Let v4's automatic tenancy scoping handle multi-tenancy
- Test everything with Pest/PHPUnit
```

---

### **2. filament-v4-schema-patterns.md**

```markdown
---
inclusion_mode: "conditional"
file_patterns:
  - "app/Filament/**/Schemas/**"
  - "app/Filament/**/Tables/**"
---

# Filament v4.3+ Schema Patterns & Layout Best Practices

## Schema Architecture

### What is a Schema?
A Schema is Filament v4.3+'s unified approach to building UI components. It replaces separate Form/Infolist/Layout systems with one consistent pattern.

```php
use Filament\Schemas\Schema;
use Filament\Schemas\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;

Schema::make()
    ->components([
        Section::make('Customer Details')
            ->schema([
                Grid::make(2)->schema([
                    TextInput::make('first_name'),
                    TextInput::make('last_name'),
                ]),
            ]),
    ]);
```

## Layout Components

### Section
```php
use Filament\Schemas\Components\Section;

Section::make('Personal Information')
    ->description('Basic customer details')
    ->schema([
        // components
    ])
    ->collapsible()
    ->collapsed()
    ->icon('heroicon-o-user')
    ->aside(); // Place description beside content
```

### Grid
```php
use Filament\Schemas\Components\Grid;

// Responsive grid
Grid::make([
    'default' => 1,
    'sm' => 2,
    'lg' => 3,
])
->schema([
    TextInput::make('field1'),
    TextInput::make('field2'),
    TextInput::make('field3'),
]);

// Column spans
Grid::make(3)->schema([
    TextInput::make('full')
        ->columnSpan(3), // Full width
    TextInput::make('half')
        ->columnSpan(2), // 2/3 width
    TextInput::make('third')
        ->columnSpan(1), // 1/3 width
]);
```

### Tabs
```php
use Filament\Schemas\Components\Tabs;

// Horizontal tabs (default)
Tabs::make('Profile')
    ->tabs([
        Tabs\Tab::make('Personal')
            ->icon('heroicon-o-user')
            ->badge('2')
            ->schema([
                TextInput::make('name'),
                TextInput::make('email'),
            ]),
        Tabs\Tab::make('Address')
            ->schema([
                TextInput::make('street'),
                TextInput::make('city'),
            ]),
    ]);

// Vertical tabs (NEW in v4)
Tabs::make('Settings')
    ->vertical()
    ->tabs([...]);

// Contained tabs
Tabs::make('Data')
    ->contained()
    ->tabs([...]);
```

### Fieldset
```php
use Filament\Schemas\Components\Fieldset;

Fieldset::make('Contact')
    ->schema([
        TextInput::make('email'),
        TextInput::make('phone'),
    ]);
```

### Split
```php
use Filament\Schemas\Components\Split;

Split::make([
    Section::make('Main Content')
        ->grow(true)
        ->schema([...]),
    Section::make('Sidebar')
        ->grow(false)
        ->schema([...]),
]);
```

### Repeater
```php
use Filament\Forms\Components\Repeater;

Repeater::make('line_items')
    ->schema([
        TextInput::make('product'),
        TextInput::make('quantity')->numeric(),
        TextInput::make('price')->numeric(),
    ])
    ->columns(3)
    ->collapsible()
    ->collapsed()
    ->cloneable()
    ->reorderable()
    ->deleteAction(fn (Action $action) => 
        $action->requiresConfirmation()
    );
```

## Container Queries (NEW in v4)

### Responsive Based on Container Size
```php
use Filament\Schemas\Components\Section;

Section::make('Responsive Section')
    ->schema([
        Grid::make()
            ->columns(1)
            ->schema([
                TextInput::make('field1'),
                TextInput::make('field2'),
            ]),
    ])
    // Responsive columns based on section width
    ->containerQuery([
        'min-width: 400px' => 'columns-2',
        'min-width: 600px' => 'columns-3',
    ]);
```

## Mixing Form and Infolist Components

### Editable + Read-Only in Same Schema
```php
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\TextEntry;

Schema::make()
    ->components([
        Section::make('Editable')
            ->schema([
                TextInput::make('name'), // Editable
                TextInput::make('email'), // Editable
            ]),
        Section::make('Read-Only')
            ->schema([
                TextEntry::make('created_at'), // Display only
                TextEntry::make('updated_at'), // Display only
            ]),
    ]);
```

## Custom Page Layouts (NEW in v4)

### Override Page Structure with Schema
```php
// In resource page
use Filament\Schemas\Components\Section;
use Filament\Resources\Pages\EditRecord;

class EditCustomer extends EditRecord
{
    public function content(): Schema
    {
        return Schema::make()
            ->components([
                Section::make('Main Form')
                    ->schema($this->form()),
                
                Section::make('Relations')
                    ->schema([
                        // Manually place relation managers
                        $this->getRelationManager('orders'),
                    ]),
                
                Section::make('Activity Log')
                    ->schema([
                        // Custom content
                    ]),
            ]);
    }
}
```

## Advanced Schema Patterns

### Wizard
```php
use Filament\Schemas\Components\Wizard;

Wizard::make([
    Wizard\Step::make('Customer Info')
        ->schema([
            TextInput::make('name'),
            TextInput::make('email'),
        ]),
    Wizard\Step::make('Address')
        ->schema([
            TextInput::make('street'),
            TextInput::make('city'),
        ]),
    Wizard\Step::make('Review')
        ->schema([
            TextEntry::make('name'),
            TextEntry::make('email'),
        ]),
])
->submitAction(new HtmlString('
    <button type="submit">
        Create Customer
    </button>
'));
```

### Card-Based Layout
```php
use Filament\Schemas\Components\Section;

Section::make()
    ->schema([
        Grid::make(3)->schema([
            Section::make('Card 1')
                ->schema([...])
                ->columnSpan(1),
            Section::make('Card 2')
                ->schema([...])
                ->columnSpan(1),
            Section::make('Card 3')
                ->schema([...])
                ->columnSpan(1),
        ]),
    ]);
```

### Conditional Schemas
```php
Schema::make()
    ->components([
        Select::make('type')
            ->options([
                'individual' => 'Individual',
                'company' => 'Company',
            ])
            ->live(),
        
        // Show based on selection
        TextInput::make('person_name')
            ->visible(fn (Get $get) => $get('type') === 'individual'),
        
        TextInput::make('company_name')
            ->visible(fn (Get $get) => $get('type') === 'company'),
    ]);
```

## Schema Organization Patterns

### Extract to Schema Classes
```php
// app/Filament/Resources/Customers/Schemas/CustomerForm.php
namespace App\Filament\Resources\Customers\Schemas;

use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            self::personalSection(),
            self::addressSection(),
            self::preferencesSection(),
        ]);
    }
    
    protected static function personalSection(): Section
    {
        return Section::make('Personal Information')
            ->schema([
                TextInput::make('name')->required(),
                TextInput::make('email')->email()->required(),
            ]);
    }
    
    protected static function addressSection(): Section
    {
        return Section::make('Address')
            ->schema([
                TextInput::make('street'),
                TextInput::make('city'),
            ]);
    }
    
    protected static function preferencesSection(): Section
    {
        return Section::make('Preferences')
            ->schema([
                Toggle::make('newsletter'),
                Toggle::make('notifications'),
            ]);
    }
}
```

## Table Schema Patterns

### Extract Table Configuration
```php
// app/Filament/Resources/Customers/Tables/CustomersTable.php
namespace App\Filament\Resources\Customers\Tables;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns(self::columns())
            ->filters(self::filters())
            ->actions(self::actions())
            ->bulkActions(self::bulkActions())
            ->defaultSort('created_at', 'desc');
    }
    
    protected static function columns(): array
    {
        return [
            TextColumn::make('name')
                ->searchable()
                ->sortable(),
            TextColumn::make('email')
                ->searchable()
                ->copyable(),
            TextColumn::make('created_at')
                ->dateTime()
                ->sortable()
                ->toggleable(),
        ];
    }
    
    protected static function filters(): array
    {
        return [
            SelectFilter::make('status')
                ->options([
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                ]),
        ];
    }
    
    // ... actions and bulkActions methods
}
```

## Best Practices

### DO:
- ✅ Extract complex schemas to separate classes
- ✅ Use container queries for true responsive design
- ✅ Organize forms with Sections and Grids
- ✅ Use Tabs for complex multi-section forms
- ✅ Mix Form and Infolist components when appropriate
- ✅ Leverage v4's page schema for custom layouts
- ✅ Keep schema components focused and reusable

### DON'T:
- ❌ Create overly deep nesting (max 3-4 levels)
- ❌ Mix too many different layout types in one schema
- ❌ Ignore accessibility (labels, descriptions, etc.)
- ❌ Forget to make schemas responsive
- ❌ Hardcode values that should be dynamic

## Performance Tips

1. **Lazy Load Heavy Components**
```php
Section::make('Heavy Content')
    ->defer Loading()
    ->schema([...]);
```

2. **Use Cached Queries**
```php
Select::make('category_id')
    ->options(fn () => cache()->remember(
        'categories',
        3600,
        fn () => Category::pluck('name', 'id')
    ));
```

3. **Optimize Repeaters**
```php
Repeater::make('items')
    ->simple() // Simplified UI for better performance
    ->schema([...]);
```
```

---

### **3. filament-v4-actions.md**

```markdown
---
inclusion_mode: "conditional"
file_patterns:
  - "app/Filament/**/*Action*.php"
  - "app/Filament/**/Pages/**"
  - "app/Filament/**/Resources/**"
---

# Filament v4.3+ Unified Actions System

## The v4 Action Revolution

### What Changed?
In v3, there were multiple Action classes in different namespaces (Forms, Tables, Pages, etc.). In v4, **almost all Actions use the same base class**: `Filament\Actions\Action`.

### Benefits
- ✅ Never import the wrong Action class
- ✅ Create portable Actions usable anywhere
- ✅ Consistent API across all contexts
- ✅ Easier to extend and customize

## Basic Action Usage

### Single Namespace
```php
// v4: ONE import for all contexts
use Filament\Actions\Action;

// Works in Forms, Tables, Pages, Infolists, etc.
Action::make('approve')
    ->label('Approve')
    ->icon('heroicon-o-check')
    ->action(fn () => /* logic */);
```

### Action Contexts

#### Table Actions
```php
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;

public static function table(Table $table): Table
{
    return $table
        ->actions([
            EditAction::make(),
            DeleteAction::make(),
            Action::make('activate')
                ->action(fn ($record) => $record->activate())
                ->requiresConfirmation()
                ->color('success'),
        ]);
}
```

#### Form Actions
```php
use Filament\Forms\Components\Actions\Action;

TextInput::make('email')
    ->suffixAction(
        Action::make('sendVerification')
            ->icon('heroicon-o-envelope')
            ->action(fn ($record) => /* send email */)
    );
```

#### Page Header Actions
```php
use Filament\Actions\Action;
use Filament\Actions\CreateAction;

protected function getHeaderActions(): array
{
    return [
        CreateAction::make(),
        Action::make('export')
            ->action(fn () => $this->export()),
    ];
}
```

#### Bulk Actions
```php
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteBulkAction;

->bulkActions([
    DeleteBulkAction::make(),
    BulkAction::make('activate')
        ->action(fn (Collection $records) => 
            $records->each->activate()
        )
        ->deselectRecordsAfterCompletion()
        ->requiresConfirmation(),
]);
```

## Advanced Action Patterns

### Modal Actions
```php
use Filament\Actions\Action;

Action::make('edit')
    ->form([
        TextInput::make('name')->required(),
        TextInput::make('email')->email(),
    ])
    ->action(function (array $data, $record) {
        $record->update($data);
        
        Notification::make()
            ->title('Saved successfully')
            ->success()
            ->send();
    })
    ->modalHeading('Edit Customer')
    ->modalDescription('Update customer details')
    ->modalSubmitActionLabel('Save')
    ->modalWidth('lg');
```

### Slide-Over Actions (NEW in v4)
```php
Action::make('view')
    ->slideOver()
    ->form([
        // form fields
    ])
    ->action(fn () => /* logic */);
```

### Confirmation Modals
```php
Action::make('delete')
    ->requiresConfirmation()
    ->modalHeading('Delete customer')
    ->modalDescription('Are you sure you want to delete this customer?')
    ->modalSubmitActionLabel('Yes, delete')
    ->color('danger')
    ->action(fn ($record) => $record->delete());
```

### Multi-Step Actions (Wizard)
```php
use Filament\Forms\Components\Wizard;

Action::make('setup')
    ->form([
        Wizard::make([
            Wizard\Step::make('Details')
                ->schema([
                    TextInput::make('name'),
                ]),
            Wizard\Step::make('Settings')
                ->schema([
                    Toggle::make('active'),
                ]),
        ]),
    ])
    ->action(fn (array $data) => /* process */);
```

## Portable Actions (NEW in v4)

### Create Once, Use Everywhere
```php
// app/Filament/Actions/ApproveAction.php
namespace App\Filament\Actions;

use Filament\Actions\Action;

class ApproveAction
{
    public static function make(): Action
    {
        return Action::make('approve')
            ->label('Approve')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->action(function ($record) {
                $record->update(['status' => 'approved']);
                
                Notification::make()
                    ->title('Approved successfully')
                    ->success()
                    ->send();
            });
    }
}
```

### Use in Multiple Contexts
```php
// In Table
use App\Filament\Actions\ApproveAction;

->actions([
    ApproveAction::make(),
]);

// In Page
protected function getHeaderActions(): array
{
    return [
        ApproveAction::make(),
    ];
}

// In Form
->suffixAction(ApproveAction::make())
```

## Action Customization

### Conditional Visibility
```php
Action::make('approve')
    ->visible(fn ($record) => $record->status === 'pending')
    ->hidden(fn ($record) => $record->isApproved());
```

### Conditional Disabling
```php
Action::make('delete')
    ->disabled(fn ($record) => $record->hasChildren())
    ->disabledTooltip('Cannot delete items with children');
```

### Authorization
```php
Action::make('delete')
    ->authorize(fn ($record) => auth()->user()->can('delete', $record))
    ->hidden(fn () => !auth()->user()->can('deleteAny', Customer::class));
```

### Before/After Hooks
```php
Action::make('process')
    ->before(function ($record) {
        Log::info("Processing {$record->id}");
    })
    ->action(function ($record) {
        $record->process();
    })
    ->after(function ($record) {
        Log::info("Processed {$record->id}");
        // Send notification, etc.
    });
```

## Action Grouping

### Dropdown Actions
```php
use Filament\Actions\ActionGroup;

->actions([
    ActionGroup::make([
        Action::make('view'),
        Action::make('edit'),
        Action::make('delete'),
    ])
    ->label('Actions')
    ->icon('heroicon-o-ellipsis-vertical')
    ->button(),
]);
```

### Icon Button Groups
```php
ActionGroup::make([
    Action::make('edit')->icon('heroicon-o-pencil'),
    Action::make('delete')->icon('heroicon-o-trash'),
])
->iconButton();
```

## Notifications with Actions

### Success Notifications
```php
Action::make('save')
    ->action(function ($record, array $data) {
        $record->update($data);
        
        Notification::make()
            ->title('Saved successfully')
            ->success()
            ->body('Customer information has been updated.')
            ->send();
    });
```

### Action Notifications
```php
Notification::make()
    ->title('Approval required')
    ->body('This customer needs your approval.')
    ->actions([
        \Filament\Notifications\Actions\Action::make('approve')
            ->button()
            ->action(fn () => /* approve */),
        \Filament\Notifications\Actions\Action::make('reject')
            ->button()
            ->color('danger')
            ->action(fn () => /* reject */),
    ])
    ->send();
```

## URL Actions

### Open URL
```php
Action::make('view_website')
    ->url(fn ($record) => $record->website)
    ->openUrlInNewTab()
    ->icon('heroicon-o-arrow-top-right-on-square');
```

### Route Actions
```php
Action::make('view')
    ->url(fn ($record) => route('customers.show', $record))
    ->color('gray');
```

## File Download Actions

```php
Action::make('download')
    ->action(function ($record) {
        return response()->download(
            storage_path("app/invoices/{$record->invoice_path}")
        );
    })
    ->icon('heroicon-o-arrow-down-tray');
```

## Complex Action Patterns

### Chained Actions
```php
Action::make('processOrder')
    ->steps([
        function ($record) {
            $record->charge();
        },
        function ($record) {
            $record->ship();
        },
        function ($record) {
            $record->notify();
        },
    ])
    ->after(function () {
        Notification::make()
            ->title('Order processed')
            ->success()
            ->send();
    });
```

### Action with File Upload
```php
Action::make('import')
    ->form([
        FileUpload::make('file')
            ->acceptedFileTypes(['text/csv'])
            ->required(),
    ])
    ->action(function (array $data) {
        Excel::import(new CustomersImport, $data['file']);
    });
```

### Action with Confirmation Input
```php
Action::make('deleteAccount')
    ->form([
        TextInput::make('confirmation')
            ->label('Type DELETE to confirm')
            ->required()
            ->rule('in:DELETE'),
    ])
    ->action(fn ($record) => $record->delete())
    ->color('danger');
```

## Best Practices

### DO:
- ✅ Create portable Action classes for reusable logic
- ✅ Use appropriate action colors (danger for delete, success for approve)
- ✅ Add confirmation for destructive actions
- ✅ Provide clear labels and descriptions
- ✅ Use before/after hooks for logging and side effects
- ✅ Handle authorization properly
- ✅ Send notifications for important actions

### DON'T:
- ❌ Put complex business logic directly in action closures
- ❌ Forget to add confirmation for dangerous operations
- ❌ Skip authorization checks
- ❌ Ignore error handling
- ❌ Create duplicate actions when you could reuse

## Testing Actions

```php
use function Pest\Livewire\livewire;

it('can approve customer', function () {
    $customer = Customer::factory()->create(['status' => 'pending']);
    
    livewire(ListCustomers::class)
        ->callTableAction('approve', $customer)
        ->assertNotified('Approved successfully');
    
    expect($customer->fresh()->status)->toBe('approved');
});

it('requires confirmation for delete', function () {
    $customer = Customer::factory()->create();
    
    livewire(ListCustomers::class)
        ->callTableAction('delete', $customer)
        ->assertActionRequiresConfirmation();
});
```

## Common Action Patterns

### Export Action
```php
use Filament\Actions\Action;
use Maatwebsite\Excel\Facades\Excel;

Action::make('export')
    ->label('Export to Excel')
    ->icon('heroicon-o-arrow-down-tray')
    ->action(function () {
        return Excel::download(
            new CustomersExport,
            'customers.xlsx'
        );
    });
```

### Clone Action
```php
Action::make('clone')
    ->icon('heroicon-o-document-duplicate')
    ->action(function ($record) {
        $clone = $record->replicate();
        $clone->save();
        
        redirect(static::getUrl('edit', ['record' => $clone]));
    });
```

### Bulk Export Action
```php
BulkAction::make('export')
    ->action(function (Collection $records) {
        return Excel::download(
            new CustomersExport($records),
            'selected-customers.xlsx'
        );
    });
```
```

---

### **4. filament-v4-tables.md**

```markdown
---
inclusion_mode: "conditional"
file_patterns:
  - "app/Filament/**/Tables/**"
  - "app/Filament/**/Resources/**"
---

# Filament v4.3+ Table Optimization & Best Practices

## Performance Improvements

### v4 Table Rendering
- **2-3x faster** than v3
- Improved Livewire wire:key handling
- Better query optimization
- Smarter eager loading

## Table Structure

### Basic Table Configuration
```php
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

public static function table(Table $table): Table
{
    return $table
        ->columns([...])
        ->filters([...])
        ->actions([...])
        ->bulkActions([...])
        ->defaultSort('created_at', 'desc')
        ->striped()
        ->poll('30s'); // Auto-refresh
}
```

## Column Types & Features

### Text Column
```php
TextColumn::make('name')
    ->searchable()
    ->sortable()
    ->copyable()
    ->copyMessage('Copied!')
    ->copyMessageDuration(1500)
    ->limit(50)
    ->tooltip(fn ($record) => $record->name)
    ->wrap()
    ->badge()
    ->color(fn ($state) => match ($state) {
        'active' => 'success',
        'inactive' => 'danger',
        default => 'gray',
    });
```

### Icon Column
```php
use Filament\Tables\Columns\IconColumn;

IconColumn::make('is_active')
    ->boolean()
    ->trueIcon('heroicon-o-check-badge')
    ->falseIcon('heroicon-o-x-mark')
    ->trueColor('success')
    ->falseColor('danger');
```

### Image Column
```php
use Filament\Tables\Columns\ImageColumn;

ImageColumn::make('avatar')
    ->circular()
    ->size(40)
    ->defaultImageUrl(url('/images/placeholder.png'));
```

### Color Column
```php
use Filament\Tables\Columns\ColorColumn;

ColorColumn::make('color')
    ->copyable();
```

### Toggle Column (Editable)
```php
use Filament\Tables\Columns\ToggleColumn;

ToggleColumn::make('is_active')
    ->onColor('success')
    ->offColor('danger')
    ->beforeStateUpdated(function ($record, $state) {
        // Log the change
    })
    ->afterStateUpdated(function ($record, $state) {
        Notification::make()
            ->title('Status updated')
            ->success()
            ->send();
    });
```

### Select Column (Editable)
```php
use Filament\Tables\Columns\SelectColumn;

SelectColumn::make('status')
    ->options([
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ])
    ->disabled(fn ($record) => $record->is_locked);
```

## Advanced Column Features

### Column Relationships
```php
TextColumn::make('user.name')
    ->label('Created By')
    ->searchable()
    ->sortable();

TextColumn::make('orders_count')
    ->counts('orders')
    ->label('Total Orders');

TextColumn::make('orders_sum_total')
    ->sum('orders', 'total')
    ->money('USD');
```

### Summarizers (NEW in v4)
```php
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\Summarizers\Average;
use Filament\Tables\Columns\Summarizers\Count;

TextColumn::make('total')
    ->money('USD')
    ->summarize([
        Sum::make()->money('USD'),
        Average::make()->money('USD'),
        Count::make(),
    ]);
```

### Column Groups
```php
use Filament\Tables\Columns\ColumnGroup;

->columns([
    ColumnGroup::make('Customer', [
        TextColumn::make('name'),
        TextColumn::make('email'),
    ]),
    ColumnGroup::make('Dates', [
        TextColumn::make('created_at'),
        TextColumn::make('updated_at'),
    ]),
]);
```

## Filters

### Select Filter
```php
use Filament\Tables\Filters\SelectFilter;

SelectFilter::make('status')
    ->options([
        'active' => 'Active',
        'inactive' => 'Inactive',
    ])
    ->default('active')
    ->multiple();
```

### Ternary Filter (Yes/No/All)
```php
use Filament\Tables\Filters\TernaryFilter;

TernaryFilter::make('is_featured')
    ->label('Featured')
    ->boolean()
    ->trueLabel('Yes')
    ->falseLabel('No')
    ->placeholder('All');
```

### Date Filter
```php
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;

Filter::make('created_at')
    ->form([
        DatePicker::make('created_from'),
        DatePicker::make('created_until'),
    ])
    ->query(function (Builder $query, array $data): Builder {
        return $query
            ->when(
                $data['created_from'],
                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
            )
            ->when(
                $data['created_until'],
                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
            );
    });
```

Prefer the shared `App\Filament\Support\Filters\DateScopeFilter` for timestamp ranges (e.g., `DateScopeFilter::make()` for `created_at`, `DateScopeFilter::make(name: 'start_at_range', column: 'start_at')` for event start dates) so filters reuse the global DateScopes trait instead of duplicating Carbon/date comparisons.

### Query Builder Filter (Complex)
```php
use Filament\Tables\Filters\QueryBuilder;

QueryBuilder::make()
    ->constraints([
        QueryBuilder\Constraints\TextConstraint::make('name'),
        QueryBuilder\Constraints\NumberConstraint::make('price'),
        QueryBuilder\Constraints\DateConstraint::make('created_at'),
        QueryBuilder\Constraints\BooleanConstraint::make('is_active'),
    ]);
```

## Search

### Basic Search
```php
->columns([
    TextColumn::make('name')
        ->searchable(),
    TextColumn::make('email')
        ->searchable(),
]);
```

### Global Search
```php
->columns([
    TextColumn::make('name')
        ->searchable(isIndividual: true, isGlobal: true),
]);
```

### Search Debouncing
```php
->searchDebounce('500ms')
->searchOnBlur()
```

## Pagination

### Standard Pagination
```php
->paginated([10, 25, 50, 100, 'all'])
->defaultPaginationPageOption(25);
```

### Simple Pagination (Better Performance)
```php
->simplePagination()
```

### Defer Loading (NEW in v4)
```php
->deferLoading()
```

## Query Optimization

### Eager Loading
```php
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->with(['user', 'orders', 'tags']);
}
```

### Scopes
```php
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->where('status', 'active')
        ->latest();
}
```

### Specific Columns (Performance)
```php
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->select(['id', 'name', 'email', 'created_at']);
}
```

## Table Layout

### Grid View
```php
use Filament\Tables\Table;

public static function table(Table $table): Table
{
    return $table
        ->contentGrid([
            'md' => 2,
            'xl' => 3,
        ])
        ->columns([...]);
}
```

### Striped Rows
```php
->striped()
```

### Column Toggling
```php
TextColumn::make('email')
    ->toggleable(isToggledHiddenByDefault: true);
```

## Empty States

### Custom Empty State
```php
->emptyStateHeading('No customers yet')
->emptyStateDescription('Create your first customer to get started.')
->emptyStateIcon('heroicon-o-user-group')
->emptyStateActions([
    Action::make('create')
        ->label('Create Customer')
        ->url(route('filament.admin.resources.customers.create'))
        ->icon('heroicon-o-plus'),
]);
```

## Bulk Actions

### Standard Bulk Actions
```php
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkAction;

->bulkActions([
    DeleteBulkAction::make(),
    
    BulkAction::make('activate')
        ->label('Activate')
        ->icon('heroicon-o-check')
        ->action(fn (Collection $records) => 
            $records->each->update(['status' => 'active'])
        )
        ->deselectRecordsAfterCompletion()
        ->requiresConfirmation(),
]);
```

### Export Bulk Action
```php
BulkAction::make('export')
    ->label('Export Selected')
    ->icon('heroicon-o-arrow-down-tray')
    ->action(function (Collection $records) {
        return Excel::download(
            new CustomersExport($records),
            'customers.xlsx'
        );
    });
```

## Reordering

### Enable Drag-and-Drop
```php
use Filament\Tables\Actions\Action;

public static function table(Table $table): Table
{
    return $table
        ->reorderable('sort_order')
        ->defaultSort('sort_order');
}

// In Model
protected $fillable = ['sort_order'];
```

## Header/Footer Actions

### Table Header Actions
```php
->headerActions([
    Action::make('export')
        ->action(fn () => /* export all */),
])
```

## Polling & Real-Time

### Auto-Refresh
```php
->poll('30s') // Refresh every 30 seconds
```

### Disable Polling Conditionally
```php
->poll(fn () => auth()->user()->wants_live_updates ? '10s' : null)
```

## Performance Best Practices

### DO:
- ✅ Use `->select()` to limit columns in queries
- ✅ Eager load relationships with `->with()`
- ✅ Use simple pagination for large datasets
- ✅ Implement proper indexes on searchable/sortable columns
- ✅ Use `->lazy()` for heavy columns
- ✅ Cache filter options
- ✅ Use summarizers instead of custom queries

### DON'T:
- ❌ Make every column searchable/sortable
- ❌ Load unnecessary relationships
- ❌ Use `->get()` or `->all()` in large tables
- ❌ Forget to add database indexes
- ❌ Ignore N+1 query problems

## Testing Tables

```php
use function Pest\Livewire\livewire;

it('can list customers', function () {
    $customers = Customer::factory()->count(10)->create();
    
    livewire(ListCustomers::class)
        ->assertCanSeeTableRecords($customers);
});

it('can search customers', function () {
    Customer::factory()->create(['name' => 'John Doe']);
    Customer::factory()->create(['name' => 'Jane Smith']);
    
    livewire(ListCustomers::class)
        ->searchTable('John')
        ->assertCanSeeTableRecords(
            Customer::where('name', 'like', '%John%')->get()
        );
});

it('can sort customers', function () {
    livewire(ListCustomers::class)
        ->sortTable('name')
        ->assertCanSeeTableRecords(
            Customer::orderBy('name')->get(),
            inOrder: true
        );
});

it('can filter customers', function () {
    Customer::factory()->create(['status' => 'active']);
    Customer::factory()->create(['status' => 'inactive']);
    
    livewire(ListCustomers::class)
        ->filterTable('status', 'active')
        ->assertCanSeeTableRecords(
            Customer::where('status', 'active')->get()
        );
});
```

## Common Patterns

### Status Badge Column
```php
TextColumn::make('status')
    ->badge()
    ->color(fn (string $state): string => match ($state) {
        'draft' => 'gray',
        'reviewing' => 'warning',
        'published' => 'success',
        'rejected' => 'danger',
    })
    ->icon(fn (string $state): string => match ($state) {
        'draft' => 'heroicon-o-pencil',
        'reviewing' => 'heroicon-o-clock',
        'published' => 'heroicon-o-check-circle',
        'rejected' => 'heroicon-o-x-circle',
    });
```

### Money Column
```php
TextColumn::make('price')
    ->money('USD')
    ->sortable();
```

### Date Column
```php
TextColumn::make('created_at')
    ->dateTime('M j, Y')
    ->sortable()
    ->since() // Show "2 hours ago"
    ->description(fn ($record): string => 
        $record->created_at->format('l, F j, Y')
    );
```
