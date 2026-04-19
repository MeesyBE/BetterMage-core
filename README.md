# BetterMagento Core

[![PHP Version](https://img.shields.io/badge/php-8.2%2B-blue)](https://php.net)
[![Magento Version](https://img.shields.io/badge/magento-2.4.x%2B-orange)](https://magento.com)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)
[![Magevanta Suite](https://img.shields.io/badge/Magevanta-Premium%20Extensions-f97316)](https://magevanta.com/extensions)

**Composer package:** `bettermagento/module-core`  
**Magento module:** `BetterMagento_Core`  
**PHP:** ≥ 8.2 | **Magento:** ≥ 2.4.x / Mage-OS

Shared foundation for all BetterMagento modules. Provides config abstraction, a unified logger, a result-value object, abstract CLI tooling, and the shared admin config section.

> 🚀 Part of the [Magevanta Extension Suite](https://magevanta.com/extensions) — premium Magento 2 performance extensions.

**No other BM module works without this package.**

---

## Installation

```bash
# Manual install only (Composer install is currently not supported).
# Copy this package into app/code/BetterMagento/<ModuleName> and run:
php bin/magento setup:upgrade
php bin/magento cache:flush

bin/magento module:enable BetterMagento_Core
bin/magento setup:upgrade
```

---

## Features

| Feature | Class / Interface |
|---------|------------------|
| Config abstraction | `BetterMagento\Core\Api\ConfigInterface` |
| Structured logger | `BetterMagento\Core\Api\LoggerInterface` |
| Result value object | `BetterMagento\Core\Api\Data\ResultInterface` |
| Abstract CLI base | `BetterMagento\Core\Console\Command\AbstractBmCommand` |
| Module status CLI | `bin/magento bettermagento:status` |
| Generic CRUD repository | `BetterMagento\Core\Model\AbstractRepository` |
| Repository contract | `BetterMagento\Core\Api\RepositoryInterface` |
| Admin module widget | `BetterMagento\Core\Block\Adminhtml\BmModuleInfo` |
| Admin grid actions | `BetterMagento\Core\Ui\Component\Listing\Column\BmActions` |
| Admin config section | `Stores > Configuration > BetterMagento > General` |

---

## Config paths

| Path | Default | Scope |
|------|---------|-------|
| `bettermagento/general/enabled` | `1` | Store |
| `bettermagento/general/debug_mode` | `0` | Default |
| `bettermagento/general/log_level` | `info` | Default |

Log output: `var/log/bettermagento.log`

---

## Usage examples

### Reading config

```php
use BetterMagento\Core\Api\ConfigInterface;

class MyService
{
    public function __construct(
        private readonly ConfigInterface $config,
    ) {}

    public function execute(): void
    {
        if (!$this->config->isEnabled('bettermagento/general/enabled')) {
            return;
        }

        $level = $this->config->get('bettermagento/general/log_level');
    }
}
```

### Logging

```php
use BetterMagento\Core\Api\LoggerInterface;

class MyService
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public function execute(): void
    {
        $this->logger->info('Cache warmed', ['module' => 'TurboCore', 'keys' => 42]);
        $this->logger->error('Backend timeout', ['host' => 'redis-1']);
        // debug() is a no-op unless bettermagento/general/debug_mode = 1
        $this->logger->debug('Raw SQL', ['query' => $sql]);
    }
}
```

### Returning results from services

```php
use BetterMagento\Core\Api\Data\ResultInterface;
use BetterMagento\Core\Model\Data\Result;

class CacheWarmer
{
    public function warm(): ResultInterface
    {
        try {
            // ... do work
            return (new Result())
                ->withSuccess(true)
                ->withMessage('Cache warmed successfully')
                ->withData(['keys_written' => 1024]);
        } catch (\Throwable $e) {
            return (new Result())
                ->withSuccess(false)
                ->withMessage($e->getMessage());
        }
    }
}

// Consuming:
$result = $warmer->warm();
if (!$result->isSuccess()) {
    $this->logger->error($result->getMessage());
}
```

### Writing a CLI command

```php
use BetterMagento\Core\Console\Command\AbstractBmCommand;
use Symfony\Component\Console\Command\Command;

class StatusCommand extends AbstractBmCommand
{
    protected static $defaultName = 'bettermagento:status';

    protected function configure(): void
    {
        $this->setDescription('Show BetterMagento module status');
    }

    protected function run(): int
    {
        $this->title('BetterMagento Status');
        $this->success('Core module is active');
        $this->printElapsed();
        return Command::SUCCESS;
    }
}
```

Register in `etc/di.xml`:

```xml
<type name="Magento\Framework\Console\CommandListInterface">
    <arguments>
        <argument name="commands" xsi:type="array">
            <item name="bettermagento_status" xsi:type="object">MyVendor\MyModule\Console\Command\StatusCommand</item>
        </argument>
    </arguments>
</type>
```

### Using the abstract repository

```php
use BetterMagento\Core\Model\AbstractRepository;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class MyEntityRepository extends AbstractRepository
{
    public function __construct(
        private readonly MyEntityFactory $modelFactory,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resourceModel,
        \Magento\Framework\Api\SearchResultsInterfaceFactory $searchResultsFactory,
        \Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface $collectionProcessor,
        private readonly MyEntityCollectionFactory $collectionFactory,
    ) {
        parent::__construct($resourceModel, $searchResultsFactory, $collectionProcessor);
    }

    protected function createModel(): AbstractModel
    {
        return $this->modelFactory->create();
    }

    protected function createCollection(): AbstractCollection
    {
        return $this->collectionFactory->create();
    }
}
```

`getById()` results are cached in an in-memory identity map per request. Call `evict($id)` or `clearCache()` to invalidate.

### status CLI command

```bash
bin/magento bettermagento:status
```

Prints a table of all enabled `BetterMagento_*` modules with their setup version and whether they declare a dependency on Core.

### Admin dashboard widget

Add to the admin dashboard layout to show all installed BetterMagento modules:

```xml
<!-- view/adminhtml/layout/adminhtml_dashboard_index.xml -->
<block class="BetterMagento\Core\Block\Adminhtml\BmModuleInfo"
       name="bettermagento.module.info"
       template="BetterMagento_Core::module-info.phtml"
       before="-"/>
```

Or simply push the supplied `view/adminhtml/layout/adminhtml_dashboard_index.xml` into a Magento install.

### Generic actions column for admin grids

```xml
<column name="actions" class="BetterMagento\Core\Ui\Component\Listing\Column\BmActions">
    <settings>
        <label translate="true">Actions</label>
    </settings>
    <argument name="data" xsi:type="array">
        <item name="config" xsi:type="array">
            <item name="editUrlPath"   xsi:type="string">mymodule/entity/edit</item>
            <item name="deleteUrlPath" xsi:type="string">mymodule/entity/delete</item>
            <item name="indexField"    xsi:type="string">entity_id</item>
        </item>
    </argument>
</column>
```

---

## Integration tests

```bash
# Standalone (auto-skip, no Magento required)
./vendor/bin/phpunit -c phpunit.integration.xml.dist

# Full (requires a Magento install)
export MAGENTO_ROOT=/var/www/magento
./vendor/bin/phpunit -c phpunit.integration.xml.dist
```

---

## Running tests

```bash
cd packages/core
# Manual install only (skip composer install for now)../vendor/bin/phpunit
```

### Static analysis

```bash
./vendor/bin/phpstan analyse
```

---

## Dependencies

| Package | Version | Purpose |
|---------|---------|---------|
| `magento/framework` | ≥ 107.0 | Magento DI, config, logging |
| `psr/log` | ^3.0 | PSR-3 logger interface |
| `monolog/monolog` | ^3.0 | Concrete log handler |

---

## Changelog

### 0.4.0 — 2026-02-28
- Synchronized module.xml setup_version with composer.json
- Version bump 0.3.0 → 0.4.0

### 0.3.0 — 2026-02-27
- `Block/Adminhtml/BmModuleInfo` — admin dashboard widget showing BM module versions + Core dep indicator
- `Ui/Component/Listing/Column/BmActions` — configurable Edit/Delete action column for any BM grid
- `view/adminhtml/templates/module-info.phtml` — HTML template for the widget
- `view/adminhtml/layout/adminhtml_dashboard_index.xml` — dashboard layout wiring
- `Test/Integration/` — integration test scaffold with dual-mode bootstrap (standalone/full Magento)
- `phpunit.integration.xml.dist` — separate PHPUnit config for integration suite
- CI: `--coverage-min-func 80` gate + integration job (auto-skip in CI)
- 12 new unit tests (BmModuleInfo ×8, BmActions ×4)
- Version bump 0.2.0 → 0.3.0

### 0.2.0 — 2026-02-27
- `RepositoryInterface` — generic CRUD contract with `getById`, `save`, `delete`, `deleteById`, `getList`
- `AbstractRepository` — in-memory identity-map cache, Magento `SearchResults` integration
- `StatusCommand` — `bin/magento bettermagento:status` with module table + Core-dep indicator
- `AbstractBmCommandTest` — full unit test suite for CLI framework (8 tests)
- `AbstractRepositoryTest` — 10 unit tests covering cache, CRUD, exception wrapping
- `.github/workflows/ci.yml` — PHPUnit + PHPStan on PHP 8.2 + 8.3

### 0.1.0 — 2026-02-27
- Initial release
- `ConfigInterface` + `Model\Config` — admin config abstraction
- `LoggerInterface` + `Model\Logger` — structured BM logger (`var/log/bettermagento.log`)
- `ResultInterface` + `Model\Data\Result` — immutable result value object
- `AbstractBmCommand` — base CLI command with colored output & timing
- `etc/config.xml` — default config values
- `etc/adminhtml/system.xml` — BetterMagento admin section
- `etc/acl.xml` — `BetterMagento_Core::config` ACL resource
- `etc/di.xml` — interface preferences + Monolog handler wiring
- Full unit test suite (18 assertions)
- `phpunit.xml.dist` + `phpstan.neon` (level 8)
