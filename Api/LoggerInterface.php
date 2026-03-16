<?php

declare(strict_types=1);

namespace BetterMagento\Core\Api;

/**
 * BetterMagento logging interface.
 *
 * All log entries are written to var/log/bettermagento.log.
 * Debug-level entries are suppressed unless bettermagento/general/debug_mode is enabled.
 *
 * Usage:
 *   $this->logger->info('Cache warmed', ['module' => 'TurboCore', 'keys' => 42]);
 *   $this->logger->error('Cache backend unreachable', ['exception' => $e->getMessage()]);
 */
interface LoggerInterface
{
    /**
     * Only logged when bettermagento/general/debug_mode = 1.
     *
     * @param array<string, mixed> $context
     */
    public function debug(string|\Stringable $message, array $context = []): void;

    /**
     * @param array<string, mixed> $context
     */
    public function info(string|\Stringable $message, array $context = []): void;

    /**
     * @param array<string, mixed> $context
     */
    public function warning(string|\Stringable $message, array $context = []): void;

    /**
     * @param array<string, mixed> $context
     */
    public function error(string|\Stringable $message, array $context = []): void;

    /**
     * @param array<string, mixed> $context
     */
    public function critical(string|\Stringable $message, array $context = []): void;
}
