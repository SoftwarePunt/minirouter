<?php

namespace SoftwarePunt\MiniRouter\HTTP;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class FallbackResponse implements ResponseInterface
{
    private int $statusCode;
    private string $reasonPhrase;
    private FallbackStringStream $body;
    private string $protocolVersion;
    private array $headers;

    public function __construct(int $statusCode = 200, string $reasonPhrase = "OK", ?string $body = null)
    {
        $this->statusCode = $statusCode;
        $this->reasonPhrase = $reasonPhrase;
        $this->body = new FallbackStringStream($body);
        $this->protocolVersion = "1.1";
        $this->headers = [];
    }

    public function getProtocolVersion()
    {
        return $this->protocolVersion;
    }

    public function withProtocolVersion($version)
    {
        $instance = clone $this;
        $instance->protocolVersion = $version;
        return $instance;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function hasHeader($name)
    {
        return $this->getHeader($name) !== null;
    }

    public function getHeader($name)
    {
        $caseMapping = array_change_key_case($this->headers, CASE_LOWER);
        $value = $caseMapping[strtolower($name)] ?? null;
        if ($value)
            return [0 => $value];
        return [];
    }

    public function getHeaderLine($name)
    {
        return $this->getHeader($name)[0] ?? null;
    }

    public function withHeader($name, $value)
    {
        $instance = clone $this;
        $instance->headers[$name] = $value;
        return $instance;
    }

    public function withAddedHeader($name, $value)
    {
        $instance = clone $this;
        $instance->headers[$name] = $value;
        return $instance;
    }

    public function withoutHeader($name)
    {
        $instance = clone $this;
        unset($instance->headers[$name]);
        return $instance;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function withBody(StreamInterface $body)
    {
        $instance = clone $this;
        $instance->body = new FallbackStringStream($body->__toString());
        return $instance;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function withStatus($code, $reasonPhrase = '')
    {
        $instance = clone $this;
        $instance->statusCode = $code;
        $instance->reasonPhrase = $reasonPhrase;
        return $instance;
    }

    public function getReasonPhrase()
    {
        return $this->reasonPhrase;
    }
}