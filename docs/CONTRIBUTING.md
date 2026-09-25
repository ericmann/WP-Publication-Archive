# Contributing

## Environment

```bash
composer install && npm install
npx wp-env start          # runs bin/setup-wp-env.sh after start
composer verify            # lint, analyse, test:map, test (needs wp-env)
composer verify:static     # the Docker-free subset
```

Host PHP may be newer than 7.4; `composer.json` pins `config.platform.php`
so the lock file resolves for the supported minimum.

## Rules that are checked mechanically

See CLAUDE.md § Constraints and `docs/SPEC.md` §3. Every one is a
`constraints` entry in `docs/foundry.json` and runs on every Foundry task,
and the reviewer treats a violation as the most severe finding. The short
version:

- Names live in `Keys`. Options are read in `Flags`. Hooks are registered in
  `Plugin` and fired in `Hooks`. Time comes from `Clock`.
- Every class under `includes/` has a test file with the same slug.
- Stubs are exactly `throw new NotImplementedException( __METHOD__ );`.
- `phpcs:ignore` needs `-- reason:` on the same line.

## Styles and assets

Add styles to `assets/css/base.css`; `Assets::register()` already registers
it under the 3.0.1 handle with `Keys::ASSET_VERSION`, and
`Assets::enqueue_front()` enqueues it when the plugin is enabled. Design
tokens go on `:root` with the `--eam-` prefix. Tests that freeze time use
`new FixedClock( Keys::EPOCH )`.

## Adding a hook

1. Add the constant to `includes/class-keys.php`.
2. Add a static method to `includes/class-hooks.php`.
3. Document it in `docs/HOOKS.md`.
4. Add a test in `tests/integration/test-hooks.php`.

## Adding a service

1. Create `includes/class-<slug>.php` in the `WPPA` namespace with a file
   docblock naming the SPEC section it implements and an `@author` tag
   (`composer test:map` checks both).
2. Construct it in `Plugin::__construct()`, add an accessor and a `replace()`
   case.
3. Create `tests/unit/test-<slug>.php` or `tests/integration/test-<slug>.php`.
4. `composer dump-autoload -o`.

## The DAM

`bash bin/fetch-dam.sh` clones the VIP Digital Asset Manager at the pinned
`DAM_REF` (`bin/wp-env.conf`) into `.cache/vip-digital-asset-manager` and
maps it into wp-env through `.wp-env.override.json` (gitignored). It needs
SSH access to `github.a8c.com`; without it the fetch fails and `bin/test.sh`
fails loudly rather than silently skipping the DAM group.

- `WPPA_DAM=0 composer test` runs the suite without the DAM, excluding
  `@group dam` tests.
- With the DAM fetched (the default, `DAM=1`), `composer test` sets
  `WPPA_TEST_DAM=1`, loads the DAM before this plugin, and excludes
  `@group nodam` tests instead.
- `phpstan/stubs/vip-dam.php` stubs the DAM's public surface so
  `composer analyse` never needs a DAM checkout, including in CI.
- DAM symbols (`VIP\DAM\*`, `VIP_DAM_*`) may appear only in `includes/class-dam-bridge.php`,
  `tests/` and `phpstan/stubs/` (P14).

## Committing vendor/

Default: `vendor/` is ignored and CI installs it.

## Commit messages

```
<ID>: <title>

Goal: <one sentence>
Tests: <files and test names added or changed>
Closes: <D-items closed, or "none">
Interpretation: <every choice the task text left open, or "none">
Measurement: <tuning tasks only, else "n/a">
Manual check: <"n/a", or "NOT VERIFIED (human): <what to check>">
```
