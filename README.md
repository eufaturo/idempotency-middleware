## Idempotency Middleware for Laravel

[![GitHub Tests Action Status][icon-action-tests]][url-action-tests]
[![GitHub Code Analysis Action Status][icon-action-analysis]][url-action-analysis]
[![Software License][icon-license]][url-license]
[![Latest Version on Packagist][icon-packagist-version]][url-packagist]
[![Total Downloads][icon-packagist-downloads]][url-packagist]

## Introduction

Simple Laravel middleware for implementing idempotency in your API requests

## Installation

This library is installed via [Composer](https://getcomposer.org/) and to install, run the following command.

```bash
composer require eufaturo/idempotency-middleware
```

## Usage

Apply the middleware in your routes or route groups in your, here's a simple example:

```php
<?php

use Eufaturo\IdempotencyMiddleware\Idempotency;

Route::middleware(['auth:api', Idempotency::class])->group(function () {
    Route::post('/create-user', function () {
        // Create the user ...
    });
});
```

### Usage with HTTP Requests

To perform an idempotent request, provide an additional `Idempotency-Key` header through the request options where the value should be a valid UUID v4

Example:

```http request
POST /api/create-user HTTP/1.1
Content-Type: application/json
Idempotency-Key: 6b3fd36c-24c6-4eb2-a764-bb6c91b33e56

{
  "name": "John Doe",
  "email": "john.doe@example.com",
  "password: "secret"
}
```

## Testing

```shell
composer test
```

## Contributing

Thank you for your interest. Here are some of the many ways to contribute.

- Check out our [contributing guide](/.github/CONTRIBUTING.md)
- Look at our [code of conduct](/.github/CODE_OF_CONDUCT.md)

## License

This library is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

[url-action-tests]: https://github.com/eufaturo/idempotency-middleware/actions?query=workflow%3Atests
[url-action-analysis]: https://github.com/eufaturo/idempotency-middleware/actions?query=workflow%3Acode-analysis
[url-packagist]: https://packagist.org/packages/eufaturo/idempotency-middleware
[url-license]: https://opensource.org/licenses/MIT

[icon-action-tests]: https://github.com/eufaturo/idempotency-middleware/actions/workflows/tests.yml/badge.svg?branch=main
[icon-action-analysis]: https://github.com/eufaturo/idempotency-middleware/actions/workflows/code-analysis.yml/badge.svg?branch=main
[icon-license]: https://img.shields.io/github/license/eufaturo/idempotency-middleware?label=License
[icon-packagist-version]: https://img.shields.io/packagist/v/eufaturo/idempotency-middleware.svg?label=Packagist
[icon-packagist-downloads]: https://img.shields.io/packagist/dt/eufaturo/idempotency-middleware.svg?label=Downloads
