---
name: livewire-development
description: "Use when creating, reviewing, or refactoring Livewire 4 components, component state, wire directives, render/query behavior, or Livewire component tests in Laravel apps."
license: MIT
metadata:
  author: laravel
---

# Livewire Development

## When to Apply

Activate this skill when:

- Creating or modifying Livewire components
- Using wire: directives (model, click, loading, sort, intersect)
- Implementing islands or async actions
- Writing Livewire component tests

## Documentation

Use `search-docs` for detailed Livewire 4 patterns and documentation.

## Basic Usage

### Creating Components

```bash

# Single-file component (default in v4)

php artisan make:livewire create-post

# Multi-file component

php artisan make:livewire create-post --mfc

# Class-based component (v3 style)

php artisan make:livewire create-post --class

# With namespace

php artisan make:livewire Posts/CreatePost
```

### Converting Between Formats

Use `php artisan livewire:convert create-post` to convert between single-file, multi-file, and class-based formats.

### Choosing a Component Format

Before creating a component, check `config/livewire.php` for directory overrides, which change where files are stored. Then, look at existing files in those directories (defaulting to `app/Livewire/` and `resources/views/livewire/`) to match the established convention.

### Component Format Reference

| Format | Flag | Class Path | View Path |
|--------|------|------------|-----------|
| Single-file (SFC) | default | — | `resources/views/livewire/create-post.blade.php` (PHP + Blade in one file) |
| Multi-file (MFC) | `--mfc` | `app/Livewire/CreatePost.php` | `resources/views/livewire/create-post.blade.php` |
| Class-based | `--class` | `app/Livewire/CreatePost.php` | `resources/views/livewire/create-post.blade.php` |
| View-based | ⚡ prefix | — | `resources/views/livewire/create-post.blade.php` (Blade-only with functional state) |

Namespaced components map to subdirectories: `make:livewire Posts/CreatePost` creates files at `app/Livewire/Posts/CreatePost.php` and `resources/views/livewire/posts/create-post.blade.php`.

### Single-File Component Example

<!-- Single-File Component Example -->
```php
<?php
use Livewire\Component;

new class extends Component {
    public int $count = 0;

    public function increment(): void
    {
        $this->count++;
    }
}
?>

<div>
    <button wire:click="increment">Count: @{{ $count }}</button>
</div>
```

## Livewire 4 Specifics

### Key Changes From Livewire 3

These things changed in Livewire 4, but may not have been updated in this application. Verify this application's setup to ensure you follow existing conventions.

- Use `Route::livewire()` for full-page components (e.g., `Route::livewire('/posts/create', CreatePost::class)`); config keys renamed: `layout` → `component_layout`, `lazy_placeholder` → `component_placeholder`.
- `wire:model` now ignores child events by default (use `wire:model.deep` for old behavior); `wire:scroll` renamed to `wire:navigate:scroll`.
- Component tags must be properly closed; `wire:transition` now uses View Transitions API (modifiers removed).
- JavaScript: `$wire.$js('name', fn)` → `$wire.$js.name = fn`; `commit`/`request` hooks → `interceptMessage()`/`interceptRequest()`.

### New Features

- Component formats: single-file (SFC), multi-file (MFC), view-based components.
- Islands (`@island`) for isolated updates; lazy islands can use `@placeholder` for skeleton states.
- Async actions (`wire:click.async`, `#[Async]`) for parallel execution.
- Deferred/bundled loading: `defer`, `lazy.bundle` for optimized component loading.
- Persisted navigation and current-link styling: `@persist`, `wire:current`, and `wire:navigate:scroll`.
- DOM visibility and replacement helpers: `wire:show`, `wire:replace`, and `wire:transition`.

| Feature | Usage | Purpose |
|---------|-------|---------|
| Islands | `@island` / `@island(lazy: true)` | Isolated update regions |
| Placeholder | `@placeholder ... @endplaceholder` | Loading UI for lazy islands |
| Async | `wire:click.async` or `#[Async]` | Non-blocking actions |
| Deferred | `defer` attribute | Load after page render |
| Bundled | `lazy.bundle` | Load multiple together |
| Persist | `@persist('nav')` | Keep navigation/media between `wire:navigate` visits |
| Current | `wire:current="..."` | Active-link styling on navigate links |
| Show | `wire:show="expression"` | Toggle visibility without tearing DOM nodes down |
| Replace | `wire:replace` | Fully replace DOM children for stateful custom elements |

### New Directives

- `wire:sort`, `wire:intersect`, `wire:ref`, `wire:show`, `wire:replace`, `wire:current`, `.renderless`, and `.preserve-scroll` are available for use.
- `data-loading` attribute automatically added to elements triggering network requests.

| Directive | Purpose |
|-----------|---------|
| `wire:sort` | Drag-and-drop sorting |
| `wire:intersect` | Viewport intersection detection |
| `wire:ref` | Element references for JS |
| `wire:show` | Toggle visibility with CSS instead of removing DOM |
| `wire:replace` | Replace children for custom/stateful DOM islands |
| `wire:current` | Style active `wire:navigate` links |
| `.renderless` | Component without rendering |
| `.preserve-scroll` | Preserve scroll position |

## Best Practices

- Always use `wire:key` in loops
- Use `wire:loading` for loading states
- Use `wire:model.live` for instant updates (default is debounced)
- Validate and authorize in actions (treat like HTTP requests)
- Use `@island` when only part of a larger Livewire surface needs isolated re-rendering
- Use `@placeholder` only for lazy islands or lazy-loaded regions where a skeleton improves perceived performance
- Use `wire:show` when you want transitions and DOM continuity; use Blade `@if` when the element should not exist at all
- Use `wire:current` on `wire:navigate` links when active styling should survive persisted navigation

## State Design

Separate state into three buckets:

- Persistent state: form inputs, filters, sort direction, pagination, route-backed values
- Derived state: query results, labels, selected records, counts, presenter output
- UI-only state: dropdown visibility, modal state, tabs, loading toggles

Rules:
- Keep derived state out of public arrays/collections when it can be `#[Computed]`
- Do not store full Eloquent collections or models in public properties unless there is a strong reason
- Prefer scalar IDs or lightweight arrays over hydrated models in component state
- Protect route/server-owned properties like IDs, tokens, and context flags with `#[Locked]`

## Livewire 4 Attributes

Use Livewire 4 attributes intentionally:

- `#[Computed]`: for request-memoized derived state such as selected records, filtered collections, and presenter output
- `#[Validate]`: for property-level rules on interactive inputs and filters
- `#[Locked]`: for public properties that must not be modified client-side

```php
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;

#[Locked]
public string $token = '';

#[Validate('required|string|in:all,open,closed')]
public string $status = 'all';

#[Computed]
public function tickets()
{
    return Ticket::query()
        ->forOrganization(auth()->user()->organization_id)
        ->when($this->status !== 'all', fn ($query) => $query->where('status', $this->status))
        ->simplePaginate(10);
}
```

Notes:
- `#[Computed]` is memoized only for the current component request
- If a mutation changes the underlying source, invalidate with `unset($this->computedPropertyName)`
- Use application cache only for expensive shared data, not as a substitute for good component structure

## Query and Render Guidance

- Keep `render()` thin; resolve heavy derived state in `#[Computed]` methods or dedicated presenter/query classes
- If the same component method needs the same query in `mount()`, validation, and `render()`, move it to `#[Computed]`
- Use explicit `select([...])` on large list queries
- Prefer paginated results for large datasets
- Use `withCount()` / `withExists()` instead of loading entire relations for badges or booleans
- Use `wire:init` when data can load after first paint without harming UX

## Hydration Safety

Watch for:
- large public arrays of search results
- large notification/activity payloads
- storing models or collections that rehydrate on every request
- repeated relation loading triggered by `updated*` handlers or render-time calls

If a component repeatedly serializes large datasets, move those results to computed state or a paginated query.

## Architecture Guidance

- Livewire components should orchestrate UI, not own deep domain logic
- Move writes, transactions, and multi-step workflows into action/service classes
- Move large read queries into presenters/query objects if the component starts collecting many scopes
- Use nested Livewire components only when the child needs isolated behavior or lifecycle; otherwise prefer Blade

## Blade Pairing

- Add `wire:key` to dynamic loops, nested lists, and items that can reorder or update independently
- Use `wire:navigate` for full-page Livewire routes when it clearly improves navigation flow
- Use `@persist` selectively for navigation, audio/video, or shell regions that should survive `wire:navigate` visits
- Use `wire:navigate:scroll` only on persisted scroll containers that should retain their own scroll position
- Prefer `wire:transition` for Livewire-controlled conditional regions instead of ad hoc CSS-only fades
- Avoid expensive inline formatting that would require extra queries; preload what the Blade needs
- Do not place `@island` directly inside Blade loops or conditionals; move the loop or conditional inside the island

## Configuration

- `smart_wire_keys` defaults to `true`; new configs: `component_locations`, `component_namespaces`, `make_command`, `csp_safe`.

## Alpine & JavaScript

- `wire:transition` uses browser View Transitions API; `$errors` and `$intercept` magic properties available.
- Non-blocking `wire:poll` and parallel `wire:model.live` updates improve performance.

For interceptors and hooks, see [reference/javascript-hooks.md](reference/javascript-hooks.md).

## Testing

<!-- Testing Example -->
```php
Livewire::test(Counter::class)
    ->assertSet('count', 0)
    ->call('increment')
    ->assertSet('count', 1);
```

## Verification

1. Browser console: Check for JS errors
2. Network tab: Verify Livewire requests return 200
3. Ensure `wire:key` on all `@foreach` loops

## Common Pitfalls

- Missing `wire:key` in loops → unexpected re-rendering
- Expecting `wire:model` real-time → use `wire:model.live`
- Unclosed component tags → syntax errors in v4
- Using deprecated config keys or JS hooks
- Including Alpine.js separately (already bundled in Livewire 4)
- Putting large query trees directly inside `render()` instead of computed/presenter layers
- Trusting client-editable public IDs or tokens that should be `#[Locked]`
- Hiding repeated heavy database work inside computed properties without discussing cache strategy
