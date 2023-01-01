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
$router->register("/ping", function () {
    return "pong";
});

```

### Dispatching requests

Once your routes are registered, pass your request object (any PSR-7 compatible `RequestInterface`) to the router:

```php
$response = $router->dispatch($request);
```

This call will always return a PSR-7 compatible `ResponseInterface`.

If a matching route is found, your target function will be executed and its response will be returned. Your functions can return their own response object, or a string that will be served as a 200 OK response.

If there's a problem with the request, if the route was not found, or if your target function did not return anything, an error response is returned instead.
