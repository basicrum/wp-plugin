# Basicrum WordPress Plugin

`plugins/basicrum/` is the installable plugin. It uses the `Basicrum\WP`
namespace, PSR-4 Composer autoloading, PHP 7.4+, and WordPress 6.0+.

## Structure

- `basicrum.php` is the bootstrap; `src/Plugin.php` registers services.
- `src/Assets.php` injects Boomerang and config; `PageTypeDetector.php` sets
  `p_type`; `Compatibility.php` handles cache-plugin exclusions.
- The standard loader is used without consent mode; consent mode uses the
  consent loader and exposes `OPT_IN_BASIC_RUM()` / `OPT_OUT_BASIC_RUM()` for
  integration with a site's consent banner.
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
make integration-setup
make integration
make translations
```

Unit tests use Brain\Monkey; integration tests use the WordPress test suite.
