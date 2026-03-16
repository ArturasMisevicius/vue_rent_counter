# Repository Interface

## Why

The domain layer defines *what* data operations are needed without knowing *how* they are implemented. This decouples business logic from Eloquent (or any ORM), making services testable with mocks.

## Pattern

```php
// src/API/[Domain]/Domain/Repository/[Domain]Repository.php
declare(strict_types=1);

namespace API\Hashtag\Domain\Repository;

use API\Shared\Domain\Criteria\Criteria;
use Illuminate\Support\Collection;

interface HashtagRepository
{
    /**
     * @return Collection<int, \App\Models\Hashtag>
     */
    public function matching(Criteria $criteria): Collection;
}
```

## Checklist

- Place the interface in `src/API/[Domain]/Domain/Repository/`.
- Define only the `matching(Criteria $criteria): Collection` method unless additional query types are genuinely needed.
- Add a `@return Collection<int, ModelClass>` PHPDoc annotation.
- No framework imports (no Eloquent, no Laravel facades) in this file.
- After creating the interface, register it in `TreeNationProvider` (see `rules/register-bindings.md`).
