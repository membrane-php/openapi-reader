# Validation And How It Deviates From OpenAPI Specification
This page documents where validation deviates from the OpenAPI Specification.

## Stricter Requirements
It is stricter when it comes to providing an unambiguous specification.
This helps to avoid confusion as well as simplify development for other OpenAPI tools.

### OperationId Is Required

Operations MUST have an [operationId](https://github.com/OAI/OpenAPI-Specification/blob/3.1.0/versions/3.0.3.md#fixed-fields-8).

`operationId` is a unique string used to identify an operation.
By making it required, it serves as a _reliable_ method of identification.

### Query Strings Must Be Unambiguous

Operations MUST NOT use more than one _ambiguous parameter_.

In this context, an _ambiguous parameter_ is defined as being `in:query` with one of the following combinations:
- `type:object` with `style:form` and `explode:true`
- `type:object` or `type:array` with `style:spaceDelimited`
- `type:object` or `type:array` with `style:pipeDelimited`

If everything else can be identified; then through a process of elimination, the ambiguous segment belongs to the _ambiguous parameter_

If an operation contains two or more _ambiguous parameters_, then there are multiple ways of interpreting the ambiguous segment.
This ambiguity means the query string cannot be resolved deterministically.
As such, it is not allowed.

## Looser Requirements

These requirements are looser than the OpenAPI Specification.
Where the OpenAPI Specification would be invalid, the reader will add a warning to the `Validated` object.

### MultipleOf Can Be Negative

Normally `multipleOf` MUST be a positive non-zero number.

The Reader allows `multipleOf` to be any non-zero number.
A multiple of a negative number is also a multiple of its absolute value.
It's more confusing, but what is expressed is identical.

Therefore, if a negative value is given:
- You will receive a Warning
- The absolute value will be used

### AllOf, AnyOf and OneOf Can Be Empty

Normally, `allOf`, `anyOf` and `oneOf` MUST be non-empty.

The Reader allows them to be empty.

[] and null express basically the same thing, no value.
By replacing null with [], we can narrow the typehint from `array|null` to `array`.
Simplifies code using it.

### Required Can Be Empty 

OpenAPI 3.0 states `required` MUST be non-empty.  
OpenAPI 3.1 allows `required` to be empty and defaults to empty if omitted. 

The Reader always allows `required` to be empty and defaults to empty if omitted.
This allows us to narrow the typehint for `required` to always being an array.

If an empty array is given:
- If your API is using 3.0, you will receive a Warning
- An empty array will be used

### Required Can Contain Duplicates

Normally `required` MUST contain unique values.

The Reader allows `required` to contain duplicates.

If a duplicate item is found:
- You will receive a Warning
- The duplicate will be removed.
