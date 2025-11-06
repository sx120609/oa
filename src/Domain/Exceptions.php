<?php

namespace App\Domain;

use Exception;

class DomainException extends Exception
{
    protected array $details;

    public function __construct(string $message, int $code = 400, array $details = [])
    {
        parent::__construct($message, $code);
        $this->details = $details;
    }

    public function getDetails(): array
    {
        return $this->details;
    }
}

class ValidationException extends DomainException
{
    public function __construct(string $message, array $details = [])
    {
        parent::__construct($message, 422, $details);
    }
}

class NotFoundException extends DomainException
{
    public function __construct(string $message)
    {
        parent::__construct($message, 404);
    }
}

class AuthorizationException extends DomainException
{
    public function __construct(string $message = 'Unauthorized')
    {
        parent::__construct($message, 403);
    }
}

class ConflictException extends DomainException
{
    public function __construct(string $message, array $details = [])
    {
        parent::__construct($message, 409, $details);
    }
}
