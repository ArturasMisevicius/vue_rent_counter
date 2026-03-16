# Request Validation Class

## Why

Separating validation from the controller keeps the controller thin and makes the validation rules easy to test and reuse. It also prevents SQL injection and other input-based attacks by centralising sanitisation rules.

## Pattern

Query parameter validation:

```php
// app/Http/Requests/[Domain]/GetHashtagsRequest.php
class GetHashtagsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'featured' => 'sometimes|in:true,false',
            'trending' => 'sometimes|in:true,false',
            'hashtag' => 'sometimes|string',
            'limit' => 'sometimes|integer|min:1|max:100',
            'showHashtagOfTheDay' => 'sometimes|in:true,false',
            'excludeHashtag' => 'sometimes|string',
        ];
    }
}
```

Route parameter validation (e.g. a slug or ID from the URL):

```php
// app/Http/Requests/[Domain]/GetHabitActionsRequest.php
class GetHabitActionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'habitName' => 'required|string|alpha_dash',
        ];
    }
}
```

Inject it in the controller exactly the same way as for query params — Laravel resolves route segments automatically:

```php
public function __invoke(GetHabitActionsRequest $request): JsonResponse
{
    $habitName = $request->validated('habitName');
    // ...
}
```

## Checklist

- Extend `Illuminate\Foundation\Http\FormRequest`.
- Place in `app/Http/Requests/[Domain]/[Action]Request.php`.
- Use `sometimes` for optional query parameters.
- Use `in:true,false` for boolean query parameters (they arrive as strings in GET requests).
- Use `boolean()` / `integer()` / `validated()` helpers in the controller to cast values.
- `authorize()` returns `true` by default; add real auth logic only when required.
- No business logic in `rules()`.
- **Never sanitise input inside the controller** (no `preg_replace`, `strip_tags`, etc.). Express all constraints as validation rules in the `FormRequest` instead.
- Route parameters must also be validated through a `FormRequest`, not cast or sanitised inline in the controller method signature.
- **If a custom validation closure (e.g., checking that an image URL belongs to allowed domains) could apply to more than one request type, extract it to a domain service or a custom `Rule` class** — do not duplicate the closure across multiple `FormRequest` classes.
