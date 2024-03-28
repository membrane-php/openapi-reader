# Validation Performed By OpenAPI Reader

## Additional Requirements For Membrane.

### Specify an OperationId

Membrane requires all Operations to set a [unique](https://github.com/OAI/OpenAPI-Specification/blob/3.1.0/versions/3.0.3.md#fixed-fields-8) `operationId`.

This is used for identification of all available operations across your OpenAPI.

### Unambiguous Query Strings

For query parameters (i.e. `in:query`)  with a `schema` that allows compound types i.e. `array` or `objects`
there are certain combinations of `style` and `explode` that do not use the parameter's `name`.

These combinations are:
- `type:object` with `style:form` and `explode:true`
- `type:object` or `type:array` with `style:spaceDelimited`
- `type:object` or `type:array` with `style:pipeDelimited`

If an operation only has one query parameter (i.e. `in:query`) then this is fine. Membrane can safely assume the entire string belongs to that one parameter.

If an operation contains two query parameters, both of which do not use the parameter's name; Membrane cannot ascertain which parameter relates to which part of the query string.

This ambiguity leads to multiple "correct" ways to interpret the query string. Making it impossible to safely assume Membrane has validated it. Therefore, only one parameter, with one of the above combinations, is allowed on any given Operation.

## Version 3.0.X

### OpenAPI Object

- [An OpenAPI Object requires an `openapi` field](https://github.com/OAI/OpenAPI-Specification/blob/3.1.0/versions/3.0.3.md#fixed-fields).
- [An OpenAPI Object requires an `info` field](https://github.com/OAI/OpenAPI-Specification/blob/3.1.0/versions/3.0.3.md#fixed-fields).
  - [The Info Object requires a `title`](https://github.com/OAI/OpenAPI-Specification/blob/3.1.0/versions/3.0.3.md#fixed-fields-1).
  - [The Info Object requires a `version`](https://github.com/OAI/OpenAPI-Specification/blob/3.1.0/versions/3.0.3.md#fixed-fields-1).
- [All Path Items must be mapped to by their relative endpoint](https://github.com/OAI/OpenAPI-Specification/blob/3.1.0/versions/3.0.3.md#paths-object).
### Path Item

- [All Operations MUST be mapped to by a method](https://github.com/OAI/OpenAPI-Specification/blob/3.1.0/versions/3.0.3.md#fixed-fields-7).
- [Parameters must be unique. Uniqueness is defined by a combination of "name" and "in".](https://github.com/OAI/OpenAPI-Specification/blob/3.1.0/versions/3.0.3.md#fixed-fields-7)

### Operation

- [Parameters must be unique. Uniqueness is defined by a combination of "name" and "in".](https://github.com/OAI/OpenAPI-Specification/blob/3.1.0/versions/3.0.3.md#fixed-fields-8)

### Parameter

- A Parameter [MUST contain a `name` field](https://github.com/OAI/OpenAPI-Specification/blob/3.1.0/versions/3.0.3.md#fixed-fields-10).
- A Parameter [MUST contain an `in` field](https://github.com/OAI/OpenAPI-Specification/blob/3.1.0/versions/3.0.3.md#fixed-fields-10).
  - `in` [MUST be set to `path`, `query`, `header` or `cookie`](https://github.com/OAI/OpenAPI-Specification/blob/3.1.0/versions/3.0.3.md#fixed-fields-10).
  - [if `in:path` then the Parameter MUST specify `required:true`](https://github.com/OAI/OpenAPI-Specification/blob/3.1.0/versions/3.0.3.md#fixed-fields-10).
  - if `style` is specified, [acceptable values depend on the value of `in`](https://github.com/OAI/OpenAPI-Specification/blob/3.1.0/versions/3.0.3.md#style-values). 
- [A Parameter MUST contain a `schema` or `content`, but not both](https://github.com/OAI/OpenAPI-Specification/blob/3.1.0/versions/3.0.3.md#fixed-fields-10).
  - if `content` is specified, it MUST contain exactly one Media Type
    - A Parameter's MediaType MUST contain a schema.

### Schema

- [If allOf, anyOf or oneOf are set; They MUST not be empty](https://json-schema.org/draft/2020-12/json-schema-core#section-10.2).
