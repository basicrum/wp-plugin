# Basicrum Plugin Quality Checklist

Work through these items in order. An item is complete only when its implementation,
tests, and documentation requirements are all satisfied.

## 1. Preserve beacon URLs in generated JavaScript

- [x] Replace the display-context `esc_url()` call in `Assets::build_config_js()`
  with URL sanitization appropriate for JavaScript serialization.
- [x] Add a regression test using a beacon URL with multiple query parameters.
- [x] Verify the generated JavaScript contains the original `&` separators and
  does not contain HTML entities such as `&#038;`.
- [x] Run PHP lint, PHPCS, and unit tests.

Acceptance criteria:

- [x] Beacon URLs with query strings reach Boomerang unchanged and remain safely
  encoded through `wp_json_encode()`.

## 2. Resolve the unused consent mode setting

- [x] Define the intended behavior for `explicit`, `implicit`, and `cookie_popup`
  modes.
- [x] Decide whether each mode has distinct runtime behavior.
- [x] Implement the defined behavior, or remove modes that do not represent real
  behavior and keep a single consent-enabled switch.
- [x] Keep defaults, settings UI, validation, runtime behavior, documentation,
  and translations synchronized.
- [x] Add PHP tests for every supported setting and loader selection path.

Decision:

- Immediate loading and consent-controlled loading are the only observable
  policies. The unused `explicit`, `implicit`, and `cookie_popup` development
  values were removed before the first public release.
- Basicrum supplies an integration gate, not a consent popup, legal-basis
  decision, or compliance guarantee.
- In consent-controlled loading, the site's external consent tool is the source
  of truth on every page. Automatic handling loads one unambiguous supported
  adapter after the consent loader; manual handling leaves adapter placement to
  the webmaster. The adapter calls exactly one of the two public callbacks.
  Basicrum does not persist consent across page loads.
- The settings page uses progressive disclosure: automatic handling shows only
  a compact verdict, provider evidence, one next action, and copyable non-secret
  diagnostics. Manual handling reveals the callback contract and copyable
  provider adapters in a separate sibling panel. Blocked automatic states can
  reveal the appropriate manual setup but never save the mode automatically.

Acceptance criteria:

- [x] Every consent option shown to an administrator has observable and documented
  runtime behavior.
- [x] No unused consent settings remain in stored options or the UI.

## 3. Add JavaScript consent and loader tests

- [x] Add a JavaScript test runner and a documented test command.
- [x] Test that Boomerang does not load before consent is granted.
- [x] Test that opt-in loads Boomerang exactly once.
- [x] Test that repeated opt-in calls do not load duplicate scripts.
- [x] Test that opt-out disables Boomerang when it is already loaded.
- [x] Test removal of Boomerang RT and BA cookies during opt-out.
- [x] Test measurement-cookie cleanup for the current host and parent domains.
- [x] Run the same behavioral assertions against minified and unminified loaders.
- [x] Test the Borlabs Cookie 3.0.6+ adapter contract.
- [x] Test the shared WP Consent API adapter for opt-in and opt-out regions.
- [x] Test the connected CookieYes fallback with its documented update payload.
- [x] Add JavaScript tests to CI.

Acceptance criteria:

- [x] Privacy-critical loader behavior is covered independently of PHP tests.
- [x] Minified and unminified loaders pass the same behavior suite.

## 4. Make installation and release artifacts resilient

- [x] Handle a missing `vendor/autoload.php` without an uncaught PHP fatal error.
- [x] Provide a clear administrator-facing error or use a plugin-owned autoloading
  strategy that does not require a generated `vendor` directory.
- [x] Move release packaging into one reusable script used by local builds and CI.
- [x] Build the exact release ZIP during CI.
- [x] Inspect the ZIP for required runtime files and excluded development files.
- [x] Install the ZIP into a clean WordPress instance.
- [x] Activate the packaged plugin through WP-CLI.
- [x] Render an admin request and a frontend request after activation.
- [x] Verify the frontend contains the expected loader when the plugin is enabled.
- [x] Generate and publish a SHA-256 checksum for release artifacts.

Acceptance criteria:

- [x] The generated ZIP, rather than the source checkout, installs, activates, and
  serves requests without warnings or fatal errors.

## 5. Expand the compatibility matrix

- [x] Keep the minimum combination: PHP 7.4 and WordPress 6.0.
- [x] Add the latest stable WordPress release as an explicit integration target.
- [x] Keep WordPress trunk as an allowed early-warning target.
- [x] Add PHP 8.0 so the declared `>=7.4` range has no untested gap.
- [x] Add PHP 8.4 and PHP 8.5 unit coverage.
- [x] Test the latest stable WordPress release with a currently supported PHP
  version.
- [x] Test WordPress trunk with the newest compatible PHP version in the first
  CI run of the expanded matrix.
- [x] Add an isolated WooCommerce E2E suite covering shop, product, cart,
  checkout, order-pay, and order-received page types through actual Basicrum
  beacons.
- [x] Update `Tested up to` only after the stable integration test passes.
- [x] Document how compatibility metadata is updated for each release.

Current compatibility reference:

- WordPress latest stable tested: 7.0.2.
- Plugin `Tested up to`: 7.0.
- Expanded PHP and WordPress matrix: passed in CI run #35 at commit `31851b5`,
  including PHP 8.5 with WordPress trunk.
- Official references:
  - https://en-gb.wordpress.org/download/releases/
  - https://make.wordpress.org/core/handbook/references/php-compatibility-and-wordpress-versions/
  - https://www.php.net/supported-versions.php

Acceptance criteria:

- [x] CI passes the minimum, latest stable, and forward-looking runtime boundaries
  represented by the plugin metadata.

## 6. Add stronger automated analysis and dependency controls

- [x] Add PHPStan with WordPress stubs and select an initial enforced level.
- [x] Add `composer validate --strict` to CI.
- [x] Add `composer audit --locked` to CI.
- [x] Configure dependency update automation for Composer and GitHub Actions.
- [x] Set explicit minimal `GITHUB_TOKEN` permissions in every workflow.
- [x] Give release workflows only the additional permissions required to upload
  assets.
- [x] Pin third-party GitHub Actions to immutable commit SHAs.
- [x] Document how pinned actions are reviewed and updated.

Acceptance criteria:

- [x] Type issues, invalid package metadata, known dependency advisories, and
  excessive workflow permissions are checked automatically.

## 7. Make JavaScript and translation artifacts reproducible

- [ ] Add a documented JavaScript build command.
- [ ] Generate minified standard and consent loaders from their unminified sources.
- [ ] Record the minifier and its version in a lock file.
- [ ] Make CI regenerate the loaders and fail when the committed output differs.
- [ ] Document the source and version of the bundled Boomerang asset.
- [x] Add a repeatable command for generating `languages/basicrum.pot`.
- [x] Add a repeatable command for compiling translation MO files.
- [x] Make CI detect stale POT or MO files.

Acceptance criteria:

- [ ] A clean checkout can reproduce all committed minified and translation assets
  without manual editing.

## 8. Enforce repository conventions in CI

- [x] Add an automated check that rejects en dash and em dash characters in
  tracked source, comments, documentation, and user-facing text.
- [x] Add a version consistency check covering the plugin header,
  `BASICRUM_VERSION`, `Stable tag`, changelog, and release tag.
- [x] Run both checks in pull requests and pushes to the main branch.
- [x] Document the checks in `AGENTS.md` and the contributor documentation.

Acceptance criteria:

- [x] Repository conventions and version metadata cannot drift silently even when
  changes are made without an agent.

## Final release gate

- [ ] PHP syntax lint passes.
- [ ] PHPCS passes.
- [ ] PHP unit tests pass across the supported PHP matrix.
- [ ] WordPress integration tests pass across the supported WordPress matrix.
- [x] JavaScript tests pass.
- [x] Static analysis passes.
- [x] Composer validation and audit pass.
- [ ] Generated asset checks pass.
- [x] The packaged ZIP smoke test passes.
- [x] Version and repository convention checks pass.
