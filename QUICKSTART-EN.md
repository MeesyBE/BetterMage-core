# Quickstart — BetterMage Suite for Magento 2.4

Install any BetterMage module (Core first) with Composer, enable it, and verify it works. ~15 minutes.

> 🌐 HTML version with the same content: `docs-site/quickstart.html` in the docs bundle.

## 1. Requirements

| Component | Required | Check with |
|-----------|----------|------------|
| PHP | `8.2` or `8.3` | `php -v` |
| Composer | 2.x | `composer --version` |
| Magento | 2.4.x (Adobe Commerce or Mage-OS) | `bin/magento --version` |
| Adobe repo keys | public + private key from `marketplace.magento.com` | see step 2 |

> Version constraints that matter: `magento/framework` maxes out at `103.0.x` (there is no 107.x),
> `magento/module-ui` max `101.2.x`, `magento/module-backend` `102.0.x`,
> `magento/module-directory` max `100.4.x`. Composer's solver picks the right patch per PHP
> version — keep the constraints from our `composer.json` files and don't force newer majors.

## 2. Adobe repo authentication

Magento packages come from Adobe's private repo. Create keys at `marketplace.magento.com`
(My Profile → Access Keys), then store them:

```bash
composer config --global --auth http-basic.repo.magento.com <public-key> <private-key>
```

Verify the auth works:

```bash
composer config --global --list | grep repo.magento.com
```

## 3. Register the VCS repository

> ⚠️ `bettermagento/module-core` is **not** on Packagist. You must add the Git repository as a
> VCS source — a plain `composer require bettermagento/module-core` will fail to resolve.

In your Magento project root, add the repository (replace the URL with the repo you were given):

```bash
composer config repositories.bettermagento-core vcs git@github.com:bettermagento/module-core.git
```

This adds the following snippet to your `composer.json`:

```json
"repositories": [
    {
        "type": "vcs",
        "url": "git@github.com:bettermagento/module-core.git"
    },
    {
        "type": "composer",
        "url": "https://repo.magento.com/"
    }
]
```

## 4. Require the package

Only `dev-main` resolves (the single `v1.0.0` tag does not satisfy `^0.1`-style ranges),
so require it explicitly:

```bash
composer require bettermagento/module-core:dev-main
```

Further BetterMage modules (e.g. query-optimizer, redis-turbo) each add their own VCS line and
require the same way. **Core is always required first — no other BetterMage module works without it.**

## 5. Enable and install the module

Run each command separately so failures are easy to locate:

```bash
php bin/magento module:enable BetterMagento_Core
```

```bash
php bin/magento setup:upgrade
```

```bash
php bin/magento setup:di:compile
```

```bash
php bin/magento setup:static-content:deploy -f
```

```bash
php bin/magento cache:flush
```

> Order matters: `module:enable` *before* `setup:upgrade`. Skipping `setup:di:compile` in
> production mode leaves generated factories/interceptors stale.

## 6. Verification checklist

- [ ] Module is registered and enabled: `php bin/magento module:status BetterMagento_Core`
- [ ] Status CLI reports the module: `php bin/magento bettermagento:status`
- [ ] Admin config section exists: Stores > Configuration > BetterMagento > General
- [ ] Log file is writable: `var/log/bettermagento.log` appears after first logged event
- [ ] No setup errors remain: `php bin/magento setup:db:status` reports up to date

## 7. Troubleshooting — top 5

1. **"Could not find package bettermagento/module-core".**
   The VCS repository is missing or misspelled. Re-check step 3 — the package is not on
   Packagist and only resolves via the VCS source.
2. **"Could not find a version matching…" / nothing resolves to `dev-main`.**
   Require `dev-main` explicitly (step 4). Ranges like `^0.1` do not match the only tag (`v1.0.0`).
3. **403/401 from `repo.magento.com`.**
   Adobe auth keys are missing or wrong. Repeat step 2 — public key as username,
   private key as password.
4. **"Your requirements could not be resolved" around `magento/framework`.**
   Something forces a non-existent version (e.g. 107.x — max is 103.0.x). Keep our constraints
   and let the solver pick per your PHP version (8.2/8.3).
5. **Module enabled but admin section / CLI missing.**
   You likely skipped `setup:upgrade` or ran it before `module:enable`. Re-run step 5 in order,
   then `cache:flush`.

> Still stuck? See the full [troubleshooting guide](TROUBLESHOOTING-EN.md) (top-10 pitfalls +
> checklist) and the [FAQ](FAQ-EN.md) for common follow-up questions.
