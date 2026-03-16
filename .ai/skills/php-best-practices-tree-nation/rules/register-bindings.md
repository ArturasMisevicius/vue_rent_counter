# Register Repository Bindings

## Why

Laravel's service container resolves dependencies by interface. Without a binding, injecting a repository interface will throw a `BindingResolutionException`.

## Where

`app/Providers/TreeNationProvider.php` — add to the `$bindings` array.

## Pattern

```php
protected $bindings = [
    // existing bindings ...
    HashtagRepository::class => EloquentHashtagRepository::class,
];
```

## Checklist

- Add the binding immediately after creating the repository interface and implementation.
- Use the fully-qualified class name for both interface and implementation (or add `use` imports at the top of the provider).
- Never use `$this->app->bind()` in the `register()` method for repositories — the `$bindings` array is the project convention.
- Confirm the interface namespace is `API\[Domain]\Domain\Repository\` and the implementation namespace is `API\[Domain]\Infrastructure\Persistence\`.
