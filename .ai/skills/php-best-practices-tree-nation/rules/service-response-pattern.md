# Service Response Pattern

## Why

Wrapping service output in a typed response object enforces a stable contract between the service and its callers, and allows direct JSON serialization without extra transformation logic.

## Pattern

```php
// src/API/[Domain]/Application/Services/GetHashtagsServiceResponse.php
declare(strict_types=1);

namespace API\Hashtag\Application\Services;

use JsonSerializable;

final class GetHashtagsServiceResponse implements JsonSerializable
{
    /**
     * @param array<int, array{hashtag: string, count: int}> $items
     */
    public function __construct(private array $items) {}

    /**
     * @return array<int, array{hashtag: string, count: int}>
     */
    public function items(): array
    {
        return $this->items;
    }

    /**
     * @return array<int, array{hashtag: string, count: int}>
     */
    public function jsonSerialize(): array
    {
        return $this->items;
    }
}
```

## Checklist

- Implement `JsonSerializable` so the object can be passed directly to `response()->json()`.
- Add `@param` and `@return` PHPDoc annotations with precise array shapes for complex types.
- Keep the class `final` and immutable (no setters).
- Provide named accessor methods (e.g., `items()`) alongside `jsonSerialize()`.
- Location: `src/API/[Domain]/Application/Services/[Action]ServiceResponse.php`.
- **No extra spaces for alignment** in array keys. Use a single space around `=>`:
  ```php
  // correct
  'id' => $this->id,
  'title' => $this->title,

  // wrong — do not pad keys to align values
  'id'    => $this->id,
  'title' => $this->title,
  ```
