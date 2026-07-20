# Basicrum Plugin Source

This directory contains the installable Basicrum WordPress plugin. Runtime PHP, JavaScript, CSS, images, the gettext template, and production Composer dependencies in the release ZIP all come from this boundary. Locale-specific PO and MO files are not bundled.

## Entry points

- `basicrum.php` contains the WordPress plugin header and bootstrap.
- `src/Plugin.php` registers plugin services.
- `src/Assets.php` controls frontend monitoring injection.
- `src/Admin/Settings/` contains settings rendering and validation.
- `readme.txt` is the canonical WordPress.org user documentation and changelog.
- `THIRD-PARTY-NOTICES.txt` records bundled software with separate licenses.
- `.distignore` defines development files excluded from releases.

## Development

Run development and test commands from the repository root. The root [README](../../README.md) contains the quick start, and [AGENTS.md](../../AGENTS.md) lists the complete required checks and repository conventions.

Do not distribute a ZIP made directly from this directory. Run `make package` from the repository root so Composer production dependencies are rebuilt and the resulting archive is verified.
