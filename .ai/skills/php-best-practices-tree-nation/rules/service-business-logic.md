# Service Business Logic

## Why

Services are the single home for business logic. This makes logic reusable across controllers, jobs, and commands, and makes it unit-testable without booting the HTTP layer.

## Pattern

```php
// src/API/[Domain]/Application/Services/GetHashtagsService.php
declare(strict_types=1);

namespace API\Hashtag\Application\Services;

use API\Hashtag\Application\DTO\GetHashtagsDTO;
use API\Hashtag\Domain\Repository\HashtagRepository;
use API\Shared\Domain\Criteria\Criteria;
use API\Shared\Domain\Criteria\Filters;
use API\Shared\Domain\Criteria\Order;

final class GetHashtagsService
{
    public function __construct(private HashtagRepository $repository) {}

    public function execute(GetHashtagsDTO $dto): GetHashtagsServiceResponse
    {
        $filters = [];

        if ($dto->isFeatured()) {
            $filters[] = ['field' => 'featured', 'operator' => '=', 'value' => true];
        }

        if ($dto->getHashtag() !== null) {
            $filters[] = ['field' => 'hashtag', 'operator' => 'LIKE', 'value' => "%{$dto->getHashtag()}%"];
        }

        $criteria = new Criteria(
            Filters::fromValues($filters),
            Order::fromValues('count', 'desc'),
            null,
            $dto->getLimit(),
        );

        $results = $this->repository->matching($criteria);

        return new GetHashtagsServiceResponse($results->toArray());
    }
}
```

## Checklist

- Declare `declare(strict_types=1)`.
- Mark class as `final`.
- Inject repositories and other services via constructor — never instantiate inside the method.
- Accept a DTO (or typed primitives for simple cases) as `execute()` parameters.
- Return a typed `ServiceResponse` object, never a raw array.
- All conditional logic belongs here, not in the controller.
- Location: `src/API/[Domain]/Application/Services/[Action]Service.php`.
