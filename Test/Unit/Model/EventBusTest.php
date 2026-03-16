<?php

declare(strict_types=1);

namespace BetterMagento\Core\Test\Unit\Model;

use BetterMagento\Core\Api\LoggerInterface;
use BetterMagento\Core\Model\EventBus;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EventBusTest extends TestCase
{
    private EventBus $eventBus;
    private EventManagerInterface|MockObject $eventManager;
    private ScopeConfigInterface|MockObject $scopeConfig;
    private LoggerInterface|MockObject $logger;

    protected function setUp(): void
    {
        $this->eventManager = $this->createMock(EventManagerInterface::class);
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->eventBus = new EventBus(
            $this->eventManager,
            $this->scopeConfig,
            $this->logger,
        );
    }

    public function testDispatchPrefixesEventName(): void
    {
        $this->eventManager->expects(self::once())
            ->method('dispatch')
            ->with('bettermagento_cache_purged', ['tags' => ['foo']]);

        $this->eventBus->dispatch('cache_purged', ['tags' => ['foo']]);
    }

    public function testDispatchDoesNotDoublePrefixAlreadyPrefixedEvent(): void
    {
        $this->eventManager->expects(self::once())
            ->method('dispatch')
            ->with('bettermagento_cache_purged', []);

        $this->eventBus->dispatch('bettermagento_cache_purged');
    }

    public function testRecordingWhenDebugModeEnabled(): void
    {
        $this->scopeConfig->method('isSetFlag')->willReturn(true);

        $this->eventBus->dispatch('event_a', ['key' => 'val']);
        $this->eventBus->dispatch('event_b');

        $recorded = $this->eventBus->getRecordedEvents();
        self::assertCount(2, $recorded);
        self::assertSame('bettermagento_event_a', $recorded[0]['event']);
        self::assertSame(['key' => 'val'], $recorded[0]['data']);
        self::assertSame('bettermagento_event_b', $recorded[1]['event']);
    }

    public function testNoRecordingWhenDebugModeDisabled(): void
    {
        $this->scopeConfig->method('isSetFlag')->willReturn(false);

        $this->eventBus->dispatch('event_a');

        self::assertCount(0, $this->eventBus->getRecordedEvents());
    }

    public function testClearRecordedEvents(): void
    {
        $this->scopeConfig->method('isSetFlag')->willReturn(true);

        $this->eventBus->dispatch('test');
        self::assertCount(1, $this->eventBus->getRecordedEvents());

        $this->eventBus->clearRecordedEvents();
        self::assertCount(0, $this->eventBus->getRecordedEvents());
    }

    public function testIsRecordingReflectsDebugMode(): void
    {
        $this->scopeConfig->method('isSetFlag')
            ->willReturnCallback(fn(string $path) => $path === 'bettermagento/general/debug_mode');

        self::assertTrue($this->eventBus->isRecording());
    }
}
