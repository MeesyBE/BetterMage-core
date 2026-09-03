<?php

declare(strict_types=1);

namespace BetterMagento\Core\Model;

use BetterMagento\Core\Api\EventBusInterface;
use BetterMagento\Core\Api\LoggerInterface;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * BetterMagento event bus wrapping Magento's native EventManager.
 *
 * All events are automatically prefixed with `bettermagento_`.
 * In debug mode, events are recorded for diagnostics via admin tools.
 */
class EventBus implements EventBusInterface
{
    private const EVENT_PREFIX = 'bettermagento_';
    private const CONFIG_DEBUG_MODE = 'bettermagento/general/debug_mode';

    /** @var list<array{event: string, data: array<string, mixed>, timestamp: float}> */
    private array $recordedEvents = [];

    public function __construct(
        private readonly EventManagerInterface $eventManager,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly LoggerInterface $logger,
    ) {}

    public function dispatch(string $eventName, array $data = []): void
    {
        $prefixedName = $this->prefixEventName($eventName);

        if ($this->isRecording()) {
            $this->recordedEvents[] = [
                'event' => $prefixedName,
                'data' => $data,
                'timestamp' => microtime(true),
            ];
        }

        $this->logger->debug("EventBus dispatch: {$prefixedName}", [
            'payload_keys' => array_keys($data),
        ]);

        $this->eventManager->dispatch($prefixedName, $data);
    }

    public function getRecordedEvents(): array
    {
        return $this->recordedEvents;
    }

    public function isRecording(): bool
    {
        return (bool) $this->scopeConfig->isSetFlag(self::CONFIG_DEBUG_MODE);
    }

    public function clearRecordedEvents(): void
    {
        $this->recordedEvents = [];
    }

    private function prefixEventName(string $eventName): string
    {
        if (str_starts_with($eventName, self::EVENT_PREFIX)) {
            return $eventName;
        }

        return self::EVENT_PREFIX . $eventName;
    }
}
