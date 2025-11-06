<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Utils\HttpException;
use Throwable;

abstract class Controller
{
    protected function actorId(): ?int
    {
        if (isset($_SESSION['uid'])) {
            return (int) $_SESSION['uid'];
        }

        if (isset($_SESSION['user_id'])) {
            return (int) $_SESSION['user_id'];
        }

        return null;
    }

    protected function requireActor(): int
    {
        $actorId = $this->actorId();
        if ($actorId === null || $actorId <= 0) {
            throw new HttpException('Unauthorized', 401);
        }

        return $actorId;
    }

    protected function requireString(string $key): string
    {
        $value = trim((string) ($_POST[$key] ?? ''));
        if ($value === '') {
            throw new HttpException(sprintf('Missing %s', $key), 409);
        }

        return $value;
    }

    protected function optionalString(string $key): ?string
    {
        $value = trim((string) ($_POST[$key] ?? ''));
        return $value === '' ? null : $value;
    }

    protected function requirePositiveInt(string $key): int
    {
        $raw = $_POST[$key] ?? null;
        $value = filter_var($raw, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        if ($value === false) {
            throw new HttpException(sprintf('Invalid %s', $key), 409);
        }

        return (int) $value;
    }

    protected function optionalPositiveInt(string $key): ?int
    {
        $raw = $_POST[$key] ?? null;
        if ($raw === null || $raw === '') {
            return null;
        }

        $value = filter_var($raw, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        if ($value === false) {
            throw new HttpException(sprintf('Invalid %s', $key), 409);
        }

        return (int) $value;
    }

    protected function timestampFromPost(string $key, bool $required = true): ?int
    {
        $value = $required ? $this->requireString($key) : ($this->optionalString($key) ?? '');

        if ($value === '') {
            return null;
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            throw new HttpException(sprintf('Invalid %s', $key), 409);
        }

        return $timestamp;
    }

    protected function decimalFromPost(string $key, float $default = 0.0): float
    {
        $raw = $_POST[$key] ?? null;
        if ($raw === null || $raw === '') {
            return $default;
        }

        $value = filter_var($raw, FILTER_VALIDATE_FLOAT);
        if ($value === false) {
            throw new HttpException(sprintf('Invalid %s', $key), 409);
        }

        return (float) $value;
    }

    protected function nextReferenceId(): int
    {
        try {
            return random_int(1, 2_147_483_647);
        } catch (Throwable $exception) {
            throw new HttpException('Unable to generate identifier', 500, $exception);
        }
    }
}
