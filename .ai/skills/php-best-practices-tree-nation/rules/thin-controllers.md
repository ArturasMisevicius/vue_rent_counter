# Thin Controllers

## Why

Controllers are the HTTP entry point, not the place for business logic. Keeping them thin makes the logic reusable, testable, and independent of the HTTP layer.

## Pattern

```php
class GetHashtagsController extends ApiController
{
    public function __construct(private GetHashtagsService $service) {}

    public function __invoke(GetHashtagsRequest $request): JsonResponse
    {
        $dto = new GetHashtagsDTO(
            featured: $request->boolean('featured'),
            limit: $request->integer('limit', 10),
        );

        return response()->json($this->service->execute($dto));
    }

    public function exceptions(): array
    {
        return [];
    }
}
```

## Checklist

- Controller only creates the DTO, calls the service, and returns the response.
- No `if`, loops, or database calls inside the controller method.
- No `new Service()` instantiation — inject via constructor.
- Implement `exceptions(): array` (return empty array if no custom exceptions needed).
- Location: `app/Http/Controllers/[Domain]/[Action]Controller.php`.
