<?php

/**
 * Integration test bootstrap for BetterMagento_Core.
 *
 * There are two supported modes:
 *
 * 1. STANDALONE (default — no Magento install required):
 *    Sets up Composer autoload only. Tests that require full Magento DI are
 *    skipped automatically via the @group integration marker combined with the
 *    MAGENTO_ROOT env check inside each test.
 *
 * 2. FULL (requires a Magento install):
 *    Set the MAGENTO_ROOT environment variable to the absolute path of the
 *    Magento installation. The Magento bootstrap + object manager are initialised
 *    and real DI injection tests can run.
 *
 *    export MAGENTO_ROOT=/var/www/magento
 *    vendor/bin/phpunit -c phpunit.integration.xml.dist
 */

declare(strict_types=1);

// ------------------------------------------------------------------
// 1. Composer autoload (always)
// ------------------------------------------------------------------
$autoloadCandidates = [
    __DIR__ . '/../../vendor/autoload.php',   // when run inside packages/core/
    __DIR__ . '/../../../vendor/autoload.php', // monorepo root
    __DIR__ . '/../../../../vendor/autoload.php',
];

$autoloaded = false;
foreach ($autoloadCandidates as $path) {
    if (file_exists($path)) {
        require_once $path;
        $autoloaded = true;
        break;
    }
}

if (!$autoloaded) {
    fwrite(STDERR, "ERROR: composer autoload.php not found. Run `composer install` first.\n");
    exit(1);
}

// ------------------------------------------------------------------
// 2. Full Magento bootstrap (only when MAGENTO_ROOT is set)
// ------------------------------------------------------------------
$magentoRoot = getenv('MAGENTO_ROOT');

if ($magentoRoot !== false) {
    if (!is_dir($magentoRoot)) {
        fwrite(STDERR, "ERROR: MAGENTO_ROOT={$magentoRoot} does not exist.\n");
        exit(1);
    }

    require_once $magentoRoot . '/app/bootstrap.php';

    $params = $_SERVER;
    $params[\Magento\Framework\App\Bootstrap::INIT_PARAM_FILESYSTEM_DIR_PATHS] = [
        \Magento\Framework\Filesystem\DirectoryList::VAR_DIR => ['path' => $magentoRoot . '/var'],
    ];

    $bootstrap = \Magento\Framework\App\Bootstrap::create($magentoRoot, $params);

    // Make ObjectManager globally accessible to integration tests
    $objectManager = $bootstrap->getObjectManager();
    \Magento\TestFramework\Helper\Bootstrap::setObjectManager($objectManager);

    define('BETTERMAGENTO_INTEGRATION_ENABLED', true);
} else {
    define('BETTERMAGENTO_INTEGRATION_ENABLED', false);
}
