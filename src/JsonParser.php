<?php

namespace Coco\JsonParser;

use Coco\JsonParser\Decoders\DecodedValue;
use Coco\JsonParser\Decoders\Decoder;
use Coco\JsonParser\Exceptions\SyntaxException;
use Coco\JsonParser\Pointers\Pointer;
use Coco\JsonParser\Sources\AnySource;
use Coco\JsonParser\Tokens\Lexer;
use Coco\JsonParser\Tokens\Parser;
use Coco\JsonParser\ValueObjects\Config;
use Coco\JsonParser\ValueObjects\Progress;
use Closure;
use Exception;
use IteratorAggregate;
use Traversable;

/**
 * The JSON parser entry-point.
 *
 * @implements IteratorAggregate<string|int, mixed>
 */
final class JsonParser implements IteratorAggregate
{
    /**
     * The configuration.
     *
     * @var Config $config
     */
    private $config;

    /**
     * The lexer.
     *
     * @var Lexer $lexer
     */
    private $lexer;

    /**
     * The parser.
     *
     * @var Parser $parser
     */
    private $parser;

    /**
     * @var mixed $source
     */
    public $source;

    /**
     * Instantiate the class statically.
     *
     * @param mixed $source
     *
     * @return self
     * @throws SyntaxException
     */
    public static function parse($source): self
    {
        return new self($source);
    }

    /**
     * Instantiate the class.
     *
     * @param mixed|null $source
     * @throws SyntaxException|Exception
     */
    public function __construct($source = null)
    {
        $this->config = new Config();
        $this->init($source);
    }

    /**
     * Initializer.
     *
     * @param mixed|null $source
     *
     * @return void
     * @throws Exception
     */
    private function init($source) : void
    {
        if (is_array($source)) {
            $source = json_encode($source);
        }

        if (is_string($source)) {
            $source = htmlspecialchars_decode($source);
        }

        $this->source = $source;

        $this->lexer = new Lexer(new AnySource($source, $this->config));
        $this->parser = new Parser($this->lexer->getIterator(), $this->config);
    }

    /**
     * Retrieve the lazily iterable JSON.
     *
     * @return Traversable<string|int, mixed>
     */
    public function getIterator(): Traversable
    {
        try {
            yield from $this->parser;
        } catch (SyntaxException $e) {
            $e->setPosition($this->lexer->position());
            ($this->config->onSyntaxError)($e);
        }
    }

    /**
     * Set the JSON pointers.
     *
     * @param string[]|array<string, Closure> $pointers
     *
     * @return self
     * @throws Exceptions\IntersectingPointersException
     * @throws Exceptions\InvalidPointerException
     */
    public function pointers(array $pointers): self
    {
        foreach ($pointers as $pointer => $callback) {
            $callback instanceof Closure ? $this->pointer($pointer, $callback) : $this->pointer($callback);
        }

        return $this;
    }

    /**
     * Set a JSON pointer.
     *
     * @param string       $pointer
     * @param Closure|null $callback
     *
     * @return self
     * @throws Exceptions\IntersectingPointersException
     * @throws Exceptions\InvalidPointerException
     */
    public function pointer(string $pointer, Closure $callback = null): self
    {
        $this->config->pointers->add(new Pointer($pointer, false, $callback));

        return $this;
    }

    /**
     * Set the lazy JSON pointers.
     *
     * @param string[]|array<string, Closure> $pointers
     *
     * @return self
     * @throws Exceptions\IntersectingPointersException
     * @throws Exceptions\InvalidPointerException
     */
    public function lazyPointers(array $pointers): self
    {
        foreach ($pointers as $pointer => $callback) {
            $callback instanceof Closure ? $this->lazyPointer($pointer, $callback) : $this->lazyPointer($callback);
        }

        return $this;
    }

    /**
     * Set a lazy JSON pointer.
     *
     * @param string       $pointer
     * @param Closure|null $callback
     *
     * @return self
     * @throws Exceptions\IntersectingPointersException
     * @throws Exceptions\InvalidPointerException
     */
    public function lazyPointer(string $pointer, Closure $callback = null): self
    {
        $this->config->pointers->add(new Pointer($pointer, true, $callback));

        return $this;
    }

    /**
     * Set a lazy JSON pointer for the whole JSON.
     *
     * @return self
     * @throws Exceptions\IntersectingPointersException
     * @throws Exceptions\InvalidPointerException
     */
    public function lazy(): self
    {
        return $this->lazyPointer('');
    }

    /**
     * Traverse the JSON one key and value at a time.
     *
     * @param Closure|null $callback
     *
     * @return void
     */
    public function traverse(Closure $callback = null): void
    {
        foreach ($this as $key => $value) {
            $callback && $callback($value, $key, $this);
        }
    }

    /**
     * Eager load the JSON into an array.
     *
     * @return array<string|int, mixed>
     * @throws SyntaxException
     */
    public function toArray(): array
    {
        try {
            return $this->parser->toArray();
        } catch (SyntaxException $e) {
            if (is_callable($this->config->onSyntaxError)) {
                $exception = new SyntaxException($this->source, true);
                ($this->config->onSyntaxError)($exception);
            } else {
                throw $e;
            }
        }
    }

    /**
     * @return object
     * @throws SyntaxException
     */
    public function toObject()
    {
        return (object)$this->toArray();
    }

    /**
     * Set the JSON decoder.
     *
     * @param Decoder $decoder
     * @return self
     */
    public function decoder(Decoder $decoder): self
    {
        $this->config->decoder = $decoder;

        return $this;
    }

    /**
     * Retrieve the parsing progress.
     *
     * @return Progress
     */
    public function progress(): Progress
    {
        return $this->lexer->progress();
    }

    /**
     * The number of bytes to read in each chunk.
     *
     * @param int<1, max> $bytes
     *
     * @return self
     */
    public function bytes(int $bytes): self
    {
        $this->config->bytes = $bytes;

        return $this;
    }

    /**
     * Set the patch to apply during a decoding error.
     *
     * @param mixed|null $patch
     *
     * @return self
     */
    public function patchDecodingError($patch = null): self
    {
        return $this->onDecodingError(function (DecodedValue $decoded) use ($patch) {
            $decoded->value = is_callable($patch) ? $patch($decoded) : $patch;
        });
    }

    /**
     * Set the logic to run during a decoding error.
     *
     * @param Closure $callback
     *
     * @return self
     */
    public function onDecodingError(Closure $callback): self
    {
        $this->config->onDecodingError = $callback;

        return $this;
    }

    /**
     * Set the logic to run during a syntax error.
     *
     * @param Closure $callback
     *
     * @return self
     */
    public function onSyntaxError(Closure $callback): self
    {
        $this->config->onSyntaxError = $callback;

        return $this;
    }

    /**
     * Set source data.
     *
     * @param mixed|null $source
     *
     * @return $this
     * @throws Exception
     */
    public function setSource($source) : self
    {
        $this->init($source);

        return $this;
    }
}
