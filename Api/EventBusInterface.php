<?php

declare(strict_types=1);

namespace BetterMagento\Core\Api;

/**
 * Lightweight event bus for cross-package communication.
 *
 * Wraps Magento's native EventManager with BetterMagento conventions:
 * - All events are auto-prefixed with `bettermagento_`
 * - Debug mode records dispatched events for diagnostics
 * - Typed dispatch ensures consistent data structures
 *
 * @since 0.2.0
 */
interface EventBusInterface
{
    /**
     * Dispatch a BetterMagento event.
     *
     * The event name is auto-prefixed with `bettermagento_` if not already.
     *
     * @param string $eventName  Short event name (e.g., 'cache_purged')
     * @param array<string, mixed> $data  Event payload
     */
    public function dispatch(string $eventName, array $data = []): void;

    /**
     * Get all events dispatched during this request (debug mode only).
     *
     * @return list<array{event: string, data: array<string, mixed>, timestamp: float}>
     */
    public function getRecordedEvents(): array;

    /**
     * Check if event recording is active (debug mode).
     */
    public function isRecording(): bool;

    /**
     * Clear all recorded events.
     */
    public function clearRecordedEvents(): void;
}
