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

- [ ] Define the intended behavior for `explicit`, `implicit`, `cookie_banner`,
  and `gdpr_banner` modes.
- [ ] Decide whether each mode has distinct runtime behavior.
- [ ] Implement the defined behavior, or remove modes that do not represent real
  behavior and keep a single consent-enabled switch.
- [ ] Keep defaults, settings UI, validation, migrations, documentation, and
  translations synchronized.
- [ ] Add PHP tests for every supported setting and loader selection path.

Acceptance criteria:

- [ ] Every consent option shown to an administrator has observable and documented
  runtime behavior.
- [ ] No unused consent settings remain in stored options or the UI.

## 3. Add JavaScript consent and loader tests

- [ ] Add a JavaScript test runner and a documented test command.
- [ ] Test that Boomerang does not load before consent is granted.
- [ ] Test that opt-in loads Boomerang exactly once.
- [ ] Test that repeated opt-in calls do not load duplicate scripts.
- [ ] Test that opt-out disables Boomerang when it is already loaded.
- [ ] Test removal of Boomerang cookies during opt-out.
- [ ] Test consent cookie behavior on HTTP and HTTPS.
- [ ] Test consent cookie behavior for normal hostnames, localhost, and relevant
  subdomain cases.
- [ ] Run the same behavioral assertions against minified and unminified loaders.
- [ ] Add JavaScript tests to CI.

Acceptance criteria:

- [ ] Privacy-critical loader behavior is covered independently of PHP tests.
- [ ] Minified and unminified loaders pass the same behavior suite.

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

- [ ] Keep the minimum combination: PHP 7.4 and WordPress 6.0.
- [ ] Add the latest stable WordPress release as an explicit integration target.
- [ ] Keep WordPress trunk as an allowed early-warning target.
- [ ] Add PHP 8.0 so the declared `>=7.4` range has no untested gap.
- [ ] Add PHP 8.4 and PHP 8.5 unit coverage.
- [ ] Test the latest stable WordPress release with a currently supported PHP
  version.
- [ ] Test WordPress trunk with the newest compatible PHP version.
- [ ] Update `Tested up to` only after the stable integration test passes.
- [ ] Document how compatibility metadata is updated for each release.

Current reference when this checklist was created:

- WordPress latest stable: 7.0.1.
- Plugin `Tested up to`: 6.7.
- Official references:
  - https://en-gb.wordpress.org/download/releases/
  - https://make.wordpress.org/core/handbook/references/php-compatibility-and-wordpress-versions/
  - https://www.php.net/supported-versions.php

Acceptance criteria:

- [ ] CI covers the minimum, latest stable, and forward-looking runtime boundaries
  represented by the plugin metadata.

## 6. Add stronger automated analysis and dependency controls

- [x] Add PHPStan with WordPress stubs and select an initial enforced level.
- [x] Add `composer validate --strict` to CI.
- [x] Add `composer audit --locked` to CI.
- [x] Configure dependency update automation for Composer and GitHub Actions.
- [x] Set explicit minimal `GITHUB_TOKEN` permissions in every workflow.
- [x] Give release workflows only the additional permissions required to upload
  assets.
- [ ] Pin third-party GitHub Actions to immutable commit SHAs.
- [ ] Document how pinned actions are reviewed and updated.

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

- [ ] Add an automated check that rejects en dash and em dash characters in
  tracked source, comments, documentation, and user-facing text.
- [ ] Add a version consistency check covering the plugin header,
  `BASICRUM_VERSION`, `Stable tag`, changelog, and release tag.
- [ ] Run both checks in pull requests and pushes to the main branch.
- [ ] Document the checks in `AGENTS.md` and the contributor documentation.

Acceptance criteria:

- [ ] Repository conventions and version metadata cannot drift silently even when
  changes are made without an agent.

## Final release gate

- [ ] PHP syntax lint passes.
- [ ] PHPCS passes.
- [ ] PHP unit tests pass across the supported PHP matrix.
- [ ] WordPress integration tests pass across the supported WordPress matrix.
- [ ] JavaScript tests pass.
- [x] Static analysis passes.
- [x] Composer validation and audit pass.
- [ ] Generated asset checks pass.
- [x] The packaged ZIP smoke test passes.
- [ ] Version and repository convention checks pass.
