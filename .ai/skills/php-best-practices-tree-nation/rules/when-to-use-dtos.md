# When to Use DTOs

## Why

DTOs make parameter passing explicit and type-safe. However, they add overhead for trivial services.

## Rule

| Scenario | Approach |
|---|---|
| Service needs 3+ parameters | Create a DTO |
| Service needs 1–2 simple parameters | Pass directly to the service method |
| Parameters have related validation or default logic | Create a DTO |
| Single primitive value | Pass directly |

## Example — Use DTO

```php
// 6 parameters → DTO is appropriate
$dto = new GetHashtagsDTO(
    featured: $request->boolean('featured'),
    trending: $request->boolean('trending'),
    hashtag: $request->input('hashtag'),
    limit: $request->integer('limit', 10),
    showHashtagOfTheDay: $request->boolean('showHashtagOfTheDay'),
    excludeHashtag: $request->input('excludeHashtag'),
);
$this->service->execute($dto);
```

## Example — Skip DTO

```php
// 1 parameter → pass directly
$result = $this->service->execute($request->integer('userId'));
```

## Checklist

- If unsure, default to creating a DTO when the parameter count is likely to grow.
- Never use arrays or `stdClass` as a substitute for a typed DTO.
