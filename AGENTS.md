# Basicrum WordPress Plugin

`plugins/basicrum/` is the installable plugin. It uses the `Basicrum\WP`
namespace, PSR-4 Composer autoloading, PHP 7.4+, and WordPress 6.0+.

## Structure

- `basicrum.php` is the bootstrap; `src/Plugin.php` registers services.
- `src/Assets.php` injects Boomerang and config; `PageTypeDetector.php` sets
  `p_type`; `Compatibility.php` handles cache-plugin exclusions; `Admin/Privacy.php`
  contributes editable WordPress privacy-policy text.
- Immediate loading uses the standard loader. Consent-controlled loading uses
  the consent loader and exposes `OPT_IN_BASICRUM_LOADER_WRAPPER()` /
  `OPT_OUT_BASICRUM_LOADER_WRAPPER()` for integration with a site's external
  consent tool. Load the adapter after the consent loader and call one callback
  on every page. Basicrum does not persist consent across page loads.
- Settings use the `basicrum_settings` option. Keep defaults, UI, and
  sanitization in sync in `Helpers`, `Admin\Settings\Page`, and
  `Admin\Settings\Validate`.
- Add version-gated migrations to `Admin\Upgrades`; use the option/version
  constants from `Helpers`.

## Conventions

- Follow WordPress-Core/WPCS. The text domain is `basicrum`.
- Guard PHP files with `ABSPATH`; escape output and sanitize all input.
- Use ASCII hyphens (`-`); do not use typographic dashes in source, comments,
  documentation, or user-facing text.
- Prefix hooks with `basicrum_`; use `Assets` handle constants rather than
  hard-coded script handles.
- Boomerang lives in `assets/js/boomr/`; standard and consent loaders live in
  `assets/js/loaders/`.

## Checks

Run from the repository root:

```bash
make composer-install
make lint-php
make lint
make analyse
make composer-validate
make composer-audit
make unit
make js-test
make integration-setup
make integration
make translations
```

Unit tests use Brain\Monkey; integration tests use the WordPress test suite.
