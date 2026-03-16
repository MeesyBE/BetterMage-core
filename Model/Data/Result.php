<?php

declare(strict_types=1);

namespace BetterMagento\Core\Model\Data;

use BetterMagento\Core\Api\Data\ResultInterface;

/**
 * Immutable result value object.
 *
 * Every mutation returns a clone so callers can safely chain without aliasing.
 */
class Result implements ResultInterface
{
    private bool $success = true;
    private string $message = '';
    /** @var array<string, mixed> */
    private array $data = [];

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    /** @return array<string, mixed> */
    public function getData(): array
    {
        return $this->data;
    }

    public function withSuccess(bool $success): static
    {
        $clone = clone $this;
        $clone->success = $success;
        return $clone;
    }

    public function withMessage(string $message): static
    {
        $clone = clone $this;
        $clone->message = $message;
        return $clone;
    }

    /** @param array<string, mixed> $data */
    public function withData(array $data): static
    {
        $clone = clone $this;
        $clone->data = $data;
        return $clone;
    }
}
