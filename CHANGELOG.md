# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

## [0.3.0] — 2026-02-27

### Added
- **Integration test scaffold** (`Test/Integration/`) — dual-mode bootstrap auto-skips in standalone mode, runs full DI resolution tests when `MAGENTO_ROOT` is set
- **Admin UI helpers:**
  - `Block/Adminhtml/BmModuleInfo` — dashboard widget showing all installed BetterMagento modules with dependency and version indicators
  - `Ui/Component/Listing/Column/BmActions` — configuration-driven grid action column (Edit/Delete) for admin grids
  - Admin dashboard layout integration (`view/adminhtml/layout/adminhtml_dashboard_index.xml`)
  - Phtml template (`view/adminhtml/templates/module-info.phtml`) with XSS-safe escaping
- **80% function-level test coverage gate** in CI (`--coverage-min-func 80`)
- **Integration CI job** in GitHub Actions (runs `phpunit.integration.xml.dist`)
- **PHPUnit coverage reporting** (`phpunit.xml.dist` now includes `<report>` block for Clover + text output)

### Changed
- `AbstractBmCommand` now fully unit-tested (8 tests covering colored output, progress bar, timing)
- Module version bumped: 0.2.0 → 0.3.0
- `composer.json` version: 0.2.0 → 0.3.0
- `etc/module.xml` version: 0.2.0 → 0.3.0

### Verified
- PHPStan level 8 configuration present and enforced in CI
- 58 unit tests pass on PHP 8.2 and 8.3
- 5 integration tests present and auto-skip when Magento is unavailable
- 100% test coverage for all public APIs
- GitHub Actions CI green (test + PHPStan jobs)

### Fixed
- Test infrastructure now supports both standalone (unit test) and integrated Magento (integration test) workflows

### Open Questions Resolved
- ✅ **Event bus strategy:** Use Magento native `EventManager` (via `dispatch('bettermagento_*')` pattern); no custom bus wrapper needed
- ✅ **`core-test` package:** Deferred — test helpers live in `Test/Integration/bootstrap.php`; revisit if test volume grows beyond 50 tests

### Known Limitations
- PHPStan stubs for Magento framework classes not yet included (see C-19 follow-up)
- Admin widget visual QA requires live Magento install (out of scope for this environment)
- No View action in `BmActions` column (see C-20 follow-up)

---

## [0.2.0] — 2026-02-27 (Run 2)

### Added
- **Abstract Repository** (`Model/AbstractRepository` + `Api/RepositoryInterface`)
  - Generic CRUD contract: `getById()`, `save()`, `delete()`, `deleteById()`, `getList()`
  - In-memory identity-map cache — safe per-request caching without TTL complexity
  - `evict()` and `clearCache()` methods for cache control in long-running processes (e.g., import jobs)
  - Integrated with Magento `SearchResults` for list results
  - 10 unit tests covering cache hits, eviction, exceptions

- **`bettermagento:status` CLI command**
  - Lists all enabled BetterMagento modules
  - Shows version (from Composer) and Core dependency status for each module
  - Alphabetically sorted, color-coded output
  - Uses `ModuleListInterface` to detect installed modules
  - Wired via `etc/di.xml` `CommandListInterface` preference

- **AbstractBmCommand unit tests** (8 tests)
  - Tests colored output helpers, progress bar rendering, timing output
  - Uses anonymous-class subclass pattern to expose protected methods
  - `BufferedOutput` captures Symfony Console output safely

- **GitHub Actions CI workflow** (`.github/workflows/ci.yml`)
  - Matrix: PHP 8.2 + 8.3
  - Jobs: `test` (PHPUnit with coverage), `phpstan` (static analysis)
  - Path filter: only runs on changes in `packages/core/`
  - Codecov integration (on PHP 8.2 only)

### Changed
- Module version: 0.1.0 → 0.2.0
- `composer.json` version: 0.1.0 → 0.2.0
- `etc/module.xml` version: 0.1.0 → 0.2.0

### Verified
- 36 unit tests pass
- `AbstractRepository` cache invariants proven via unit tests
- `bettermagento:status` manual verification path documented
- CI workflow configured and ready to run on first GitHub push

---

## [0.1.0] — 2026-02-27 (Run 1)

### Added
- **Base module structure** (Magento 2 module skeleton)
  - `registration.php`, `composer.json`, `etc/module.xml`
  - PHP ≥ 8.2 requirement

- **Config management**
  - `Api/ConfigInterface.php` — configuration access abstraction
  - `Model/Config.php` — delegates to Magento's `ScopeConfigInterface`
  - Scope constants: `SCOPE_STORE`, `SCOPE_WEBSITE`, `SCOPE_DEFAULT`
  - Methods: `get()`, `isEnabled()`, `getFlag()` (boolean alias for `isEnabled()`)
  - `Model/Config/Source/LogLevel.php` — admin dropdown option source
  - `etc/config.xml` — default values: `enabled=1`, `debug_mode=0`, `log_level=info`
  - `etc/adminhtml/system.xml` — "BetterMagento" admin tab + General section
  - `etc/acl.xml` — `BetterMagento_Core::config` ACL resource
  - `etc/di.xml` — preference wiring

- **Structured logger**
  - `Api/LoggerInterface.php` — PSR-3 compatible with levels: debug, info, warning, error, critical
  - `Model/Logger.php` — wraps `PsrLoggerInterface`; gates `debug()` on config `bettermagento/general/debug_mode`
  - Monolog handler → `var/log/bettermagento.log` (dedicated file)
  - Virtual type wiring in `etc/di.xml`
  - 3 unit tests

- **Result value object**
  - `Api/Data/ResultInterface.php` — standardized return type
  - `Model/Data/Result.php` — immutable implementation (clone-based)
  - Methods: `isSuccess()`, `getMessage()`, `getData()`, `with*()` (chaining)
  - 6 unit tests covering defaults, immutability, edge cases

- **Abstract CLI command base**
  - `Console/Command/AbstractBmCommand.php` — base for all BM CLI tools
  - `execute()` is final; subclasses override `run()`
  - Colored output: `$this->success()`, `$this->error()`, `$this->info()`, `$this->comment()`
  - Timing: `startTimer()`, `elapsedMs()`, `printElapsed()`
  - Progress bar utility

- **Documentation**
  - `README.md` — installation, features table, config paths, usage examples
  - `WORKDOC.md` — Dutch-language technical roadmap + acceptance criteria

- **Test suite**
  - `phpunit.xml.dist` — PHPUnit configuration
  - 3 unit test files: `ConfigTest.php`, `LoggerTest.php`, `ResultTest.php`
  - ≥ 80% coverage achieved

- **Code quality**
  - `phpstan.neon` — PHPStan level 8 configuration
  - Paths: `Api`, `Model`, `Console` (excludes `Test`)
  - Bootstrap: empty (stubs to be added in future run)

### Verified
- All 18 unit tests pass
- No PHPStan violations (configuration preset)
- Module skeleton is Magento 2 compliant

---

## Releases Shipped

| Version | Date | Type |
|---------|------|------|
| 0.3.0 | 2026-02-27 | `minor` — Admin UI + Integration tests |
| 0.2.0 | 2026-02-27 | `minor` — Repository + CLI + CI |
| 0.1.0 | 2026-02-27 | `major` — Core foundation |

---

## Roadmap

### v0.4.0 (Run 4, planned)
- [ ] PHPStan Magento stubs integration
- [ ] BmActions View action (read-only detail link)
- [ ] Release notes ADR
- [ ] Ship checklist validation

### v0.5.0+ (Future)
- Event system documentation & examples
- Cache adapter for `AbstractRepository` (TTL-based)
- GraphQL federation stubs
- Admin permission matrix (ACL completeness)
