# Unit Test Services

## Why

Services contain all business logic. Unit tests verify that logic in isolation — without a database, HTTP layer, or Laravel boot — making them fast and reliable.

## Pattern

```php
// tests/Unit/src/API/[Domain]/Application/Services/GetHashtagsServiceTest.php
declare(strict_types=1);

namespace Tests\Unit\src\API\Hashtag\Application\Services;

use API\Hashtag\Application\DTO\GetHashtagsDTO;
use API\Hashtag\Application\Services\GetHashtagsService;
use API\Hashtag\Application\Services\GetHashtagsServiceResponse;
use API\Hashtag\Domain\Repository\HashtagRepository;
use App\Models\Hashtag;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

final class GetHashtagsServiceTest extends TestCase
{
    private GetHashtagsService $sut;
    private HashtagRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(HashtagRepository::class);
        $this->sut = new GetHashtagsService($this->repository);
    }

    public function test_execute_returns_response_with_matching_hashtags(): void
    {
        $hashtag = new Hashtag();
        $hashtag->hashtag = 'trees';
        $hashtag->count = 42;

        $this->repository
            ->expects(self::once())
            ->method('matching')
            ->willReturn(new Collection([$hashtag]));

        $dto = new GetHashtagsDTO(
            featured: false,
            trending: true,
            hashtag: null,
            limit: 10,
            showHashtagOfTheDay: false,
            excludeHashtag: null,
        );

        $result = $this->sut->execute($dto);

        self::assertInstanceOf(GetHashtagsServiceResponse::class, $result);
        self::assertCount(1, $result->items());
    }

    public function test_execute_returns_empty_response_when_no_results(): void
    {
        $this->repository
            ->expects(self::once())
            ->method('matching')
            ->willReturn(new Collection([]));

        $dto = new GetHashtagsDTO(
            featured: false,
            trending: false,
            hashtag: 'nonexistent',
            limit: 10,
            showHashtagOfTheDay: false,
            excludeHashtag: null,
        );

        $result = $this->sut->execute($dto);

        self::assertInstanceOf(GetHashtagsServiceResponse::class, $result);
        self::assertCount(0, $result->items());
    }
}
```

## Checklist

- Extend `PHPUnit\Framework\TestCase` (not Laravel's `TestCase`) for pure unit tests.
- Name the service under test `$sut` (System Under Test).
- Mock repository interfaces with `$this->createMock(InterfaceClass::class)`.
- Use `expects(self::once())` to verify the repository is called exactly once per test.
- Use `willReturn()` to define mock return values.
- Test name format: `test_[what]_[scenario]_[expected_result]()`.
- Cover both happy path and edge cases (empty results, boundary values).
- Each test method verifies one specific behavior.
- Location: `tests/Unit/src/API/[Domain]/Application/Services/[Action]ServiceTest.php`.

## Running Tests

```bash
# Run specific service test
php artisan test tests/Unit/src/API/Hashtag/Application/Services/GetHashtagsServiceTest.php

# Run all unit tests
php artisan test tests/Unit

# Run with coverage
php artisan test --coverage
```
