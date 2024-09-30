<?php

namespace SoftwarePunt\MiniRouter\HTTP;

use Psr\Http\Message\StreamInterface;

class FallbackStringStream implements StreamInterface
{
    public string $contents;

    public function __construct(?string $contents)
    {
        $this->contents = $contents ?? "";
    }

    public function __toString(): string
    {
        return $this->contents;
    }

    public function close(): void
    {
    }

    public function detach()
    {
        return null;
    }

    public function getSize(): ?int
    {
        return null;
    }

    public function tell(): int
    {
        return 0;
    }

    public function eof(): bool
    {
        return true;
    }

    public function isSeekable(): bool
    {
        return false;
    }

    public function seek($offset, $whence = SEEK_SET): void
    {
        throw new \RuntimeException('no seek');
    }

    public function rewind(): void
    {
    }

    public function isWritable(): bool
    {
        return false;
    }

    public function write($string): int
    {
        throw new \RuntimeException('no write');
    }

    public function isReadable(): bool
    {
        return false;
    }

    public function read($length): string
    {
        return new \RuntimeException('no read');
    }

    public function getContents(): string
    {
        return $this->contents;
    }

    public function getMetadata($key = null)
    {
        return null;
    }
}