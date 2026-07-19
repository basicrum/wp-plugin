# Contributing to Basicrum

Thank you for improving Basicrum. Contributions should preserve the plugin's privacy-first defaults, WordPress compatibility boundaries, and installable ZIP quality.

## Before starting

- Search existing issues and pull requests before opening a duplicate.
- Use a public issue for ordinary defects and proposals.
- Follow [SECURITY.md](SECURITY.md) for suspected vulnerabilities.
- Keep changes focused and explain any administrator-visible behavior change.

## Local setup

Docker, Docker Compose, and Make are required.

```bash
cp .env.example .env
make up
```

The development site is available at <http://localhost:9080/> with `admin` / `basicrum-dev-password`. Run `make help` to list the available checks and test environments.

## Source and conventions

The installable plugin is [`plugins/basicrum/`](plugins/basicrum/). Repository root files provide development, browser-test, Docker, CI, and release tooling.

Follow the permanent conventions in [AGENTS.md](AGENTS.md), including WordPress Coding Standards, PHP 7.4 compatibility, ASCII hyphens, synchronized version metadata, privacy-safe consent behavior, and immutable GitHub Actions pins.

When changing user-facing text, regenerate the WordPress POT template. Locale-specific PO and MO files are not bundled. When changing settings, keep defaults, rendering, validation, runtime behavior, tests, and documentation synchronized.

## Verification

Run the checks proportionate to the change. At minimum, PHP changes normally require:

```bash
make lint-php
make lint
make analyse
make unit
make conventions
```

Run `make js-test` for browser or consent behavior. Run `make integration-setup` before the first `make integration` invocation for WordPress integration changes, and use `make woocommerce-e2e` for WooCommerce page types. Changes that affect packaged files or dependencies must also pass:

```bash
make package
make package-verify
make package-smoke
```

The complete release gate is listed in [AGENTS.md](AGENTS.md).

## Pull requests

- Describe the problem, the chosen behavior, and the verification performed.
- Add or update tests for behavior changes and regressions.
- Do not include generated release ZIPs, local configuration, dependencies, or ignored reference material.
- Do not advance release or compatibility metadata without the corresponding release checks.
