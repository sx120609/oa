<?php

namespace App;

use Exception;

class HttpException extends Exception
{
    private int $statusCode;
    private ?string $errorCode;
    private ?array $details;

    public function __construct(int $statusCode, string $message, ?string $errorCode = null, ?array $details = null)
    {
        parent::__construct($message);
        $this->statusCode = $statusCode;
        $this->errorCode = $errorCode;
        $this->details = $details;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    public function getDetails(): ?array
    {
        return $this->details;
    }
}
