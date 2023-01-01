# MiniRouter
**A fast and lightweight request-response router for PHP, compatible with [PSR-7](https://www.php-fig.org/psr/psr-7/).**

## Installation
Install the package using [Composer](https://getcomposer.org/):

```bash
composer require softwarepunt/minirouter
```

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

### Routes with variables
When you register your routes, you can also use variables:

```php
$router->register('/echo/$myUrlVar', function (string $myUrlVar) {
    return "echo: {$myUrlVar}";
});
```

The variable name in your route definition will be passed as a named argument (string) to your target function, where you can access it directly.