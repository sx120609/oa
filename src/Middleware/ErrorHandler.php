<?php

namespace App\Middleware;

use App\Domain\DomainException;
use App\Http\Response;
use App\Infra\Logger;

class ErrorHandler
{
    public static function handle(callable $next)
    {
        try {
            return $next();
        } catch (DomainException $e) {
            Logger::info('Domain error', ['message' => $e->getMessage(), 'details' => $e->getDetails()]);
            Response::error($e->getMessage(), $e->getCode(), $e->getDetails());
        } catch (\Throwable $e) {
            Logger::error('Unhandled exception', ['message' => $e->getMessage()]);
            Response::error('Internal Server Error', 500);
        }

        return null;
    }
}
