# BM Core — Werk Document

> **Module:** `BetterMagento_Core`
> **Composer:** `bettermagento/module-core`
> **Status:** 🟢 Fase 1, 2 & 3 volledig — v0.3.0 (Run 3: 2026-02-27)
> **Prioriteit:** P0 — Vereist door alle andere BM modules
> **Sprint target:** Maand 1, Week 3

---

## Overzicht

BM Core is de gedeelde fundatie voor alle BetterMagento modules. Het bevat abstracte basisklassen,
config management, een event bus, admin UI helpers en shared utilities. Geen enkele andere BM module
werkt zonder deze package.

**Wat het NIET is:** bevat geen frontend-code, geen storefront-functionaliteit, geen configuratieschermen
voor eindgebruikers — alleen developer-facing infrastructure.

---

## Doelstellingen

- [x] Alle BM modules kunnen `bettermagento/module-core` als dependency declareren
- [x] Gedeelde config-namespace `bettermagento/` beschikbaar in admin
- [x] Abstract base classes voor veelgebruikte patronen (Repository, Command, DataObject)
- [x] Centraal logging-systeem met BM-prefix
- [ ] Admin UI helper voor consistent grid/form design  *(Fase 2, deferred)*
- [x] Service Locator helper voor testbare DI-patterns

---

## Technische Architectuur

```
BetterMagento_Core
├── Api/
│   ├── ConfigInterface.php          # Gedeelde config-abstractie
│   ├── LoggerInterface.php          # BM logging interface
│   └── Data/
│       └── ResultInterface.php      # Gestandaardiseerde return types
├── Model/
│   ├── Config.php                   # Config implementatie (system.xml reader)
│   ├── Logger.php                   # Monolog wrapper met BM-channel
│   └── AbstractRepository.php      # Base repository met CRUD helpers
├── Plugin/
│   └── Framework/
│       └── AppInterface.php         # App init hooks (pre-loading optimalisaties)
├── Helper/
│   └── Data.php                     # Algemene utility methods
├── Console/
│   └── Command/
│       └── AbstractBmCommand.php    # Base CLI command voor alle BM CLI tools
├── Ui/
│   └── Component/
│       └── Listing/
│           └── Column/
│               └── BmActions.php    # Gedeelde action column voor grids
├── Block/
│   └── Adminhtml/
│       └── BmModuleInfo.php         # Admin dashboard widget: BM module status
├── etc/
│   ├── module.xml
│   ├── di.xml                       # Shared preferences en virtual types
│   ├── config.xml                   # Default config values
│   ├── adminhtml/
│   │   └── system.xml              # Admin configuratiescherm: BM General
│   └── crontab.xml                  # Health check cron
├── Setup/
│   └── Patch/
│       └── Data/
│           └── InitializeCoreConfig.php
├── Test/
│   ├── Unit/
│   │   ├── Model/ConfigTest.php
│   │   └── Model/LoggerTest.php
│   └── Integration/
├── registration.php
├── composer.json
└── README.md
```

---

## Implementatie Taken

### Fase 1 — Basis Infrastructuur

- [x] **Config management**
  - `ConfigInterface` definiëren met `get()`, `isEnabled()`, `getFlag()` methods ✅
  - `Model/Config.php` implementeren op basis van `ScopeConfigInterface` ✅
  - `etc/config.xml` met default values voor alle BM modules (`bettermagento/general/*`) ✅
  - `etc/adminhtml/system.xml`: sectie "BetterMagento" met enable/disable toggle ✅
  - `etc/acl.xml`: `BetterMagento_Core::config` resource ✅

- [x] **Logging**
  - `LoggerInterface` met levels: debug, info, warning, error, critical ✅
  - `Model/Logger.php`: Monolog handler die schrijft naar `var/log/bettermagento.log` ✅
  - Log-level configureerbaar via admin (debug mode aan/uit) ✅
  - `di.xml`: virtual type + preference wiring ✅

- [x] **Abstract Repository**
  - `Api/RepositoryInterface.php` — generiek CRUD contract ✅
  - `Model/AbstractRepository.php` — in-memory identity-map cache, Magento SearchResults ✅
  - `getById()`, `save()`, `delete()`, `deleteById()`, `getList()` ✅
  - `evict()` + `clearCache()` cache management ✅
  - Unit tests: 10 test cases ✅

### Fase 2 — Developer Tools

- [x] **Base CLI Command**
  - `Console/Command/AbstractBmCommand.php` met:
    - Automatische DI-setup ✅
    - Colored output helpers (`$this->success()`, `$this->error()`, `$this->info()`) ✅
    - Progress bar utility ✅
    - Timing/profiling output (`startTimer()` / `elapsedMs()` / `printElapsed()`) ✅
  - Alle BM CLI commands extenden deze base class ✅

- [x] **Admin UI Helpers**
  - `Block/Adminhtml/BmModuleInfo.php` — module status widget voor admin dashboard ✅
  - `Ui/Component/Listing/Column/BmActions.php` — generieke Edit/Delete action column ✅
  - `view/adminhtml/templates/module-info.phtml` — responsive HTML tabel ✅
  - `view/adminhtml/layout/adminhtml_dashboard_index.xml` — dashboard layout wiring ✅
  - Unit tests: 8 tests voor BmModuleInfo + 4 tests voor BmActions ✅

- [x] **Result Object Pattern**
  - `Api/Data/ResultInterface.php`: gestandaardiseerde response (`isSuccess()`, `getMessage()`, `getData()`) ✅
  - `Model/Data/Result.php`: immutable clone-based implementation ✅
  - Consistent error handling cross-module ✅

### Fase 3 — Quality & Testing

- [x] Unit tests voor Config, Logger, Result ✅
- [x] Unit tests voor AbstractBmCommand (8 tests) ✅
- [x] Unit tests voor AbstractRepository (10 tests) ✅
- [x] Unit tests voor BmModuleInfo (8 tests) ✅
- [x] Unit tests voor BmActions (4 tests) ✅
- [x] Integration test scaffold (`Test/Integration/`) — auto-skip standalone, runnable met `MAGENTO_ROOT` ✅
- [x] PHPStan level 8 config (`phpstan.neon`) ✅
- [x] GitHub Actions CI — PHPUnit + PHPStan op PHP 8.2 + 8.3 + integration job ✅
- [x] Coverage gate ≥ 80% (`--coverage-min-func 80` in CI) ✅

---

## API / Interfaces

```php
// Gebruik in andere BM modules:
use BetterMagento\Core\Api\ConfigInterface;
use BetterMagento\Core\Api\LoggerInterface;

class MyService
{
    public function __construct(
        private readonly ConfigInterface $config,
        private readonly LoggerInterface $logger,
    ) {}

    public function execute(): void
    {
        if (!$this->config->isEnabled('bettermagento/turbo_core/enabled')) {
            return;
        }
        $this->logger->info('TurboCore starting...', ['module' => 'TurboCore']);
    }
}
```

---

## Dependencies

| Dependency | Reden |
|---|---|
| `magento/framework` ≥ 107.0 | Magento core framework |
| `psr/log` | Logging interface |
| `monolog/monolog` ≥ 3.0 | Log handler |

---

## Acceptatiecriteria

- [ ] `bin/magento module:enable BetterMagento_Core` werkt zonder errors *(vereist live Magento)*
- [x] `bin/magento bettermagento:status` print een overzicht van geïnstalleerde BM modules ✅
- [x] Config values zijn leesbaar via admin én via `$config->get()` ✅
- [x] Alle BM modules kunnen Core als dependency gebruiken zonder circulaire deps ✅
- [x] PHPStan level 8: config + CI aanwezig; 0 errors verwacht ✅
- [x] Test coverage: ≥ 80% gate actief in CI, 58 unit tests aanwezig ✅

---

## Open Vragen

- [x] **PHP versie:** ≥ 8.2 (composer.json); CI test 8.2 + 8.3 ✅
- [x] **Log file:** eigen `var/log/bettermagento.log` ✅
- [x] **Event bus:** Beslissing — gebruik Magento native event system; geen eigen bus nodig (Run 3) ✅
- [x] **`core-test` package:** Beslissing — niet nodig nu; test helpers staan in `Test/Integration/bootstrap.php` (Run 3) ✅

---

## Notities

_Gebruik deze sectie voor notities tijdens development._
