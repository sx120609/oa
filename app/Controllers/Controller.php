<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Utils\HttpException;
use Throwable;

use DateTimeImmutable;
use DateTimeZone;

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
            throw new HttpException('未登录或无权限', 401);
        }

        return $actorId;
    }

    protected function actorRole(): ?string
    {
        $role = $_SESSION['role'] ?? null;
        if ($role === null) {
            return null;
        }

        return (string) $role;
    }

    protected function actorIsAdmin(): bool
    {
        $role = $this->actorRole();
        return $role === 'owner' || $role === 'asset_admin';
    }

    protected function requireString(string $key): string
    {
        $value = trim((string) ($_POST[$key] ?? ''));
        if ($value === '') {
            throw new HttpException(sprintf('缺少字段 %s', $key), 409);
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
            throw new HttpException(sprintf('字段 %s 不合法', $key), 409);
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
            throw new HttpException(sprintf('字段 %s 不合法', $key), 409);
        }

        return (int) $value;
    }

    protected function parseDateTimeField(string $key, bool $required = true): ?DateTimeImmutable
    {
        $value = $required ? $this->requireString($key) : ($this->optionalString($key) ?? '');

        if ($value === '') {
            return null;
        }

        $normalized = str_replace(' ', 'T', trim($value));
        $timezoneId = env('APP_TZ') ?: date_default_timezone_get();
        $timezone = new DateTimeZone($timezoneId);
        $formats = ['Y-m-d\TH:i:s', 'Y-m-d\TH:i'];

        foreach ($formats as $format) {
            $dateTime = DateTimeImmutable::createFromFormat($format, $normalized, $timezone);
            if ($dateTime !== false) {
                return $dateTime;
            }
        }

        $timestamp = strtotime($normalized);
        if ($timestamp === false) {
            throw new HttpException(sprintf('字段 %s 不合法', $key), 409);
        }

        return (new DateTimeImmutable('@' . $timestamp))->setTimezone($timezone);
    }

    protected function timestampFromPost(string $key, bool $required = true): ?int
    {
        $dateTime = $this->parseDateTimeField($key, $required);
        if ($dateTime === null) {
            return null;
        }

        return $dateTime->getTimestamp();
    }

    protected function decimalFromPost(string $key, float $default = 0.0): float
    {
        $raw = $_POST[$key] ?? null;
        if ($raw === null || $raw === '') {
            return $default;
        }

        $value = filter_var($raw, FILTER_VALIDATE_FLOAT);
        if ($value === false) {
            throw new HttpException(sprintf('字段 %s 不合法', $key), 409);
        }

        return (float) $value;
    }

    protected function nextReferenceId(): int
    {
        try {
            return random_int(1, 2_147_483_647);
        } catch (Throwable $exception) {
            throw new HttpException('无法生成标识符', 500, $exception);
        }
    }
}
