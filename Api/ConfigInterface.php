<?php

declare(strict_types=1);

namespace BetterMagento\Core\Api;

/**
 * Shared configuration abstraction for all BetterMagento modules.
 *
 * Usage in other BM modules:
 *   public function __construct(private readonly ConfigInterface $config) {}
 *   $this->config->isEnabled('bettermagento/turbo_core/enabled');
 */
interface ConfigInterface
{
    public const SCOPE_STORE = 'store';
    public const SCOPE_WEBSITE = 'website';
    public const SCOPE_DEFAULT = 'default';

    /**
     * Retrieve a raw config value.
     *
     * @param string $path       System config path, e.g. "bettermagento/general/enabled"
     * @param string $scope      One of SCOPE_STORE, SCOPE_WEBSITE, SCOPE_DEFAULT
     * @param int|string|null $scopeCode  Store ID, store code, or null for current
     */
    public function get(string $path, string $scope = self::SCOPE_STORE, int|string|null $scopeCode = null): mixed;

    /**
     * Returns true when the config flag at $path is set (non-zero, non-null, non-empty).
     */
    public function isEnabled(string $path, string $scope = self::SCOPE_STORE, int|string|null $scopeCode = null): bool;

    /**
     * Returns the boolean value of the config flag at $path.
     * Alias for isEnabled for more expressive code.
     */
    public function getFlag(string $path, string $scope = self::SCOPE_STORE, int|string|null $scopeCode = null): bool;
}
