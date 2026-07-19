---
name: update-basicrum-copy
description: Update Basicrum branding, user-facing copy, documentation, and the WordPress gettext template. Use for naming changes, BasicRUM or Basic Rum cleanup, Brum Site ID help text, logos, POT files, or ASCII-hyphen checks.
---

# Update Basicrum Copy

Change source text and its generated gettext template as one unit.

## Find the full text surface

1. Search case-insensitively across PHP, JavaScript, CSS, Markdown, WordPress
   readme, tests, fixtures, examples, workflows, and POT files.
2. Distinguish source strings from the generated POT template.
3. Check identifiers separately from display text; do not casually rename stored
   option keys, hooks, handles, or public callbacks.

## Apply project vocabulary

- Use `Basicrum`, never `BasicRUM` or `Basic Rum`.
- Use `Brum Site ID` in administrator-facing text and point users to the
  Basicrum backoffice instead of describing UUID internals.
- Use ASCII hyphens only. Do not introduce en dashes or em dashes.
- Keep statements about consent, privacy, and compliance precise. Basicrum
  follows the external tool's decision; it does not make a site compliant.
- Keep WordPress gettext calls in PHP source. Do not add locale-specific PO or
  MO files unless bundled translations are deliberately restored.
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
spelling after generation and inspect the POT diff. Run relevant PHP or browser
tests when rendered markup, selectors, or behavior changed.
