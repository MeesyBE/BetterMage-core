<?php

declare(strict_types=1);

namespace BetterMagento\Core\Test\Unit\Model;

use BetterMagento\Core\Api\LoggerInterface;
use BetterMagento\Core\Model\PerformanceMonitor;
use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PerformanceMonitorTest extends TestCase
{
    private PerformanceMonitor $monitor;
    private ScopeConfigInterface|MockObject $scopeConfig;
    private LoggerInterface|MockObject $logger;

    protected function setUp(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        // Enable monitoring by default in tests
        $this->scopeConfig->method('isSetFlag')->willReturn(true);

        $this->monitor = new PerformanceMonitor($this->scopeConfig, $this->logger);
    }

    public function testStartStopRecordsMetric(): void
    {
        $this->monitor->start('test.op');
        $elapsed = $this->monitor->stop('test.op', ['count' => 5]);

        self::assertGreaterThanOrEqual(0.0, $elapsed);
        $metrics = $this->monitor->getMetrics();
        self::assertCount(1, $metrics);
        self::assertSame('test.op', $metrics[0]['operation']);
        self::assertSame(['count' => 5], $metrics[0]['metadata']);
    }

    public function testStopWithoutStartReturnsZero(): void
    {
        $elapsed = $this->monitor->stop('never_started');
        self::assertSame(0.0, $elapsed);
        self::assertCount(0, $this->monitor->getMetrics());
    }

    public function testRecordDirectMetric(): void
    {
        $this->monitor->record('queries.analyzed', 42, ['source' => 'test']);

        $metrics = $this->monitor->getMetrics();
        self::assertCount(1, $metrics);
        self::assertSame('queries.analyzed', $metrics[0]['operation']);
        self::assertSame(42.0, $metrics[0]['elapsed_ms']);
    }

    public function testGetSummaryGroupsByOperation(): void
    {
        $this->monitor->record('op.a', 10.0);
        $this->monitor->record('op.a', 20.0);
        $this->monitor->record('op.b', 5.0);

        $summary = $this->monitor->getSummary();

        self::assertArrayHasKey('op.a', $summary);
        self::assertSame(2, $summary['op.a']['count']);
        self::assertSame(30.0, $summary['op.a']['total_ms']);
        self::assertSame(15.0, $summary['op.a']['avg_ms']);
        self::assertSame(10.0, $summary['op.a']['min_ms']);
        self::assertSame(20.0, $summary['op.a']['max_ms']);

        self::assertArrayHasKey('op.b', $summary);
        self::assertSame(1, $summary['op.b']['count']);
    }

    public function testClearMetrics(): void
    {
        $this->monitor->record('test', 1.0);
        $this->monitor->start('timer');
        self::assertCount(1, $this->monitor->getMetrics());

        $this->monitor->clearMetrics();
        self::assertCount(0, $this->monitor->getMetrics());

        // Timer should also be cleared — stop should return 0
        $elapsed = $this->monitor->stop('timer');
        self::assertSame(0.0, $elapsed);
    }

    public function testIsActiveReflectsDebugMode(): void
    {
        self::assertTrue($this->monitor->isActive());
    }

    public function testInactiveMonitorSkipsAllOperations(): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('isSetFlag')->willReturn(false);

        $monitor = new PerformanceMonitor($scopeConfig, $this->logger);
        $monitor->start('op');
        $monitor->stop('op');
        $monitor->record('metric', 99);

        self::assertCount(0, $monitor->getMetrics());
        self::assertFalse($monitor->isActive());
    }
}
