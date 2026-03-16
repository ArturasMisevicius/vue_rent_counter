# Eloquent Repository Implementation

## Why

The infrastructure layer provides the concrete Eloquent implementation of the domain interface. Separating this from the interface lets us swap implementations (e.g., for caching or testing) without touching business logic.

## Pattern

```php
// src/API/[Domain]/Infrastructure/Persistence/Eloquent[Domain]Repository.php
declare(strict_types=1);

namespace API\Hashtag\Infrastructure\Persistence;

use API\Hashtag\Domain\Repository\HashtagRepository;
use API\Shared\Domain\Criteria\Criteria;
use API\Shared\Infrastructure\Persistence\EloquentRepository;
use API\Shared\Infrastructure\Persistence\LaravelEloquentCriteriaConverter;
use App\Models\Hashtag;
use Illuminate\Support\Collection;

final class EloquentHashtagRepository extends EloquentRepository implements HashtagRepository
{
    public function __construct()
    {
        parent::__construct(new Hashtag());
    }

    /**
     * @return Collection<int, Hashtag>
     */
    public function matching(Criteria $criteria): Collection
    {
        $eloquentCriteria = LaravelEloquentCriteriaConverter::convert($criteria);
        $query = Hashtag::query()->tap($eloquentCriteria);

        /** @var Collection<int, Hashtag> */
        return $query->get();
    }
}
```

## Checklist

- Extend `API\Shared\Infrastructure\Persistence\EloquentRepository`.
- Implement the domain repository interface.
- Constructor calls `parent::__construct(new ModelClass())`.
- Use `LaravelEloquentCriteriaConverter::convert()` to translate `Criteria` to Eloquent.
- Return a typed `Collection<int, ModelClass>` with a `@var` cast.
- Location: `src/API/[Domain]/Infrastructure/Persistence/Eloquent[Domain]Repository.php`.
- Register the binding in `TreeNationProvider` after creating this class.
- **Always start queries with `Model::query()`** instead of calling static methods like `Model::where()` or `Model::findOrFail()` directly. This gives PHPStan a proper `Builder` type and avoids the need for `// @phpstan-ignore-next-line`:
  ```php
  // correct
  return Action::query()->findOrFail($id);
  return Action::query()->where('habit_id', $habitId)->where('active', true)->get();

  // wrong — triggers PHPStan errors on magic static calls
  return Action::findOrFail($id);       // @phpstan-ignore-next-line required
  return Action::where('habit_id', $habitId)->get();
  ```
