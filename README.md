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
make translations
make js-test
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
- **Wait for visitor consent** blocks Boomerang until the site's external
  consent tool calls `window.OPT_IN_BASICRUM_LOADER_WRAPPER()` on the current
  page. The tool must call `window.OPT_OUT_BASICRUM_LOADER_WRAPPER()` when
  consent is rejected, expires, or is withdrawn.

The callbacks are registered when the consent loader reaches its configured
Script Position. The consent integration must run after those callbacks are
available and call one of them on every page. Basicrum does not persist a
separate consent choice across page loads; the external tool is authoritative
on every page. If consent is withdrawn after Boomerang loading starts, reload
the page before granting it again.

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

A tested Borlabs Cookie v3 adapter is available under
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

The JavaScript package, Playwright configuration, and browser tests live at the
repository root. The release builder copies only `plugins/basicrum/`, and the
release verifier also rejects Node or Playwright development files if they ever
appear in the plugin ZIP.

`make analyse` runs PHPStan level 5 with WordPress-aware stubs. The enforced
configuration is stored in `plugins/basicrum/phpstan.neon.dist`.

`make composer-validate` checks Composer metadata and lock-file consistency in
strict mode. `make composer-audit` checks the complete lock file against current
security advisories. CI runs all three checks on every push and pull request.

Dependabot checks npm, Composer dependencies, and GitHub Actions weekly. CI
workflows use a read-only `GITHUB_TOKEN`; release and pre-release workflows
receive only the `contents: write` permission required to attach release assets.
