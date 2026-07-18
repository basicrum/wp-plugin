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

## Project Skills

Repository-owned workflows live in `.agents/skills/`. Claude discovers the
same canonical files through links in `.claude/skills/`.

- `change-basicrum-settings` - options, validation, dependencies, migrations,
  and runtime gates.
- `update-basicrum-copy` - naming, administrator copy, branding, and gettext
  catalogs.
- `integrate-basicrum-consent` - external consent adapters, callback contracts,
  and privacy-critical browser tests.
- `audit-basicrum-privacy` - data flows, cookies, consent, disclosures, and
  privacy compliance-readiness reviews.
- `extend-basicrum-page-types` - WordPress and WooCommerce `p_type` detection
  and beacon verification.
- `run-basicrum-test-environments` - local WordPress and inspectable
  WooCommerce stacks.
- `repair-basicrum-ci` - GitHub Actions and PHP/WordPress matrix failures.
- `release-basicrum-plugin` - packaging, compatibility metadata, checksums, and
  release gates.

Use the narrowest matching skill, and combine skills when a change crosses
boundaries. Keep permanent repository invariants here; put repeatable procedures
in the matching skill.

## Conventions

- Follow WordPress-Core/WPCS. The text domain is `basicrum`.
- Guard PHP files with `ABSPATH`; escape output and sanitize all input.
- Use ASCII hyphens (`-`); do not use typographic dashes in source, comments,
  documentation, or user-facing text.
- Keep the plugin header Version, `BASICRUM_VERSION`, `Stable tag`, and top
  changelog version identical. Release tags use the `v<version>` form.
- Prefix hooks with `basicrum_`; use `Assets` handle constants rather than
  hard-coded script handles.
- Boomerang lives in `assets/js/boomr/`; standard and consent loaders live in
  `assets/js/loaders/`.
- WooCommerce browser E2E tests are root-level test tooling. Keep the pinned
  WooCommerce version and checksum synchronized in `tools/setup-woocommerce-e2e.sh`.
  The setup disables WooCommerce Coming soon mode so anonymous storefront tests
  can see the seeded product.
  Review and update each Docker image tag and digest together in
  `docker/woocommerce-e2e.yml`.
- Pin third-party GitHub Actions to full 40-character commit SHAs. Keep the
  reviewed release tag in the adjacent comment and update pins through reviewed
  Dependabot pull requests.
- Keep the PHP unit matrix continuous from the declared minimum through the
  newest supported PHP release. Use only WordPress/PHP combinations supported
  by WordPress core; treat WordPress trunk as a non-blocking early-warning job.
- Change `Tested up to` only after the blocking integration rows for that stable
  WordPress release train pass. Use its major and minor version, never a beta,
  nightly, trunk, or patch version.

## Checks

Run from the repository root:

```bash
make composer-install
make lint-php
make lint
make analyse
make composer-validate
make composer-audit
make conventions
make unit
make js-test
make woocommerce-e2e
make woocommerce-e2e-up
make woocommerce-e2e-down
make integration-setup
make integration
make translations
```

Unit tests use Brain\Monkey; integration tests use the WordPress test suite.
