---
name: repair-basicrum-ci
description: Diagnose and repair Basicrum GitHub Actions failures across PHP, WordPress, and quality jobs. Use for unit, integration, translation, JavaScript, WooCommerce, package, Composer, MySQL, action-runtime, permission, or matrix failures.
---

# Repair Basicrum CI

Fix the root cause across the intended matrix, not only the first failing row.

## Read the failure precisely

1. Read the exact failed step, exit code, surrounding output, job runtime, PHP
   version, WordPress version, and dependency resolution.
2. Separate annotations and deprecation warnings from the command that actually
   failed. A Node runtime warning on an action is not automatically the job
   failure.
3. Group failures by common step. All matrix rows failing before tests usually
   indicate workflow or setup trouble; one boundary row often indicates runtime
   compatibility.
4. Inspect the workflow and the invoked script together. Do not patch YAML while
   leaving the shared local script broken, or vice versa.

## Reproduce the relevant boundary

- Match the failing PHP and WordPress versions when possible.
- Check Composer dependency constraints against PHP 7.4 before accepting a fix
  that works only on current PHP.
- Keep WordPress test installation portable across available MySQL clients and
  archive tools.
- Treat MySQL authentication, service readiness, and host/socket selection as
  distinct setup concerns.
- For action runtime warnings, update to a reviewed upstream action release and
  pin its full 40-character SHA with the release tag in the adjacent comment.
- Preserve minimal workflow permissions and the non-blocking status of the
  WordPress trunk early-warning row.

Do not suppress warnings, drop supported matrix rows, make experimental jobs
blocking, or loosen checks merely to make a run green.

## Verify locally and in CI

Run the narrow failing command first, then the repository targets for every
surface changed. Common gates are:

```bash
make composer-validate
make composer-audit
make lint-php
make lint
make analyse
make unit
make integration-setup
make integration
make js-test
make woocommerce-e2e
make package-smoke
make conventions
```

Run only the relevant subset locally, but state what was not run. After pushing,
inspect the new workflow run; local success cannot prove every matrix row. Do
not claim a fix from a warning-free annotation list while a later step still
fails.
