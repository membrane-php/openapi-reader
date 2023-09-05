# OpenAPI Reader

This library is intended to be used in conjunction with other Membrane libraries.

It wraps the [php-openapi](https://github.com/cebe/php-openapi) library with some additional validation, including 
Membrane-specific requirements.

## Requirements

- A valid [OpenAPI specification](https://github.com/OAI/OpenAPI-Specification#readme).
- An operationId on all [Operation Objects](https://spec.openapis.org/oas/v3.1.0#operation-object) so that each route is uniquely identifiable.

## Installation

```text
composer require membrane/openapi-router
```

## Quick Start

### Instantiate a Reader

```php
$versions = [\Membrane\OpenAPIReader\OpenAPIVersion::Version_3_0];

$reader = new \Membrane\OpenAPIReader\Reader($versions);
```

### Read From An Absolute File Path

This method is the main use-case of the reader and is capable of _resolving all references._

If your file path contains the file extension then the reader can use this to determine which language the OpenAPI is written in.

```php
// code to instantiate reader... 

$reader->readFromAbsoluteFilePath('~/path/to/my-openapi.yaml');
```

Otherwise, you may ensure it reads the file as a specific format by providing a second argument:

```php
// code to instantiate reader... 

$fileFormat = \Membrane\OpenAPIReader\FileFormat::Json;

$reader->readFromAbsoluteFilePath('my-openapi', $fileFormat);
```

### Read From A String

This method is only capable of resolving 
[Reference Objects](https://spec.openapis.org/oas/v3.1.0#reference-object-example), 
it cannot resolve references to 
[Relative Documents](https://spec.openapis.org/oas/v3.1.0#relative-schema-document-example)
.

Because the OpenAPI will be read from a string, the FileFormat MUST be provided.

```php
// code to instantiate reader... 

$myOpenAPI = '<Insert your OpenAPI Spec here>';
$fileFormat = \Membrane\OpenAPIReader\FileFormat::Json;

$reader->readFromString($myOpenAPI, $fileFormat)
```
