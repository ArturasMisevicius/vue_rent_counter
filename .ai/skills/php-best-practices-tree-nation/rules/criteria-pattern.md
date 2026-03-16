# Criteria Pattern

## Why

The Criteria pattern builds queries in a database-agnostic way inside the service layer, keeping Eloquent details out of business logic and enabling easy testing with mocked repositories.

## Components (in `src/API/Shared/Domain/Criteria/`)

| Class | Purpose |
|---|---|
| `Criteria` | Main query object (filters + order + offset + limit) |
| `Filters` | Collection of filter conditions |
| `Order` | Sorting specification |

## Pattern

```php
$filters = [];

if ($dto->isTrending()) {
    $filters[] = ['field' => 'trending', 'operator' => '=', 'value' => true];
}

if ($dto->getHashtag() !== null) {
    $filters[] = ['field' => 'hashtag', 'operator' => 'LIKE', 'value' => "%{$dto->getHashtag()}%"];
}

$criteria = new Criteria(
    Filters::fromValues($filters),
    Order::fromValues('count', 'desc'),
    null,               // offset
    $dto->getLimit(),   // limit
);

$results = $this->repository->matching($criteria);
```

## Available Filter Operators

| Operator | Meaning |
|---|---|
| `=` | Equal |
| `!=` | Not equal |
| `>` | Greater than |
| `<` | Less than |
| `>=` | Greater than or equal |
| `<=` | Less than or equal |
| `LIKE` | Pattern match (use `%` wildcards) |
| `IN` | In array |

## Checklist

- Build the `$filters` array conditionally — only add a filter when the parameter is provided.
- Use `Filters::fromValues([])` (empty array) when no filters apply.
- Pass `null` for offset or limit when not needed.
- Never write raw Eloquent queries inside the service; always use Criteria.
- `LaravelEloquentCriteriaConverter::convert($criteria)` in the repository translates this to Eloquent automatically.
