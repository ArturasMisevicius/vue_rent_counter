# Model Property Access via Getters

## Why

Accessing Eloquent model properties directly (e.g. `$action->id`) bypasses any accessor logic defined on the model and makes the code harder to refactor. Using getter methods (or Eloquent accessors) keeps the access point consistent and explicit.

## Pattern

Define an accessor on the model (Laravel's `Attribute` casting or a plain getter method):

```php
// app/Models/Action.php
use Illuminate\Database\Eloquent\Casts\Attribute;

final class Action extends Model
{
    public function id(): Attribute
    {
        return Attribute::get(fn () => $this->attributes['id']);
    }

    public function title(): Attribute
    {
        return Attribute::get(fn () => $this->attributes['title']);
    }
}
```

For relationships, use typed getter methods rather than relying on dynamic properties or `getAttribute()`:

```php
// app/Models/Action.php
public function getOwner(): ?User
{
    return $this->owner instanceof User ? $this->owner : null;
}

public function getHabit(): ?Habit
{
    return $this->habit instanceof Habit ? $this->habit : null;
}
```

Then access via the getter in services and response objects:

```php
// src/API/Habit/Application/Services/GetActionDetailService.php
return new GetActionDetailResponse(
    id: $action->id(),
    title: $action->title(),
    description: $action->description(),
);

$owner = $action->getOwner();
$habit = $action->getHabit();
```

## Checklist

- **Do not access model properties directly** (e.g. `$action->id`, `$action->title`) inside services or response constructors.
- **Do not call `->getAttribute()`** directly in services or response objects — use typed getter methods instead.
- Use getter methods or Eloquent `Attribute` accessors instead.
- For relationship objects, define a typed getter (e.g. `getOwner(): ?User`) that returns the related model with the correct type, rather than using `->getAttribute('owner')`.
- If the model does not yet have a getter for the property you need, add one before using it.
- This applies when building response objects, DTOs, and any other place where model data is mapped to another structure.
