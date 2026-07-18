# Basicrum Workspace

This repository now uses a small monorepo-style layout.

## Layout

- `plugins/basicrum/` - the installable WordPress plugin package
- `docker/` - Dockerfiles for local development tooling
- `tools/` - helper scripts for local development and testing
- `plan-refs/` - planning notes and ADRs
- `plugin-references/` - external reference implementations kept for study

## Common Commands

From the repository root:

```bash
make build
make up
make composer-install
make unit
make integration-setup
make integration
make lint
make analyse
make composer-validate
make composer-audit
make conventions
make translations
make js-test
make woocommerce-e2e
make woocommerce-e2e-up
make package
make package-smoke
```

### Local WordPress

`make up` installs Composer dependencies, starts the stack, provisions
WordPress on a new database, and activates Basicrum. The development site is at
`http://localhost:9080/wp-admin/`.

Default local administrator credentials are `admin` /
`basicrum-dev-password`. Copy `.env.example` to `.env` to override the
port, database, site, or administrator values. `make down` preserves the
site data; `make clean` resets it.

To use an HTTP beacon URL during local testing, enable **HTTP Strictness**
under **Basicrum > Developer Settings**. Keep it disabled on production sites
so HTTP beacon URLs are automatically upgraded to HTTPS.

### Visitor Privacy

Basicrum supports two observable loading policies under **Basicrum > Visitor
Privacy**:

- **Load immediately** loads Boomerang on every eligible page without waiting
  for a consent signal.
- **Follow external consent tool** blocks or loads Boomerang according to the
  current allow or deny decision reported by the site's consent tool. The tool
  calls `window.OPT_IN_BASICRUM_LOADER_WRAPPER()` when monitoring is allowed and
  `window.OPT_OUT_BASICRUM_LOADER_WRAPPER()` when monitoring is denied, expires,
  or is withdrawn.

The callbacks are registered when the consent loader reaches its configured
Script Position. The consent integration must run after those callbacks are
available and call one of them on every page. Basicrum does not persist a
separate consent choice across page loads; the external tool is authoritative
on every page. If consent is withdrawn after Boomerang loading starts, reload
the page before granting it again.

An allow decision does not always mean that the visitor clicked an accept
button. Region-aware tools such as WP Consent API may allow a category before
interaction in an opt-out region while denying it by default in an opt-in
region. Basicrum follows the decision reported by the external tool; the site
owner remains responsible for configuring that tool and the applicable legal
basis.

Load the adapter after the Basicrum consent loader and use this shape:

```js
function reportBasicrumConsent(allowed) {
  const callbackName = allowed
    ? 'OPT_IN_BASICRUM_LOADER_WRAPPER'
    : 'OPT_OUT_BASICRUM_LOADER_WRAPPER';

  if (typeof window[callbackName] === 'function') {
    window[callbackName]();
  }
}
```

Tested adapters for Borlabs Cookie 3.0.6+, WP Consent API with Complianz or
CookieYes, and connected CookieYes without WP Consent API are available under
[`examples/integrations/`](examples/integrations/).

Basicrum does not display a consent popup, select a legal basis, or make a site
compliant by itself. The WordPress Privacy Policy Guide includes editable
suggested disclosure text for the configured collector and loading policy.

## Plugin Source

The WordPress plugin itself lives in `plugins/basicrum/`.

If you want to work with Composer directly instead of using `make`, run commands from that directory.

## Release Artifact

`make package` builds `release/basicrum.zip`, generates
`release/basicrum.zip.sha256`, and verifies that the archive contains the
required runtime files without tests or other development files. The build runs
`composer install --no-dev --classmap-authoritative` in a temporary directory,
so the ZIP contains a fresh, optimized production autoloader without modifying
the development checkout.

Run `make package-smoke` before a release. It installs the generated ZIP into a
fresh, isolated WordPress instance, activates it with WP-CLI, enables tracking,
and requests both the Basicrum administration page and the frontend. The test
also checks that the frontend loader is present and that neither request nor the
WordPress logs contain PHP warnings or fatal errors.

CI runs this same build and smoke-test path and retains both the ZIP and its
SHA-256 checksum as workflow artifacts. Release and pre-release workflows attach
both files to the GitHub release.

## Automated Quality Controls

`make js-test` installs the locked root-level Node dependencies and runs the
standard and consent loader behavior suites in Chromium. The tests execute the
actual minified and unminified files from `plugins/basicrum/assets/js/loaders/`,
intercept the synthetic Boomerang request, and block every unexpected network
request. They cover consent gating, repeated opt-in, opt-out cleanup, and cookie
behavior on HTTP, HTTPS, localhost, and subdomains.

### WordPress and PHP compatibility policy

CI runs the unit suite on PHP 7.4, 8.0, 8.1, 8.2, 8.3, 8.4, and 8.5. PHP 7.4
and 8.0 remain in the matrix because the plugin declares PHP 7.4 as its minimum;
they are compatibility targets, not recommended runtimes for new production
sites.

The integration suite uses supported boundary combinations instead of a full
WordPress and PHP cross-product:

- PHP 7.4 and PHP 8.0 with the minimum supported WordPress 6.0 release train.
- PHP 8.4 and PHP 8.5 with the explicitly tested current stable WordPress
  release train.
- PHP 8.5 with WordPress trunk as a non-blocking early-warning target.

An explicit stable value such as `7.0` resolves through the official WordPress
version API to the latest patch in that release train.

When WordPress publishes a new stable release train:

1. Confirm its PHP compatibility in the official
   [WordPress compatibility table](https://make.wordpress.org/core/handbook/references/php-compatibility-and-wordpress-versions/).
2. Update the explicit stable WordPress value in `.github/workflows/ci.yml`.
3. Run the required unit and integration matrix and resolve any failures.
4. Only after the stable integration rows pass, update `Tested up to` in
   `plugins/basicrum/readme.txt` using the WordPress major and minor version.
5. Never advance `Tested up to` from a beta, nightly, or trunk result. Keep
   `Requires at least` and `Requires PHP` unchanged unless support for those
   boundaries is deliberately dropped.

`make woocommerce-e2e` starts an isolated WordPress 6.9 and WooCommerce 10.9.4
stack, seeds a product, payable order, and completed order, then uses Playwright
to visit the shop, product, cart, checkout, order-pay, and order-received pages.
It intercepts the synthetic collector and verifies the actual Basicrum beacon
`p_type` values.
WooCommerce Coming soon mode is disabled in this isolated stack, so the shop
also proves that the seeded product is visible to anonymous visitors.
The WooCommerce download is pinned and checksum-verified in
`tools/setup-woocommerce-e2e.sh`; update its version and checksum together.
The stack's Docker image tags and immutable digests are reviewed together in
`docker/woocommerce-e2e.yml`.

To inspect the same seeded WooCommerce storefront manually, run
`make woocommerce-e2e-up`. It exposes an isolated stack at
`http://localhost:9081/` with `admin` / `basicrum-e2e-password`; use
`make woocommerce-e2e-down` to stop and reset it. Set
`WOOCOMMERCE_E2E_PORT` to use a different host port. The inspectable stack
keeps its own data and plugin copy, so run `make woocommerce-e2e-down` and then
`make woocommerce-e2e-up` after changing plugin source.

### WooCommerce page-type acknowledgments

The WooCommerce `p_type` taxonomy and its specificity order were informed by
[WooCommerce Conditional Tags](https://developer.woocommerce.com/docs/theming/theme-development/conditional-tags),
[WooCommerce core conditional functions](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/includes/wc-conditional-functions.php),
and the open-source [GTM4WP WooCommerce integration](https://github.com/duracelltomi/gtm4wp/blob/master/integration/woocommerce.php).
They inspired the page-type ideas only; Basicrum has its own implementation and
does not copy source code from those projects.

The JavaScript package, Playwright configuration, and browser tests live at the
repository root. The release builder copies only `plugins/basicrum/`, and the
release verifier also rejects Node or Playwright development files if they ever
appear in the plugin ZIP.

`make analyse` runs PHPStan level 5 with WordPress-aware stubs. The enforced
configuration is stored in `plugins/basicrum/phpstan.neon.dist`.

`make composer-validate` checks Composer metadata and lock-file consistency in
strict mode. `make composer-audit` checks the complete lock file against current
security advisories. CI runs all three checks on every push and pull request.

`make conventions` rejects en dash and em dash characters in tracked text files.
It also verifies that the plugin header version, `BASICRUM_VERSION`, WordPress
`Stable tag`, and top changelog version match. Release workflows additionally
compare the GitHub release tag to that version. Release tags must use the
`v<version>` form, such as `v1.0.2`. To check a planned release locally, run
`BASICRUM_RELEASE_TAG=v<version> make conventions`.

Dependabot checks npm, Composer dependencies, and GitHub Actions weekly. CI
workflows use a read-only `GITHUB_TOKEN`; release and pre-release workflows
receive only the `contents: write` permission required to attach release assets.

### GitHub Actions supply-chain policy

Every third-party GitHub Action is pinned to a full 40-character commit SHA.
The comment beside each pin identifies the reviewed upstream release tag.
Dependabot opens weekly GitHub Actions update pull requests. Before merging one,
verify that the proposed SHA belongs to the expected upstream release, review the
upstream changes and permissions, keep the SHA and release-tag comment in sync,
and require CI to pass. Exercise an update used only by a release workflow with
a prerelease before using it for a production release. Apply the same review when
making a manual action update; never replace a pin with a mutable tag.
