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
make package
```

### Local WordPress

`make up` installs Composer dependencies, starts the stack, provisions
WordPress on a new database, and activates Basicrum. The development site is at
`http://localhost:8080/wp-admin/`.

Default local administrator credentials are `admin` /
`basicrum-dev-password`. Copy `.env.example` to `.env` to override the
port, database, site, or administrator values. `make down` preserves the
site data; `make clean` resets it.

## Plugin Source

The WordPress plugin itself lives in `plugins/basicrum/`.

If you want to work with Composer directly instead of using `make`, run commands from that directory.
