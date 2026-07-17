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
make translations
make package
make package-smoke
```

### Local WordPress

`make up` installs Composer dependencies, starts the stack, provisions
WordPress on a new database, and activates Basicrum. The development site is at
`http://localhost:8080/wp-admin/`.

Default local administrator credentials are `admin` /
`basicrum-dev-password`. Copy `.env.example` to `.env` to override the
port, database, site, or administrator values. `make down` preserves the
site data; `make clean` resets it.

To use an HTTP beacon URL during local testing, enable **HTTP Strictness**
under **Basicrum > Developer Settings**. Keep it disabled on production sites
so HTTP beacon URLs are automatically upgraded to HTTPS.

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
