# Invokable Controllers

## Why

Single-action controllers (`__invoke`) enforce the single-responsibility principle: one controller, one HTTP action. This keeps routing expressive and controllers focused.

## Pattern

```php
// routes/api.php
Route::get('/hashtags', GetHashtagsController::class);

// Controller
class GetHashtagsController extends ApiController
{
    public function __invoke(GetHashtagsRequest $request): JsonResponse { ... }
}
```

## Checklist

- Use `__invoke` for every new controller.
- Register route using the controller class directly (no method string).
- Do not add other public methods to single-action controllers.
- Extend `App\Http\Controllers\ApiController`, not the base Laravel `Controller`.
