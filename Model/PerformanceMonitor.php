<?php

declare(strict_types=1);

namespace BetterMagento\Core\Model;

use BetterMagento\Core\Api\PerformanceMonitorInterface;
use BetterMagento\Core\Api\LoggerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Cross-package performance monitor.
 *
 * Active only when debug mode is enabled. Collects timing and metric data
 * across all BetterMagento packages for diagnostics and optimization.
 */
class PerformanceMonitor implements PerformanceMonitorInterface
{
    private const CONFIG_DEBUG_MODE = 'bettermagento/general/debug_mode';

    /** @var array<string, float> running timers: operation → start microtime */
    private array $timers = [];

    /** @var list<array{operation: string, elapsed_ms: float, metadata: array<string, mixed>, timestamp: float}> */
    private array $metrics = [];

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly LoggerInterface $logger,
    ) {}

    public function start(string $operation): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->timers[$operation] = microtime(true);
    }

    public function stop(string $operation, array $metadata = []): float
    {
        if (!$this->isActive() || !isset($this->timers[$operation])) {
            return 0.0;
        }

        $elapsed = round((microtime(true) - $this->timers[$operation]) * 1000, 3);
        unset($this->timers[$operation]);

        $this->metrics[] = [
            'operation' => $operation,
            'elapsed_ms' => $elapsed,
            'metadata' => $metadata,
            'timestamp' => microtime(true),
        ];

        if ($elapsed > 1000) {
            $this->logger->warning("Slow operation: {$operation}", [
                'elapsed_ms' => $elapsed,
                ...$metadata,
            ]);
        }

        return $elapsed;
    }

    public function record(string $metric, float|int $value, array $metadata = []): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->metrics[] = [
            'operation' => $metric,
            'elapsed_ms' => (float) $value,
            'metadata' => $metadata,
            'timestamp' => microtime(true),
        ];
    }

    public function getMetrics(): array
    {
        return $this->metrics;
    }

    public function getSummary(): array
    {
        $grouped = [];

        foreach ($this->metrics as $metric) {
            $op = $metric['operation'];
            if (!isset($grouped[$op])) {
                $grouped[$op] = ['count' => 0, 'total_ms' => 0.0, 'min_ms' => PHP_FLOAT_MAX, 'max_ms' => 0.0];
            }

            $grouped[$op]['count']++;
            $grouped[$op]['total_ms'] += $metric['elapsed_ms'];
            $grouped[$op]['min_ms'] = min($grouped[$op]['min_ms'], $metric['elapsed_ms']);
            $grouped[$op]['max_ms'] = max($grouped[$op]['max_ms'], $metric['elapsed_ms']);
        }

        foreach ($grouped as &$stats) {
            $stats['avg_ms'] = round($stats['total_ms'] / $stats['count'], 3);
            $stats['total_ms'] = round($stats['total_ms'], 3);
        }

        return $grouped;
    }

    public function isActive(): bool
    {
        return (bool) $this->scopeConfig->isSetFlag(self::CONFIG_DEBUG_MODE);
    }

    public function clearMetrics(): void
    {
        $this->metrics = [];
        $this->timers = [];
    }
}
