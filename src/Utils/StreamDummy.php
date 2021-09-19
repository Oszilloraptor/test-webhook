<?php declare(strict_types=1);
namespace Rikta\TestWebhook\Utils;

use Psr\Http\Message\StreamInterface;
use RuntimeException;
use const SEEK_SET;

/**
 * StreamInterface around a string
 * Used to replace Streams around resources (e.g. php:://input) that would get killed by Serializing
 * @internal
 */
class StreamDummy implements StreamInterface
{
    private string $content;
    private array $metaData;

    public function __construct(string $content, array $metaData = [])
    {
        $this->content = $content;
        $this->metaData = $metaData;
    }

    /**
     * @inheritDoc
     */
    public function close(): void
    {
    }
    /**
     * @inheritDoc
     */
    public function detach(): void
    {
    }
    /**
     * @inheritDoc
     */
    public function getSize(): int
    {
        return mb_strlen($this->content);
    }
    /**
     * @inheritDoc
     */
    public function tell(): int
    {
        return $this->getSize();
    }
    /**
     * @inheritDoc
     */
    public function eof(): bool
    {
        return true;
    }
    /**
     * @inheritDoc
     */
    public function isSeekable(): bool
    {
        return false;
    }
    /**
     * @inheritDoc
     */
    public function seek($offset, $whence = SEEK_SET): void
    {
        throw new RuntimeException();
    }
    /**
     * @inheritDoc
     */
    public function rewind(): void
    {
        throw new RuntimeException();
    }
    /**
     * @inheritDoc
     */
    public function isWritable(): bool
    {
        return false;
    }
    /**
     * @inheritDoc
     */
    public function write($string): void
    {
        throw new RuntimeException();
    }
    /**
     * @inheritDoc
     */
    public function isReadable(): bool
    {
        return false;
    }
    /**
     * @inheritDoc
     */
    public function read($length): void
    {
        throw new RuntimeException();
    }
    /**
     * @inheritDoc
     */
    public function getContents(): string
    {
        return $this->content;
    }
    /**
     * @inheritDoc
     */
    public function getMetadata($key = null): ?array
    {
        return $key === null ? null : $this->metaData;
    }
    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return $this->content;
    }
}