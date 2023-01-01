<?php

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use SoftwarePunt\MiniRouter\MiniRouter;

class RouterTest extends TestCase
{
    public function testMissingHostHeader400()
    {
        $router = new MiniRouter();

        $request = new Request("GET", "/some-path");
        $response = $router->dispatch($request);

        $this->assertInstanceOf(ResponseInterface::class, $response,
            "dispatch() should always return a ResponseInterface");
        $this->assertSame(400, $response->getStatusCode(),
            "dispatch() should return 400 (Bad Request) if Host is missing from request");
    }

    public function testEmptyRouter404()
    {
        $router = new MiniRouter();

        $request = new Request("GET", "https://somehost.com/some-path");
        $response = $router->dispatch($request);

        $this->assertInstanceOf(ResponseInterface::class, $response,
            "dispatch() should always return a ResponseInterface");
        $this->assertSame(404, $response->getStatusCode(),
            "dispatch() should always return 404 (Not Found) if no routes are registered");
        $this->assertSame("Not Found", $response->getReasonPhrase(),
            "dispatch() should always return 404 (Not Found) if no routes are registered");
    }

    public function testSimpleCallableRouting()
    {
        $router = new MiniRouter();

        $router->register("/callable-route", function (RequestInterface $request): ResponseInterface {
            return new Response(body: "simple response");
        });

        $request = new Request("GET", "https://somehost.com/callable-route");
        $response = $router->dispatch($request);

        $this->assertInstanceOf(ResponseInterface::class, $response,
            "dispatch() should always return a ResponseInterface");
        $this->assertSame(200, $response->getStatusCode(),
            "dispatch() should return implementation response result");
        $this->assertSame("simple response", $response->getBody()->__toString(),
            "dispatch() should return implementation response body");
    }

    public function testCallableStringReturnPingPong()
    {
        $router = new MiniRouter();

        $router->register("/ping", function (RequestInterface $request) {
            return "pong";
        });

        $request = new Request("GET", "https://somehost.com/ping");
        $response = $router->dispatch($request);

        $this->assertInstanceOf(ResponseInterface::class, $response,
            "dispatch() should always return a ResponseInterface");
        $this->assertSame(200, $response->getStatusCode(),
            "dispatch() should return 200 (OK) for string return");
        $this->assertSame("pong", $response->getBody()->__toString(),
            "dispatch() should return string return in response body");
    }

    public function testVariableCallableRouting()
    {
        $router = new MiniRouter();

        $router->register('/callable-route/$urlVar', function (RequestInterface $request, string $urlVar): ResponseInterface {
            return new Response(body: "echo var: {$urlVar}");
        });

        $request = new Request("GET", "https://somehost.com/callable-route/var_value_from_request");
        $response = $router->dispatch($request);

        $this->assertInstanceOf(ResponseInterface::class, $response,
            "dispatch() should always return a ResponseInterface");
        $this->assertSame(200, $response->getStatusCode(),
            "dispatch() should return implementation response result");
        $this->assertSame("echo var: var_value_from_request", $response->getBody()->__toString(),
            "dispatch() should return implementation response body (based on variable request)");

        $request = new Request("GET", "https://somehost.com/callable-route/other_var_value");
        $response = $router->dispatch($request);

        $this->assertSame("echo var: other_var_value", $response->getBody()->__toString(),
            "dispatch() should return implementation response body (based on variable request)");
    }
}