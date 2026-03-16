<?php

declare(strict_types=1);

namespace BetterMagento\Core\Model;

use BetterMagento\Core\Api\LoggerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Psr\Log\LoggerInterface as PsrLoggerInterface;

/**
 * BetterMagento logger.
 *
 * Wraps a PSR-3 logger (wired in di.xml to a dedicated Monolog handler that
 * writes to var/log/bettermagento.log).  Debug messages are suppressed unless
 * bettermagento/general/debug_mode is enabled in admin config.
 */
class Logger implements LoggerInterface, PsrLoggerInterface
{
    private const CONFIG_DEBUG_MODE = 'bettermagento/general/debug_mode';

    public function __construct(
        private readonly PsrLoggerInterface $psrLogger,
        private readonly ScopeConfigInterface $scopeConfig,
    ) {}

    public function debug(string|\Stringable $message, array $context = []): void
    {
        if ($this->scopeConfig->isSetFlag(self::CONFIG_DEBUG_MODE)) {
            $this->psrLogger->debug($message, $context);
        }
    }

    public function info(string|\Stringable $message, array $context = []): void
    {
        $this->psrLogger->info($message, $context);
    }

    public function warning(string|\Stringable $message, array $context = []): void
    {
        $this->psrLogger->warning($message, $context);
    }

    public function error(string|\Stringable $message, array $context = []): void
    {
        $this->psrLogger->error($message, $context);
    }

    public function critical(string|\Stringable $message, array $context = []): void
    {
        $this->psrLogger->critical($message, $context);
    }

    /**
     * PSR-3 methods for complete PSR logger interface compatibility.
     */
    public function alert(string|\Stringable $message, array $context = []): void
    {
        $this->psrLogger->alert($message, $context);
    }

    public function emergency(string|\Stringable $message, array $context = []): void
    {
        $this->psrLogger->emergency($message, $context);
    }

    public function notice(string|\Stringable $message, array $context = []): void
    {
        $this->psrLogger->notice($message, $context);
    }

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $this->psrLogger->log($level, $message, $context);
    }
}
