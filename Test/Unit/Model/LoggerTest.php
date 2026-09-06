<?php

declare(strict_types=1);

namespace BetterMagento\Core\Test\Unit\Model;

use BetterMagento\Core\Model\Logger;
use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface as PsrLoggerInterface;

class LoggerTest extends TestCase
{
    private PsrLoggerInterface&MockObject $psrLogger;
    private ScopeConfigInterface&MockObject $scopeConfig;
    private Logger $logger;

    protected function setUp(): void
    {
        $this->psrLogger   = $this->createMock(PsrLoggerInterface::class);
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->logger      = new Logger($this->psrLogger, $this->scopeConfig);
    }

    // ---- info ---------------------------------------------------------------

    public function testInfoAlwaysLogs(): void
    {
        $this->psrLogger->expects(self::once())
            ->method('info')
            ->with('hello world', []);

        $this->logger->info('hello world');
    }

    public function testInfoPassesContextArray(): void
    {
        $ctx = ['module' => 'TurboCore', 'key' => 42];

        $this->psrLogger->expects(self::once())
            ->method('info')
            ->with('msg', $ctx);

        $this->logger->info('msg', $ctx);
    }

    // ---- debug (gated on debug_mode) ----------------------------------------

    public function testDebugLogsWhenDebugModeEnabled(): void
    {
        $this->scopeConfig->method('isSetFlag')->willReturn(true);

        $this->psrLogger->expects(self::once())
            ->method('debug')
            ->with('verbose info', []);

        $this->logger->debug('verbose info');
    }

    public function testDebugSuppressedWhenDebugModeDisabled(): void
    {
        $this->scopeConfig->method('isSetFlag')->willReturn(false);

        $this->psrLogger->expects(self::never())->method('debug');

        $this->logger->debug('should not appear');
    }

    // ---- warning ------------------------------------------------------------

    public function testWarningAlwaysLogs(): void
    {
        $this->psrLogger->expects(self::once())
            ->method('warning')
            ->with('slow query', ['duration_ms' => 250]);

        $this->logger->warning('slow query', ['duration_ms' => 250]);
    }

    // ---- error --------------------------------------------------------------

    public function testErrorAlwaysLogs(): void
    {
        $this->psrLogger->expects(self::once())
            ->method('error')
            ->with('connection refused', []);

        $this->logger->error('connection refused');
    }

    // ---- critical -----------------------------------------------------------

    public function testCriticalAlwaysLogs(): void
    {
        $this->psrLogger->expects(self::once())
            ->method('critical')
            ->with('disk full', ['path' => '/var/log']);

        $this->logger->critical('disk full', ['path' => '/var/log']);
    }

    // ---- alert / emergency / notice / log (PSR-3 completeness) ---------------

    public function testAlertDelegates(): void
    {
        $this->psrLogger->expects(self::once())
            ->method('alert')
            ->with('must act now', ['sev' => 1]);

        $this->logger->alert('must act now', ['sev' => 1]);
    }

    public function testEmergencyDelegates(): void
    {
        $this->psrLogger->expects(self::once())
            ->method('emergency')
            ->with('system down', []);

        $this->logger->emergency('system down');
    }

    public function testNoticeDelegates(): void
    {
        $this->psrLogger->expects(self::once())
            ->method('notice')
            ->with('heads up', ['n' => 2]);

        $this->logger->notice('heads up', ['n' => 2]);
    }

    public function testLogDelegatesWithLevel(): void
    {
        $this->psrLogger->expects(self::once())
            ->method('log')
            ->with(\Psr\Log\LogLevel::WARNING, 'via log()', ['ctx' => true]);

        $this->logger->log(\Psr\Log\LogLevel::WARNING, 'via log()', ['ctx' => true]);
    }
}
