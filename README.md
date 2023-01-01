# MiniRouter
**A fast and lightweight router for PHP, compatible with [PSR-7](https://www.php-fig.org/psr/psr-7/):**

- ⏩ Super fast request-response routing
- 🌟 Static or dynamic routes with unlimited URL variables
- 💉 Automatic parameter injection for requests and route variables

## Installation
Install the package using [Composer](https://getcomposer.org/):

```bash
composer require softwarepunt/minirouter
```

This package is compatible with PHP 8.2+.

## Usage

### Registering routes
Initialize a new instance of `MiniRouter` and start registering routes:

```php
<?php

use SoftwarePunt\MiniRouter\MiniRouter;

$router = new MiniRouter();
$router->register('/ping', function () {
    return 'pong';
});

```

### Dispatching requests

Once your routes are registered, pass your request object (any PSR-7 compatible `RequestInterface`) to the router:

```php
$response = $router->dispatch($request);
```

This call will return a PSR-7 compatible `ResponseInterface`.

If a matching route is found, your target function will be executed and its response will be returned. It can return its own response object, or a string.

### Accessing the request
When your target function is called, you can ask for an instance of `RequestInterface` to access the request directly:

```php
use Psr\Http\Message\RequestInterface;

$router->register('/show-user-agent', function (RequestInterface $request) {
    return "Your user agent is: {$request->getHeaderLine('User-Agent')}";
});
```

The request object will be injected automatically. The name and order of the parameter doesn't matter.

### Routes with variables
When you register your routes, you can also use one or more URL variables that can then be injected into your target function:

```php
$router->register('/echo/$myUrlVar/$varTwo', function (string $myUrlVar, string $varTwo) {
    return "echo: {$myUrlVar} - {$varTwo}";
});
```

Variables are defined in the route by using the `$` prefix. Their values are automatically extracted from the request URL, and injected into your target function as named parameters (strings).
