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

    public function __toString()
    {
        return $this->contents;
    }

    public function close()
    {
    }

    public function detach()
    {
        return null;
    }

    public function getSize()
    {
        return null;
    }

    public function tell()
    {
        return 0;
    }

    public function eof()
    {
        return true;
    }

    public function isSeekable()
    {
        return false;
    }

    public function seek($offset, $whence = SEEK_SET)
    {
        return new \RuntimeException('no seek');
    }

    public function rewind()
    {
    }

    public function isWritable()
    {
        return false;
    }

    public function write($string)
    {
        return new \RuntimeException('no write');
    }

    public function isReadable()
    {
        return false;
    }

    public function read($length)
    {
        return new \RuntimeException('no read');
    }

    public function getContents()
    {
        return $this->contents;
    }

    public function getMetadata($key = null)
    {
        return null;
    }
}