# Troubleshooting — BetterMage Suite for Magento 2.4

The top-10 pitfalls we hit while building and testing BetterMage modules — and how to fix each one.
Companion to [QUICKSTART-EN.md](QUICKSTART-EN.md); read that first if you haven't installed anything yet.

---

## 1. Generated `*Factory` classes don't exist standalone

`SearchResultsFactory`, `JsonFactory`, `PageFactory`, and friends are **generated at runtime**
by Magento's code generator — they are not real classes in the source tree.

- In `Test/bootstrap.php`, guard with `class_exists()` / `interface_exists()` before aliasing or requiring them.
- Gotcha: for **interfaces**, `class_exists()` returns `false` even when the interface exists — always use `interface_exists()` for those.
- Never `require` a generated factory file directly; let Magento's `ObjectManager` / test framework create it.

## 2. Backend blocks fatal via `ObjectManager` (`Template.php:82`)

If your Adminhtml block extends Magento's `Template` block without passing the optional helpers,
Magento falls back to `ObjectManager::getInstance()` — which is fatal in unit tests.

Fix: add nullable constructor params and forward them to the parent:

```php
public function __construct(
    \Magento\Backend\Block\Template\Context $context,
    ?\Magento\Framework\Json\Helper\Data $jsonHelper = null,
    ?\Magento\Framework\Filesystem\Directory\ReadFactory $directoryHelper = null,
    array $data = []
) {
    parent::__construct($context, $data, $jsonHelper, $directoryHelper);
    // ...
}
```

Then pass mocks for `$jsonHelper` / `$directoryHelper` in your unit tests.

## 3. Predis v2 commands go through `__call`

With Predis v2, commands like `get`, `set`, `del`, `scan`, `keys`, `info`, `slowlog` are **not**
real methods — they are dispatched via `__call`. Mocking them with `->method('slowlog')` fails.

Correct pattern:

```php
$client = $this->getMockBuilder(\Predis\Client::class)
    ->disableOriginalConstructor()
    ->addMethods(['slowlog', 'info', 'get', 'set'])
    ->getMock();
```

Rule: add every Predis command you use via `addMethods([...])`, never mock them directly.

## 4. `ScopeConfigInterface::isSetFlag()` returns mixed

The interface declares **no return type** on `isSetFlag()`, so static analysis sees `mixed`.

- In source code: always cast — `(bool) $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED)`.
- In tests: mock the method your source **actually calls**. If the source calls `getValue()`,
  mocking `isSetFlag()` does nothing — check the source first (`getValue` vs `isSetFlag`).

## 5. `willReturnMap` rows must match the call arity exactly

PHPUnit's `willReturnMap()` matches rows by argument count. If your source calls

```php
$this->scopeConfig->getValue(self::XML_PATH_FOO);   // 1 argument
```

then a map row with 3 elements (`[$path, $scope, $storeId, $value]`) will **never match**
and the mock silently returns `null`.

Use a callback instead — it only cares about what you use:

```php
$scopeConfig->method('getValue')
    ->willReturnCallback(fn(string $path) => match ($path) {
        'my/path/enabled' => '1',
        default => null,
    });
```

## 6. Mock concrete classes for `AdapterInterface` and `StoreInterface`

Two Magento interfaces are missing methods your code needs:

| Interface | Missing | Mock this instead |
|-----------|---------|-------------------|
| `ResourceConnection\AdapterInterface` | `lastInsertId()` (sometimes `select()`) | `Magento\Framework\DB\Adapter\Pdo\Mysql` |
| `StoreManagerInterface\StoreInterface` | `getCurrentCurrency()` | the concrete `Magento\Store\Model\Store` |

Mocking the interface leaves you with a mock that can't stub the method you call.

## 7. Use `Psr\Log\LoggerInterface` so `NullLogger` fits

Magento's core `Logger` class implements **both** its own interface and PSR-3.
If you type-hint the Magento-specific class, you can't inject PSR's `NullLogger` in tests.

Type-hint `Psr\Log\LoggerInterface` wherever you only need `log()` / PSR methods —
then `new \Psr\Log\NullLogger()` drops straight into your unit tests.

## 8. Composer: ext-gd / ext-intl missing on CI or containers

Magento needs `ext-gd` and `ext-intl`. If your environment lacks them (slim CI images,
minimal Docker containers), don't install system packages just to resolve dependencies —
use Composer's platform-ignore flags for install-only runs:

```bash
composer install --ignore-platform-req=ext-gd --ignore-platform-req=ext-intl
```

Only use these flags where the extension truly isn't needed at runtime (static analysis,
unit tests). Production and integration-test hosts must have the real extensions.

## 9. Adobe repo `auth.json` via environment (no secrets in the repo)

Magento packages come from Adobe's private repo (`repo.magento.com`). Never commit
`auth.json` with real keys. On CI / containers, inject the keys via environment:

```bash
composer config --global --auth http-basic.repo.magento.com "$MAGENTO_PUBLIC_KEY" "$MAGENTO_PRIVATE_KEY"
```

Verify with `composer config --global --list | grep repo.magento.com`.
Get the keys at `marketplace.magento.com` (My Profile → Access Keys).

## 10. Magento version facts that break naive constraints

Verified against Adobe's repo — pin to reality, not wishful thinking:

| Package | Max real version | Notes |
|---------|-----------------|-------|
| `magento/framework` | `103.0.x` | there is **no** 107.x |
| `magento/module-ui` | `101.2.x` | |
| `magento/module-backend` | `102.0.x` | |
| `magento/module-block` / `module-directory` | `100.4.x` (directory) | |
| `phpstan/phpstan-magento` | **does not exist** (404) | use `bitexpert/phpstan-magento ^0.43` + `phpstan ^2.0` |
| `bettermagento/module-core` | **not on Packagist** | add the Git repo as a VCS source, require `dev-main` |

Also: PHPStan 2.x removed `checkMissingIterableValueType` and
`checkGenericClassInNonGenericObjectType` — drop them from `phpstan.neon` when upgrading.
`BROTLI_ENCODE` and `JSON_SORT_KEYS` don't exist as PHP constants; PHPUnit has
`assertGreaterThan()`, not `assertGreater()`.

---

## Verification checklist

Run these five checks before opening a PR — they catch 90% of red CI:

- [ ] `composer validate --strict` passes (constraints resolve, no Packagist-only assumptions).
- [ ] Unit tests green: `vendor/bin/phpunit -c Test/Unit/phpunit.xml` (or your configured suite) — no `ObjectManager::getInstance()` fatals.
- [ ] PHPStan clean at the configured level (new code introduces zero new errors; baseline untouched).
- [ ] No generated `*Factory` classes referenced without guards; no Predis commands mocked without `addMethods()`.
- [ ] No secrets committed (`git grep -i -E 'private.?key|auth\.json' -- .` returns nothing sensitive).

---

*Start here: [QUICKSTART-EN.md](QUICKSTART-EN.md) — install any BetterMage module in ~15 minutes.*
