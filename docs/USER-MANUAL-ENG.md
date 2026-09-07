# BetterMage Core — User Manual (English)

Module: `BetterMagento_Core` · Version: 0.4.0 · Magento 2.4.x (validated on 2.4.7)

BetterMage **Core** is the foundation of the BetterMagento suite. It holds the shared
master switch, the shared logger, the global **BetterMagento** admin navigation entry, and
the **BetterMagento Modules** dashboard widget that reports the installed version and Core
dependency status of every BetterMagento module on the store.

> The other BetterMagento modules (**QueryOptimizer**, **LightweightApi**, **RedisTurbo**,
> **TurboCore**, **EdgeDelivery**) all declare a dependency on Core and require it to be
> installed and enabled.

---

## 1. What Core provides

| Capability | Where | Notes |
|---|---|---|
| Master switch | System config `bettermagento_general/general/enabled` | Disables the whole suite when off |
| Shared logger | `var/log/bettermagento.log` | `BetterMagento\Core\Model\Logger`, also `Psr\Log\LoggerInterface` |
| Admin navigation | Content → *BetterMagento* | Parent menu item; modules add their own submenus |
| Module health widget | Admin Dashboard → **BetterMagento Modules** | Lists every installed module, its version and Core dependency status |
| Shared API interfaces | `BetterMagento\Core\Api\*` | Contracts used by all companion modules |

---

## 2. Requirements

- Magento Open Source / Commerce **2.4.x** (tested on 2.4.7)
- PHP 8.1+ (lab: PHP 8.3)
- Composer
- Other BetterMagento modules are optional, but require Core to be present.

> **Packagist note:** `bettermagento/module-core` is distributed as a VCS repository
> (GitHub `MeesyBE/BetterMage-core`). Install from the VCS repo as `dev-main`.

---

## 3. Installation

```bash
# 1) Register the VCS repository and require the module (adjust repo URL for your infrastructure)
composer config repositories.bettermagento-core vcs https://github.com/MeesyBE/BetterMage-core.git
composer require bettermagento/module-core:dev-main

# 2) Enable the module
bin/magento module:enable BetterMagento_Core

# 3) Upgrade the schema / data
bin/magento setup:upgrade

# 4) Compile DI and clear caches (production-ish; also needed after other modules are added)
bin/magento setup:di:compile
bin/magento cache:flush
```

To verify the module is active:

```bash
bin/magento module:status BetterMagento_Core
```

It should report enabled. The **BetterMagento Modules** dashboard widget will then show
`BetterMagento_Core` with a green Core-dependency check.

---

## 4. Configuration

**Path:** Stores → Configuration → **BetterMagento** tab → **General** →
**General Settings** (section `bettermagento_general`).

All Core settings are website/global-scoped; `debug_mode` and `log_level` are global only.

| Field | Config path | Default | Description |
|---|---|---|---|
| **Enable BetterMagento** | `bettermagento_general/general/enabled` | Yes | Master switch for **all** BetterMagento modules. When off, the whole suite is inactive. |
| **Debug Mode** | `bettermagento_general/general/debug_mode` | No | Enables verbose logging to `var/log/bettermagento.log`. **Disable on production.** |
| **Log Level** | `bettermagento_general/general/log_level` | (see source model) | Only shown when Debug Mode is on. Chooses the verbosity of the suite's shared logger. |

### Recommendation

- Keep **Enable BetterMagento = Yes**.
- Keep **Debug Mode = No** in production; enable temporarily to diagnose issues, then re-disable.

---

## 5. Daily use

- **Verify module health:** open the Magento Admin Dashboard; the **BetterMagento Modules**
  table lists every BetterMagento module with its version and a Core-dependency check mark
  (green = dependency satisfied). If any module shows a missing Core dependency, re-run
  `composer require`/`setup:upgrade` for that module.
- **Enable/disable the whole suite:** flip *Enable BetterMagento* in the configuration above.
- **Read logs:** shared suite logging is written to `var/log/bettermagento.log`.

---

## 6. Screenshots

Screenshot from the live BetterMagento lab admin (2.4.7), captured 2026-09-07:

[`docs/screenshots/00-dashboard-modules.png`](screenshots/00-dashboard-modules.png) — Admin
Dashboard showing the **BetterMagento Modules** widget with all six modules installed
(Core 0.4.0, QueryOptimizer 1.1.0, RedisTurbo / LightweightApi / TurboCore / EdgeDelivery
0.2.0) and their Core dependency status.

> **Lab note (r2):** in the lab admin available to the docs worker, the dedicated
> configuration section (`bettermagento_general`) could not be rendered as a screenshot:
> the lab's System Configuration controller returns a 404 (`Page not found`) for the
> authenticated admin user. The configuration field documentation above is taken from the
> module's `etc/adminhtml/system.xml` (exact source of the rendered form). Fixing the lab
> System Configuration routing would allow the config form to be captured directly.