---
name: release-basicrum-plugin
description: Build and verify installable Basicrum WordPress releases. Use for packaging, Composer production autoloading, version metadata, Tested up to, compatibility matrices, action pins, permissions, checksums, ZIP smoke tests, or release gates.
---

# Release Basicrum Plugin

Test the artifact users install, not only the source checkout.

## Preserve the package boundary

- Treat `plugins/basicrum/` as the installable plugin and repository root files
  as development tooling unless explicitly copied by the release builder.
- Build through `tools/build-release.sh`. Install Composer production
  dependencies in a temporary tree with an optimized authoritative autoloader;
  do not replace normal Composer PSR-4 loading with a handwritten runtime
  autoloader.
- Include referenced runtime PHP, JavaScript, CSS, images, translations, readme,
  license, and production vendor files.
- Exclude tests, Playwright and Node files, examples, Docker files, agent skills,
  local configuration, caches, and development dependencies.
- Generate the SHA-256 checksum from the exact ZIP that passed smoke testing.

## Keep release metadata and support claims honest

Keep the plugin header Version, `BASICRUM_VERSION`, `Stable tag`, top changelog
version, and `v<version>` release tag identical. Advance `Tested up to` only
after blocking integration rows for that stable WordPress train pass. Never use
beta, nightly, trunk, or a patch version for that field.

Keep PHP unit coverage continuous from 7.4 through the newest supported release.
Keep minimum WordPress, current stable, and non-blocking trunk integration roles
explicit and use combinations supported by WordPress core.

## Harden release automation

- Pin third-party GitHub Actions to reviewed full commit SHAs and keep adjacent
  release-tag comments synchronized.
- Review Dependabot release notes, permissions, runtime changes, and CI before
  merging updates.
- Keep default workflow permissions read-only. Grant `contents: write` only to
  release jobs that attach artifacts.
- Run Composer validation, auditing, PHPStan, translation reproducibility,
  repository conventions, and generated-asset checks.

## Exercise the artifact

```bash
make package
make package-verify
make package-smoke
```

Inspect the ZIP listing, install it into clean WordPress, activate through
WP-CLI, render an admin and frontend request, confirm the loader appears with
valid settings, and reject PHP warnings or fatals.

Before a production release, run every check listed in `AGENTS.md`, confirm the
blocking CI matrix is green, and exercise release-workflow-only dependency
updates through a prerelease. Report any gate not actually run.
