<?php

declare(strict_types=1);

namespace BetterMagento\Core\Test\Unit\Model;

use BetterMagento\Core\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    private ScopeConfigInterface&MockObject $scopeConfig;
    private Config $config;

    protected function setUp(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->config = new Config($this->scopeConfig);
    }

    public function testGetDelegatesToScopeConfigGetValue(): void
    {
        $this->scopeConfig
            ->expects(self::once())
            ->method('getValue')
            ->with('bettermagento/general/enabled', 'store', null)
            ->willReturn('1');

        $result = $this->config->get('bettermagento/general/enabled');

        self::assertSame('1', $result);
    }

    public function testGetPassesScopeAndScopeCode(): void
    {
        $this->scopeConfig
            ->expects(self::once())
            ->method('getValue')
            ->with('bettermagento/general/enabled', 'website', 2)
            ->willReturn('0');

        $this->config->get('bettermagento/general/enabled', 'website', 2);
    }

    public function testIsEnabledReturnsTrueWhenFlagIsSet(): void
    {
        $this->scopeConfig
            ->expects(self::once())
            ->method('isSetFlag')
            ->with('bettermagento/general/enabled', 'store', null)
            ->willReturn(true);

        self::assertTrue($this->config->isEnabled('bettermagento/general/enabled'));
    }

    public function testIsEnabledReturnsFalseWhenFlagIsNotSet(): void
    {
        $this->scopeConfig
            ->method('isSetFlag')
            ->willReturn(false);

        self::assertFalse($this->config->isEnabled('bettermagento/general/enabled'));
    }

    public function testGetFlagDelegatesToIsSetFlag(): void
    {
        $this->scopeConfig
            ->expects(self::once())
            ->method('isSetFlag')
            ->with('bettermagento/general/debug_mode', 'store', null)
            ->willReturn(true);

        self::assertTrue($this->config->getFlag('bettermagento/general/debug_mode'));
    }

    public function testGetReturnsNullForUnknownPath(): void
    {
        $this->scopeConfig
            ->method('getValue')
            ->willReturn(null);

        self::assertNull($this->config->get('bettermagento/unknown/key'));
    }
}
