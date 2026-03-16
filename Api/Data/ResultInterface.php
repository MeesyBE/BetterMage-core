<?php

declare(strict_types=1);

namespace BetterMagento\Core\Api\Data;

/**
 * Standardised return type for BetterMagento service operations.
 *
 * Immutable value object — every `with*` method returns a new instance.
 *
 * Usage:
 *   return (new Result())
 *       ->withSuccess(false)
 *       ->withMessage('Cache backend unreachable')
 *       ->withData(['backend' => 'redis', 'host' => '127.0.0.1']);
 */
interface ResultInterface
{
    public function isSuccess(): bool;

    public function getMessage(): string;

    /** @return array<string, mixed> */
    public function getData(): array;

    public function withSuccess(bool $success): static;

    public function withMessage(string $message): static;

    /** @param array<string, mixed> $data */
    public function withData(array $data): static;
}
