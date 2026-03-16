<?php

declare(strict_types=1);

namespace BetterMagento\Core\Test\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Smoke tests that verify BetterMagento_Core loads inside a Magento ObjectManager.
 *
 * In STANDALONE mode (no MAGENTO_ROOT env var), every test is marked skipped
 * with an explanatory message. This keeps CI green without a Magento install
 * while still providing a runnable scaffold for integration environments.
 *
 * To run against a real Magento install:
 *   export MAGENTO_ROOT=/var/www/magento
 *   vendor/bin/phpunit -c phpunit.integration.xml.dist
 */
class ModuleLoadTest extends TestCase
{
    protected function setUp(): void
    {
        if (!defined('BETTERMAGENTO_INTEGRATION_ENABLED') || !BETTERMAGENTO_INTEGRATION_ENABLED) {
            self::markTestSkipped(
                'Integration tests require a Magento install. '
                . 'Set the MAGENTO_ROOT environment variable and use phpunit.integration.xml.dist.'
            );
        }
    }

    // -------------------------------------------------------------------------
    // Smoke tests (require Magento ObjectManager)
    // -------------------------------------------------------------------------

    public function testConfigInterfaceResolvesFromObjectManager(): void
    {
        $om     = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $config = $om->get(\BetterMagento\Core\Api\ConfigInterface::class);

        self::assertInstanceOf(
            \BetterMagento\Core\Model\Config::class,
            $config,
            'ConfigInterface preference must resolve to Model\Config'
        );
    }

    public function testLoggerInterfaceResolvesFromObjectManager(): void
    {
        $om     = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $logger = $om->get(\BetterMagento\Core\Api\LoggerInterface::class);

        self::assertInstanceOf(
            \BetterMagento\Core\Model\Logger::class,
            $logger,
            'LoggerInterface preference must resolve to Model\Logger'
        );
    }

    public function testResultInterfaceResolvesFromObjectManager(): void
    {
        $om     = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $result = $om->get(\BetterMagento\Core\Api\Data\ResultInterface::class);

        self::assertInstanceOf(
            \BetterMagento\Core\Model\Data\Result::class,
            $result,
            'ResultInterface preference must resolve to Model\Data\Result'
        );
    }

    public function testConfigGetReturnsDefaultEnabledValue(): void
    {
        $om     = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        /** @var \BetterMagento\Core\Api\ConfigInterface $config */
        $config = $om->get(\BetterMagento\Core\Api\ConfigInterface::class);

        $enabled = $config->get('bettermagento/general/enabled');
        self::assertNotNull($enabled, 'Default config value for bettermagento/general/enabled must exist');
    }

    public function testStatusCommandIsRegisteredInDi(): void
    {
        $om  = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $cmd = $om->get(\BetterMagento\Core\Console\Command\StatusCommand::class);

        self::assertInstanceOf(
            \BetterMagento\Core\Console\Command\StatusCommand::class,
            $cmd
        );
    }
}
