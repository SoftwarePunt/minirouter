# MiniRouter

[![PHPUnit](https://github.com/SoftwarePunt/minirouter/actions/workflows/php.yml/badge.svg)](https://github.com/SoftwarePunt/minirouter/actions/workflows/php.yml)

**A fast and lightweight router for PHP, compatible with [PSR-7](https://www.php-fig.org/psr/psr-7/):**

- ⏩ Super fast request-response routing
- 🌟 Static or dynamic routes with unlimited URL variables
- 💉 Automatic parameter injection for requests and route variables

## Installation
Install the package using [Composer](https://getcomposer.org/):

```bash
composer require softwarepunt/minirouter
```

This package is compatible with PHP 8.4+.

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

### Incoming requests (PSR-7)
You will need a [PSR-7 implementation](https://packagist.org/providers/psr/http-message-implementation), such as [`guzzlehttp/psr7`](https://packagist.org/packages/guzzlehttp/psr7), to provide incoming request data. For example:

```php
use GuzzleHttp\Psr7\ServerRequest;

$request = ServerRequest::fromGlobals();
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

### Routing to (controller) classes
You can also register routes that will construct a class instance, and invoke a specific method. This can help you organize your code, and is more typical for a model-view-controller (MVC) architecture.

```php
<?php

use SoftwarePunt\MiniRouter\MiniRouter;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

$router = new MiniRouter();
$router->registerController('/greet/$name', SomeControllerClass::class, "targetMethod");

class SomeControllerClass
{
    public function before(RequestInterface $request): ?ResponseInterface
    {
        // This method will be called *before* routing to the target method
        // You can access the request and other variables here
        if (!exampleAuthCheck())
            return new Response(403, body: "Access denied!");
        // If before() returns a response, routing is aborted and that response is returned
        // This is a good place to handle things like pre-flight checks and authentication
        return null;
    }

    public function targetMethod(string $name): ResponseInterface
    {
        return new Response(200, body: "Hello, {$name}!");
    }
}
```

You can use the optional `before` method to perform pre-flight checks and run common code - for example, to handle authentication before allowing routing to proceed.

### Simple redirects
You can also use the `registerRedirect` utility function to quickly register HTTP 301 or 302 redirects:

```php
// 301 Moved Permanently
$router->registerRedirect('/from', '/to', true);
// 302 Found (Temporary redirect) 
$router->registerRedirect('/from', '/to');
```

## Examples

### Complete `index.php`

```php
<?php

use MyProject\HomeController;
use GuzzleHttp\Psr7\ServerRequest;
use SoftwarePunt\MiniRouter\MiniRouter;

// ---------------------------------------------------------------------------------------------------------------------
// Init

require_once "../bootstrap.php";

$router = new MiniRouter();

// ---------------------------------------------------------------------------------------------------------------------
// Routes

$router->registerController('/', HomeController::class, 'serveHome');

// ---------------------------------------------------------------------------------------------------------------------
// Main

$request = ServerRequest::fromGlobals();
$response = $router->dispatch($request);

// ---------------------------------------------------------------------------------------------------------------------
// Serve

http_response_code($response->getStatusCode());

foreach ($response->getHeaders() as $name => $headerContent) {
    if (is_array($headerContent)) {
        foreach ($headerContent as $value) {
            header(sprintf('%s: %s', $name, $value), false);
        }
    } else {
        header(sprintf('%s: %s', $name, $headerContent), false);
    }
}

echo $response->getBody()->getContents();
exit(0);
```
