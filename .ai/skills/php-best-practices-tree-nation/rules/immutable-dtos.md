# Immutable DTOs

## Why

DTOs transport data between layers. Allowing mutation after construction leads to hidden state changes that are hard to trace.

## Pattern

```php
// src/API/[Domain]/Application/DTO/GetHashtagsDTO.php
declare(strict_types=1);

namespace API\Hashtag\Application\DTO;

final class GetHashtagsDTO
{
    public function __construct(
        public readonly bool $featured,
        public readonly bool $trending,
        public readonly ?string $hashtag,
        public readonly int $limit,
        public readonly bool $showHashtagOfTheDay,
        public readonly ?string $excludeHashtag,
    ) {
    }
}
```

## Checklist

- Declare `declare(strict_types=1)` at the top.
- Mark class as `final`.
- Use constructor property promotion (PHP 8+) with `public readonly` visibility.
- Access DTO data directly via readonly properties.
- Nullable fields use `?Type` syntax.
- No business logic inside DTOs.
- Location: `src/API/[Domain]/Application/DTO/[Action]DTO.php`.
