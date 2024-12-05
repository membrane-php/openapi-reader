# How The Reader Simplifies Your OpenAPI

Besides simply parsing your OpenAPI into `Validated` objects,
the reader is designed to _simplify the developer experience_ of other OpenAPI tools.

## Standard Simplifications

### Never Worry About Uninitialized Properties
Properties should be safe to access:
All properties have a value.
`null` represents an omitted field _if_ no other value can be safely assumed.

### Strong Typehints
Data structures should be easily discoverable.
All properties should have strong typehints.

## Opinionated Simplifications

## Narrow Typehints
Typehints are narrowed, if and only if, it has no impact on expressiveness.

### String Metadata Is Always String

Optional metadata is expressed in two ways;
1. There is data
2. There is not

As such, the Reader structures metadata in two ways:
1. A string containing non-whitespace characters
2. An empty string `''`

When accessing string metadata only one check is necessary:

```php
if ($metadata !== '') {
    // do something...
}
```

## Combined Fields
Data is combined, if and only if, it has no impact on expressiveness.

### Maximum|Minimum are combined with ExclusiveMaximum|ExclusiveMinimum

The structure of numeric maximums and minimums vary between versions.
However, both versions only express two things:
- Is there a limit
- Is it exclusive

As such the Reader combines the fields into:
- A `Limit|null $maximum`. 
- A `Limit|null $minimum`.

Where A `Limit` has two properties:
- `float|int $limit` 
- `bool $exclusive`
