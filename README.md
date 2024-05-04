# Simple Logger

[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/zeal/zeal-logger/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/zeal/zeal-logger/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/zeal/zeal-logger/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/zeal/zeal-logger/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)

## Index
- [About](#about)
- [Installation](#installation)
- [Usage](#usage)
    - [Adding the Logger Middleware](#1-adding-the-logger-middleware)
    - [Logging Loggable Entities](#2-logging-loggable-entities)
    - [Logging Exceptions](#3-logging-exceptions)
- [Testing](#testing)
- [Changelog](#changelog)
- [Contributing](#contributing)
- [Security Vulnerabilities](#security-vulnerabilities)
- [Credits](#credits)
- [License](#license)

## About
Simple Logger is a simple API logger that efficiently logs all requests and responses to your app.

## Installation

You can install the package via composer:

```bash
composer require zeal/logger
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="zeal-logger-migrations"
php artisan migrate
```

## Usage

### 1. Adding the Logger Middleware
Add the `Simple\Logger\Core\Middleware\LoggerMiddleware` middleware to the `api` middleware group in your `app/Http/Kernel.php` file. It's recommended to add it as the first middleware in the group to ensure it logs all requests and responses.

```php
protected $middlewareGroups = [
    'api' => [
        \Simple\Logger\Core\Middleware\LoggerMiddleware::class,
        // ...
    ],
];
```

### 2. Logging Loggable Entities
In your app, you may have different entities that you want to log, such as app entities or user entities. To associate logs with these entities, call the `logLoggableEntity` method on the `Simple\Logger\LoggerService` class, passing the entity as the first argument. For example, in a middleware that identifies a POS System calling your API:

```php
use Simple\Logger\LoggerService;

class IdentifyPosSystem
{
    public function handle($request, Closure $next)
    {
        // Some logic to identify the POS System calling the API
        
        LoggerService::safeCall(fn() => LoggerService::logLoggableEntity($posSystem));
        
        return $next($request);
    }
}
```

### 3. Logging Exceptions
- If an exception occurs in the request and response cycle, log it using the `log` method on the `Simple\Logger\LoggerService` class, passing the exception as the first argument.
- Alternatively, bind the Logger in your `App\Exceptions\Handler` class and call the `log` method to log exceptions. Ensure to use the `safeCall` function to prevent re-throwing the exception.

```php
public function report(Throwable $e)
{
    $this->reportable(function (Throwable $e) {
        if (app()->bound(LoggerService::class)){
            LoggerService::safeCall(fn() => LoggerService::log($e));
        }
    });
    parent::report($e);
}
```

Now, you can check the `api_logs` table in your database to view the logs.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Eyad Hamza](https://github.com/Eyadhamza)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

---

Happy 1st birthday to Simple Logger! 🎉🎂
