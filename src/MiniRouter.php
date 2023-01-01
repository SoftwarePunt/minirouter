<?php

namespace SoftwarePunt\MiniRouter;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use SoftwarePunt\MiniRouter\HTTP\FallbackResponse;

/**
 * Manages the registration of routes and dispatching of HTTP requests to responses.
 */
class MiniRouter
{
    // -----------------------------------------------------------------------------------------------------------------
    // Register

    /**
     * The routing table, resolves paths to callables.
     * This is a recursive array, with each step representing a level of path-based routing.
     *
     * Keys represent sublevels, and match the path.
     * Key "_" always resolves to a final closure and represents a routing result.
     * Key "$" denotes a variable sublevel name.
     *
     * Example:
     *  When registering a route "/users/$id/edit", the result is as follows:
     *
     *  ["users" => [
     *    "$" => [
     *      "edit" => [
     *        "_" => [RouteTarget for GET, RouteTarget for POST, ...]
     *      ]
     *    ]
     *  ]
     *
     * @var callable[]
     */
    protected array $routes = [];

    /**
     * Register a new route to a callable.
     *
     * @param string $path The path of the request URI, optionally with variables, e.g. "/user/$id/edit".
     * @param callable $callable
     * @return $this
     */
    public function register(string $path, callable $callable): self
    {
        $pathParts = explode('/', $path);
        array_shift($pathParts); // First item in path parts should be an empty string because of the "/"

        $routesStep = &$this->routes;

        foreach ($pathParts as $pathPart) {
            $isVariablePart = (str_starts_with($pathPart, '$'));

            if ($isVariablePart) {
                if (!isset($routesStep['$'])) {
                    $routesStep['$'] = [];
                }

                $routesStep['$']['__name'] = substr($pathPart, 1); // variable name without $
                $routesStep = &$routesStep['$'];
            } else {
                if (!isset($routesStep[$pathPart])) {
                    $routesStep[$pathPart] = [];
                }

                $routesStep = &$routesStep[$pathPart];
            }
        }

        $routesStep['_'] = $callable;
        return $this;
    }

    /**
     * Register a new route with a controller class as target.
     *
     * @param string $path The path of the request URI, optionally with variables, e.g. "/user/$id/edit".
     * @param string $controllerClass Class name of the controller to instantiate.
     * @param string $methodName Method name to call on the controller instance.
     * @return $this
     */
    public function registerController(string $path, string $controllerClass, string $methodName): self
    {
        return $this->register($path, [$controllerClass, $methodName]);
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Routing

    protected function route(string $path, array &$_variables): ?callable
    {
        if ($path !== "/" && str_ends_with($path, '/')) {
            // Remove trailing slash if we have one
            $path = substr($path, 0, -1);
        }

        $pathParts = explode('/', $path);

        // First item in path parts should be an empty string because of the "/", so remove it now.
        //  (NB: HttpRequest will never give us a path that does not start with "/".)
        array_shift($pathParts);

        $routesStep = $this->routes;

        foreach ($pathParts as $pathPart) {
            $exactMatch = $routesStep[$pathPart] ?? null;
            $variableMatch = $routesStep["$"] ?? null;

            if ($exactMatch || $variableMatch) {
                // We got a matching group, so continue but prefer exact matches
                if ($exactMatch) {
                    $routesStep = $exactMatch;
                } else {
                    $routesStep = $variableMatch;
                    $_variables[$routesStep['__name']] = $pathPart;
                }
                continue;
            }

            // Neither exact nor variable match; hard routing failure
            return null;
        }

        if (!isset($routesStep["_"]))
            return null;

        return self::createCallableFromRouteTarget($routesStep["_"]);
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Dispatch

    public function dispatch(RequestInterface $request): ResponseInterface
    {
        // Validate
        if (!$this->validateRequest($request)) {
            return new FallbackResponse(400, 'Bad request');
        }

        // Route
        $_variables = ['request' => $request];
        $callable = $this->route($request->getUri()->getPath(), $_variables);

        if (!$callable) {
            // No route found, 404
            return new FallbackResponse(404, "Not Found");
        }

        $isControllerCall = is_array($callable);
        if ($isControllerCall) {
            // "before" call on controller
            $beforeCallable = [$callable[0], 'before'];
            if (is_callable($beforeCallable)) {
                $beforeResult = call_user_func_array($beforeCallable, [$request]);
                if ($beforeResult instanceof ResponseInterface) {
                    // The before function returned an early response, do not proceed
                    return $beforeResult;
                } else if ($beforeResult === false) {
                    // The before function returned false, abort execution (generic fallback)
                    return new FallbackResponse(500, "Controller precondition failed");
                }
            }
        }

        // Execute
        $result = call_user_func_array($callable, $_variables);
        if ($result instanceof ResponseInterface) {
            // The target function returned a response of its own
            return $result;
        }

        // The target function returned something that wasn't a response, so we'll present it as one
        return new FallbackResponse(200, "OK", $result ? strval($result) : null);
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Util

    private function validateRequest(RequestInterface $request): bool
    {
        if (!$request->hasHeader("Host")) {
            // Sometimes we get invalid requests without a "Host" header, drop them
            return false;
        }

        return true;
    }

    private static function createCallableFromRouteTarget(array|callable $routeTarget): callable
    {
        if (is_callable($routeTarget))
            return $routeTarget;

        $controllerClass = $routeTarget[0];
        $methodName = $routeTarget[1];

        if (!class_exists($controllerClass, autoload: true))
            throw new \RuntimeException("Route target class does not exist: {$controllerClass}");

        $controllerInstance = new $controllerClass();

        if (!method_exists($controllerInstance, $methodName))
            throw new \RuntimeException("Route target method does not exist: {$controllerClass}->{$methodName}");

        return [$controllerInstance, $methodName];
    }
}