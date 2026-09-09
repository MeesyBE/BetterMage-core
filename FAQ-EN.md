# FAQ — BetterMage Core for Magento 2.4

Frequently asked questions about installing, running, and developing with `bettermagento/module-core`.
New to BetterMage? Start with [QUICKSTART-EN.md](QUICKSTART-EN.md) (~15 min install).
Hitting an error? See [TROUBLESHOOTING-EN.md](TROUBLESHOOTING-EN.md) for the full top-10 with fixes.

---

## Installation

### 1. How do I install BetterMage Core?
Follow [QUICKSTART-EN.md](QUICKSTART-EN.md): add the Git repo as a Composer VCS source,
require `bettermagento/module-core:dev-main`, then run `module:enable`, `setup:upgrade`,
`setup:di:compile`, `setup:static-content:deploy -f`, and `cache:flush` — each as a separate
command, in that order. Verify with `php bin/magento bettermagento:status`.

### 2. Why does `composer require bettermagento/module-core` fail with "could not find package"?
The package is **not on Packagist**. You must register the Git repository as a VCS source first
(see QUICKSTART step 3). A plain `composer require` without the VCS line can never resolve.
See also [TROUBLESHOOTING-EN.md §10](TROUBLESHOOTING-EN.md#10-magento-version-facts-that-break-naive-constraints).

### 3. Why must I require `dev-main` instead of a version like `^0.1`?
Only `dev-main` resolves: the single `v1.0.0` tag does not satisfy `^0.1`-style ranges.
Require it explicitly: `composer require bettermagento/module-core:dev-main`.

### 4. Do I need Core if I only want another BetterMage module (e.g. query-optimizer)?
Yes — **Core is always required first**. No other BetterMage module works without it.
Each extra module adds its own VCS line and is required the same way.

### 5. I get 401/403 from `repo.magento.com`. What now?
Your Adobe repo keys are missing or wrong. Store them with
`composer config --global --auth http-basic.repo.magento.com <public-key> <private-key>`
(public key = username, private key = password; from `marketplace.magento.com` → My Profile →
Access Keys). Never commit `auth.json` with real keys — inject via environment on CI
(see [TROUBLESHOOTING-EN.md §9](TROUBLESHOOTING-EN.md#9-adobe-repo-authjson-via-environment-no-secrets-in-the-repo)).

## Compatibility

### 6. Which Magento versions are supported?
Magento **2.4.x** (Adobe Commerce or Mage-OS). Verified constraints: `magento/framework`
max `103.0.x` (there is no 107.x), `magento/module-ui` max `101.2.x`,
`magento/module-backend` `102.0.x`, `magento/module-directory` max `100.4.x`.
Keep the constraints from our `composer.json` and let the solver pick the patch.

### 7. Which PHP versions are supported?
PHP **8.2 or 8.3** (`"php": ">=8.2"` in `composer.json`). Check with `php -v`.
Composer picks the right Magento patch per PHP version — don't force newer majors.

### 8. Which PHP extensions do I need?
Magento needs `ext-gd` and `ext-intl`. Production and integration-test hosts must have the
real extensions. For install-only runs (static analysis, unit tests on slim CI images) you may use
`composer install --ignore-platform-req=ext-gd --ignore-platform-req=ext-intl`
(see [TROUBLESHOOTING-EN.md §8](TROUBLESHOOTING-EN.md#8-composer-ext-gd--ext-intl-missing-on-ci-or-containers)).

## Common errors

### 9. Module enabled, but admin section / CLI command missing?
You likely skipped `setup:upgrade` or ran it before `module:enable`. Re-run the QUICKSTART
step 5 commands in order (`module:enable` → `setup:upgrade` → `setup:di:compile` →
`static-content:deploy` → `cache:flush`), then check `php bin/magento module:status
BetterMagento_Core` and `php bin/magento bettermagento:status`.

### 10. "Your requirements could not be resolved" around `magento/framework`?
Something forces a non-existent version (e.g. 107.x). See the version-facts table in
[TROUBLESHOOTING-EN.md §10](TROUBLESHOOTING-EN.md#10-magento-version-facts-that-break-naive-constraints)
and keep our constraints.

### 11. "Class SearchResultsFactory / JsonFactory / PageFactory not found" in tests?
These `*Factory` classes are **generated at runtime** — they don't exist standalone.
Guard with `class_exists()` / `interface_exists()` in `Test/bootstrap.php` (note: for interfaces
only `interface_exists()` works), and never `require` a generated factory file directly.
Full detail: [TROUBLESHOOTING-EN.md §1](TROUBLESHOOTING-EN.md#1-generated-factory-classes-dont-exist-standalone).

### 12. Adminhtml block fatals via `ObjectManager` (`Template.php:82`)?
Pass the optional helpers explicitly: add `?JsonHelper $jsonHelper = null` and
`?DirectoryHelper $directoryHelper = null` constructor params, forward them to the parent,
and pass mocks in unit tests.
Full detail: [TROUBLESHOOTING-EN.md §2](TROUBLESHOOTING-EN.md#2-backend-blocks-fatal-via-objectmanager-templatephp82).

### 13. Predis mocks fail (`->method('slowlog')` doesn't work)?
With Predis v2 all commands go through `__call`. Use
`getMockBuilder(Client::class)->disableOriginalConstructor()->addMethods([...])`
with every command you use — never mock them directly.
Full detail: [TROUBLESHOOTING-EN.md §3](TROUBLESHOOTING-EN.md#3-predis-v2-commands-go-through-__call).

### 14. `ScopeConfig::getValue()` mock returns null / `isSetFlag()` behaves oddly?
Two classic traps: `isSetFlag()` is declared without a return type (static analysis sees
`mixed`) — always `(bool)`-cast it in source; and `willReturnMap()` rows must match the call
arity exactly (a 3-element row never matches a 1-argument call). Prefer `willReturnCallback()`,
and mock the method your source **actually calls** (`getValue` vs `isSetFlag`).
Full detail: [TROUBLESHOOTING-EN.md §4](TROUBLESHOOTING-EN.md#4-scopeconfiginterfaceissetflag-returns-mixed)
and [§5](TROUBLESHOOTING-EN.md#5-willreturnmap-rows-must-match-the-call-arity-exactly).

### 15. Anything else before opening a PR?
Run the five pre-PR checks in [TROUBLESHOOTING-EN.md](TROUBLESHOOTING-EN.md#verification-checklist):
`composer validate --strict`, unit tests green, PHPStan clean, no unguarded factories or
directly-mocked Predis commands, no secrets committed. Note `phpstan/phpstan-magento` does not
exist (404) — use `bitexpert/phpstan-magento ^0.43` + `phpstan ^2.0`.

---

*Companions: [QUICKSTART-EN.md](QUICKSTART-EN.md) · [TROUBLESHOOTING-EN.md](TROUBLESHOOTING-EN.md)*
