<?php

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use SoftwarePunt\MiniRouter\MiniRouter;

class ControllerRouterTest extends TestCase
{
    public function testBasicControllerRouting()
    {
        $router = new MiniRouter();
        $router->registerController("/test", ControllerRouterTest_Impl::class, "targetFn");

        $response = $router->dispatch(new Request("GET", "https://somehost.com/test"));

        $this->assertInstanceOf(ResponseInterface::class, $response,
            "dispatch() should always return a ResponseInterface");
        $this->assertSame("hello no one", (string)$response->getBody(),
            "dispatch() should return controller target function response");

        $this->assertTrue(ControllerRouterTest_Impl::$lastTargetInstance->beforeDidRun,
            "before() should be called before target function");
    }

    public function testVariableControllerRouting()
    {
        $router = new MiniRouter();
        $router->registerController('/test/$name', ControllerRouterTest_Impl::class, "targetFn");

        $response = $router->dispatch(new Request("GET", "https://somehost.com/test/some_name"));

        $this->assertInstanceOf(ResponseInterface::class, $response,
            "dispatch() should always return a ResponseInterface");
        $this->assertSame("hello some_name", (string)$response->getBody(),
            "dispatch() should return controller target function response with injected variable");

        $this->assertTrue(ControllerRouterTest_Impl::$lastTargetInstance->beforeDidRun,
            "before() should be called before target function");
    }

    public function testControllerRoutingWithBeforeResponse()
    {
        $router = new MiniRouter();
        $router->registerController('/test/$name', ControllerRouterTest_Impl::class, "targetFn");

        $response = $router->dispatch(new Request("GET", "https://somehost.com/test/test_condition_bailout"));

        $this->assertInstanceOf(ResponseInterface::class, $response,
            "dispatch() should always return a ResponseInterface");
        $this->assertSame("unacceptable name, before bailout", (string)$response->getBody(),
            "dispatch() should return before() response if one is given");
    }
}

class ControllerRouterTest_Impl
{
    public bool $beforeDidRun = false;
    public static ?ControllerRouterTest_Impl $lastTargetInstance = null;

    public function before(RequestInterface $req, ?string $name = null): ?ResponseInterface
    {
        TestCase::assertNotNull($req);

        $this->beforeDidRun = true;

        if ($name === "test_condition_bailout") {
            return new Response(status: 400, body: "unacceptable name, before bailout");
        }

        return null;
    }

    public function targetFn(RequestInterface $req, string $name = "no one")
    {
        TestCase::assertNotNull($req);

        self::$lastTargetInstance = $this;
        return "hello {$name}";
    }
}