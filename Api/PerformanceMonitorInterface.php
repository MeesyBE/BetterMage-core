<?php

declare(strict_types=1);

namespace BetterMagento\Core\Api;

/**
 * Cross-package performance monitoring interface.
 *
 * Tracks operation timing and metrics across all BetterMagento packages.
 * Active only when debug mode is enabled to avoid production overhead.
 *
 * Usage:
 *   $this->monitor->start('redis_turbo.cache_warm');
 *   // ... work ...
 *   $this->monitor->stop('redis_turbo.cache_warm', ['keys' => 42]);
 *
 * @since 0.2.0
 */
interface PerformanceMonitorInterface
{
    /**
     * Start timing an operation.
     *
     * @param string $operation  Dot-notation operation name (e.g., 'edge_delivery.purge')
     */
    public function start(string $operation): void;

    /**
     * Stop timing an operation and record the metric.
     *
     * @param string $operation  Must match a prior start() call
     * @param array<string, mixed> $metadata  Additional context for the metric
     * @return float Elapsed time in milliseconds
     */
    public function stop(string $operation, array $metadata = []): float;

    /**
     * Record a metric value directly (without start/stop).
     *
     * @param string $metric  Metric name (e.g., 'query_optimizer.queries_analyzed')
     * @param float|int $value  Metric value
     * @param array<string, mixed> $metadata  Additional context
     */
    public function record(string $metric, float|int $value, array $metadata = []): void;

    /**
     * Get all recorded metrics for this request.
     *
     * @return list<array{operation: string, elapsed_ms: float, metadata: array<string, mixed>, timestamp: float}>
     */
    public function getMetrics(): array;

    /**
     * Get a summary of all metrics grouped by operation.
     *
     * @return array<string, array{count: int, total_ms: float, avg_ms: float, min_ms: float, max_ms: float}>
     */
    public function getSummary(): array;

    /**
     * Check if monitoring is active.
     */
    public function isActive(): bool;

    /**
     * Clear all recorded metrics.
     */
    public function clearMetrics(): void;
}
