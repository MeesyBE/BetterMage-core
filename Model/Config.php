<?php

declare(strict_types=1);

namespace BetterMagento\Core\Model;

use BetterMagento\Core\Api\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Config implements ConfigInterface
{
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
    ) {}

    public function get(string $path, string $scope = self::SCOPE_STORE, int|string|null $scopeCode = null): mixed
    {
        return $this->scopeConfig->getValue($path, $scope, $scopeCode);
    }

    public function isEnabled(string $path, string $scope = self::SCOPE_STORE, int|string|null $scopeCode = null): bool
    {
        return $this->scopeConfig->isSetFlag($path, $scope, $scopeCode);
    }

    public function getFlag(string $path, string $scope = self::SCOPE_STORE, int|string|null $scopeCode = null): bool
    {
        return $this->scopeConfig->isSetFlag($path, $scope, $scopeCode);
    }
}
