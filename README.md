# Basicrum Workspace

This repository now uses a small monorepo-style layout.

## Layout

- `plugins/basicrum/` — the installable WordPress plugin package
- `docker/` — Dockerfiles for local development tooling
- `tools/` — helper scripts for local development and testing
- `plan-refs/` — planning notes and ADRs
- `plugin-references/` — external reference implementations kept for study

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

The WordPress development site is exposed at `http://localhost:8080`.

## Plugin Source

The WordPress plugin itself lives in `plugins/basicrum/`.

If you want to work with Composer directly instead of using `make`, run commands from that directory.
