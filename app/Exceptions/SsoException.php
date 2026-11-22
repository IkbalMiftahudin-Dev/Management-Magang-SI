<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class SsoException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public readonly string $errorCode,
        public readonly int $httpStatus,
        public readonly string $publicMessage,
        public readonly array $context = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($publicMessage, $httpStatus, $previous);
    }
}
