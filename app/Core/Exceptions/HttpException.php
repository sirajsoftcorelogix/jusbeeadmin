<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

use RuntimeException;

class HttpException extends RuntimeException
{
    public function __construct(private readonly int $statusCode, string $message = '')
    {
        parent::__construct($message === '' ? 'HTTP Error' : $message, $statusCode);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
