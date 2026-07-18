---
name: update-basicrum-copy
description: Update Basicrum branding, user-facing copy, documentation, and WordPress gettext catalogs. Use for naming changes, BasicRUM or Basic Rum cleanup, Brum Site ID help text, logos, POT/PO/MO files, or ASCII-hyphen checks.
---

# Update Basicrum Copy

Change source text and every generated or translated representation as one unit.

## Find the full text surface

1. Search case-insensitively across PHP, JavaScript, CSS, Markdown, WordPress
   readme, tests, fixtures, examples, workflows, POT, and PO files.
2. Distinguish source strings from generated catalogs and compiled MO files.
3. Check identifiers separately from display text; do not casually rename stored
   option keys, hooks, handles, or public callbacks.

## Apply project vocabulary

- Use `Basicrum`, never `BasicRUM` or `Basic Rum`.
- Use `Brum Site ID` in administrator-facing text and point users to the
  Basicrum backoffice instead of describing UUID internals.
- Use ASCII hyphens only. Do not introduce en dashes or em dashes.
- Keep statements about consent, privacy, and compliance precise. Basicrum
  follows the external tool's decision; it does not make a site compliant.
- Keep WordPress gettext calls in PHP source. Do not hand-edit binary MO files.
- Update tests that intentionally assert rendered copy without making tests less
  specific merely to avoid maintaining wording.

For branding assets, place runtime images under
`plugins/basicrum/assets/images/`, reference them through plugin asset helpers,
add rendering tests, and confirm the release ZIP includes them. Remove a root
source image after the packaged derivative is verified unless it has a separate
documented purpose.

## Regenerate and prove consistency

```bash
make translations
make translations
make conventions
git diff --check
```

The second translation run must be idempotent. Search again for every retired
spelling after generation, inspect the POT and PO diff, and verify MO files were
produced by the documented tool. Run relevant PHP or browser tests when rendered
markup, selectors, or behavior changed.
