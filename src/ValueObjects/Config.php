<?php

namespace Coco\JsonParser\ValueObjects;

use Coco\JsonParser\Decoders\JsonDecoder;
use Coco\JsonParser\Decoders\DecodedValue;
use Coco\JsonParser\Decoders\Decoder;
use Coco\JsonParser\Decoders\SimdjsonDecoder;
use Coco\JsonParser\Exceptions\DecodingException;
use Coco\JsonParser\Exceptions\IntersectingPointersException;
use Coco\JsonParser\Exceptions\SyntaxException;
use Coco\JsonParser\Pointers\Pointer;
use Coco\JsonParser\Pointers\Pointers;
use Closure;

/**
 * The configuration.
 *
 */
final class Config
{
    /**
     * The JSON decoder.
     *
     * @var Decoder $decoder
     */
    public $decoder;

    /**
     * The JSON pointers.
     *
     * @var Pointers $pointers
     */
    public $pointers;

    /**
     * The number of bytes to read in each chunk.
     *
     * @var int<1, max>
     */
    public $bytes = 1024 * 8;

    /**
     * The callback to run during a decoding error.
     *
     * @var Closure
     */
    public $onDecodingError;

    /**
     * The callback to run during a syntax error.
     *
     * @var Closure
     */
    public $onSyntaxError;

    /**
     * Instantiate the class
     *
     */
    public function __construct()
    {
        $this->decoder = extension_loaded('simdjson') ? new SimdjsonDecoder() : new JsonDecoder();
        $this->pointers = new Pointers();
        $this->onDecodingError = function (DecodedValue $decoded) {
            throw new DecodingException($decoded);
        };
        $this->onSyntaxError = function (SyntaxException $e) {
            throw $e;
        };
    }

    /**
     * Clone the configuration
     *
     * @return void
     * @throws IntersectingPointersException
     */
    public function __clone()
    {
        $this->pointers = new Pointers();
        $this->pointers->add(new Pointer('', true));
    }
}
