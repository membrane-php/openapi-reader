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

### Narrow Schemas To False If Impossible To Pass
The `false` boolean schema explicitly states any input will fail.
The Reader will narrow schemas to `false` if it is proven impossible to pass.
This optimizes code that may otherwise validate input that will always fail.

#### Enum Specified Empty
Any schema specifying an empty `enum`, is narrowed to the `false` boolean schema.

If `enum` is specified, it contains the exhaustive list of valid values.
If `enum` is specified as an empty array, there are no valid values.

#### Enum Without A Valid Value
If a schema specifies `enum` without a value that passes the rest of the schema; 
it is impossible to pass, it will be narrowed to the `false` boolean schema.

### Narrow Typehints
Typehints are narrowed if it has no impact on expressiveness.

#### AllOf, AnyOf and OneOf Are Always Arrays

`allOf`, `anyOf` and `oneOf` can express two things:
1. There are subschemas
2. There are not

To express there are no subschemas, the value is omitted.

As such, the Reader structures `allOf`, `anyOf` and `oneOf` in two ways:
1. A non-empty array
2. An empty array

Though these keywords are not allowed to be empty,
[The Reader allows it](validation-deviations.md#allof-anyof-and-oneof-can-be-empty)
for the sake of simplicity.

This simplifies code that loops through subschemas.

#### String Metadata Is Always String

Optional metadata is expressed in two ways:
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

### Combined Fields
Data is combined, if and only if, it has no impact on expressiveness.

#### Maximum|Minimum are combined with ExclusiveMaximum|ExclusiveMinimum

[//]: # (TODO explain how they are combined for 3.0 and in 3.1 we take the more restrictive keyword)

A numerical limit can only be expressed in three ways:
- There is no limit
- There is an inclusive limit
- There is an exclusive limit

As such the Reader combines the relevant keywords into:
- `Limit|null $maximum`. 
- `Limit|null $minimum`.

Where `Limit` has two properties:
- `float|int $limit` 
- `bool $exclusive`

#### Const Overrides Enum in 3.1

[//]: # (TODO Flesh out a bit)

The more restrictive keyword takes precedence.
