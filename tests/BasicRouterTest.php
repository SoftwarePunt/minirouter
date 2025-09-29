<?php

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use SoftwarePunt\MiniRouter\MiniRouter;

class BasicRouterTest extends TestCase
{
    // -----------------------------------------------------------------------------------------------------------------
    // Core tests

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

    public function testInconsistentVariableRouteThrows()
    {
        $router = new MiniRouter();

        $router->register('/callable-route/$urlVar', function (RequestInterface $request, string $urlVar): ResponseInterface {
            return new Response(body: "echo var: {$urlVar}");
        });

        $this->expectExceptionMessage("Inconsistent route variable");
        $router->register('/callable-route/$notSameUrlVar', function (RequestInterface $request, string $urlVar): ResponseInterface {
            return new Response(body: "echo var: {$urlVar}");
        });
    }

    public function testDependencyInjection()
    {
        $router = new MiniRouter();

        $router->register('/callable-route/$urlVar', function (string $urlVar, RequestInterface $nonReqVarName, int $someOtherVar = 123) {
            $this->assertSame("var-input-val", $urlVar, "Specific value for URL mapped variable should be passed");
            $this->assertInstanceOf(RequestInterface::class, $nonReqVarName, "Request should be injected even if var name is inconsistent");
            $this->assertSame(123, $someOtherVar, "Default value for non-context variable should be passed");
        });

        $request = new Request("GET", "https://somehost.com/callable-route/var-input-val");
        $router->dispatch($request);
    }

    public function test302Redirect()
    {
        $router = new MiniRouter();
        $router->registerRedirect('/one/$somevar', '/two');

        $request = new Request("GET", "https://somehost.com/one/blabla");
        $response = $router->dispatch($request);

        $this->assertEquals(302, $response->getStatusCode(),
            "Default redirect should result in HTTP 302 redirect");
        $this->assertEquals("Found", $response->getReasonPhrase(),
            "Default redirect should result in Found redirect");
        $this->assertEquals("/two", $response->getHeaderLine('Location'),
            "Redirect URL should match");
    }

    public function test301Redirect()
    {
        $router = new MiniRouter();
        $router->registerRedirect('/one/$somevar', '/two', true);

        $request = new Request("GET", "https://somehost.com/one/blabla");
        $response = $router->dispatch($request);

        $this->assertEquals(301, $response->getStatusCode(),
            "Permanent redirect should result in HTTP 301 redirect");
        $this->assertEquals("Moved Permanently", $response->getReasonPhrase(),
            "Permanent redirect should result in Moved Permanently redirect");
        $this->assertEquals("/two", $response->getHeaderLine('Location'),
            "Redirect URL should match");
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Code samples from docs

    public function testDocRegisteringRoutes()
    {
        $router = new MiniRouter();

        $router->register('/ping', function () {
            return 'pong';
        });

        $response = $router->dispatch(new Request("GET", "https://somehost.com/ping"));

        $this->assertSame("pong", (string)$response->getBody());
    }

    public function testDocAccessingTheRequest()
    {
        $router = new MiniRouter();

        $router->register('/show-user-agent', function (RequestInterface $request) {
            return "Your user agent is: {$request->getHeaderLine('User-Agent')}";
        });

        $response = $router->dispatch(new Request("GET", "https://somehost.com/show-user-agent", [
            "User-Agent" => "some/user/agent (123)"
        ]));

        $this->assertSame("Your user agent is: some/user/agent (123)", (string)$response->getBody());
    }

    public function testDocRoutesWithVariables()
    {
        $router = new MiniRouter();

        $router->register('/echo/$myUrlVar/$varTwo', function (string $myUrlVar, string $varTwo) {
            return "echo: {$myUrlVar} - {$varTwo}";
        });

        $response = $router->dispatch(new Request("GET", "https://somehost.com/echo/my_var_value/var2val"));

        $this->assertSame("echo: my_var_value - var2val", (string)$response->getBody());
    }
}