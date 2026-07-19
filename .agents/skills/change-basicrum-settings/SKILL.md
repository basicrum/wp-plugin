---
name: change-basicrum-settings
description: Change Basicrum WordPress settings across defaults, admin UI, validation, future migrations, and runtime gates. Use for Beacon URL, Brum Site ID, monitoring enablement, HTTP Strictness, Script Position, or any basicrum_settings option.
---

# Change Basicrum Settings

Treat a setting as one behavior spanning storage, administration, validation,
runtime use, upgrades, tests, documentation, and the gettext template.

## Map the setting

1. Inspect `plugins/basicrum/src/Helpers.php` for keys and defaults.
2. Inspect `Admin/Settings/Page.php` and `Validate.php` for rendering,
   sanitization, validation, and error messages.
3. After the first public release, inspect the migration service before changing
   an existing stored value. Do not add migration machinery for unreleased
   development schemas.
4. Find every runtime consumer and existing test with `rg`.
5. Define behavior for new installs, existing installs, enabled monitoring, and
   disabled monitoring before editing.

## Implement the complete behavior

- Keep the `basicrum_settings` schema synchronized across defaults, UI,
  validation, runtime code, tests, docs, the POT template, and any post-release
  migrations.
- Make server-side validation authoritative. Client-side disabled states and
  invalid-field styling are usability aids, not security controls.
- Require a valid Beacon URL and Brum Site ID when monitoring is enabled. Keep
  frontend injection gated when either required value is absent.
- Disable dependent controls when monitoring is disabled, and preserve a clear
  visual hierarchy between general, performance, privacy, and developer fields.
- Keep Script Position outside Developer Settings. Keep HTTP Strictness disabled
  by default and describe it as local HTTP testing support, not a production
  mode.
- Use WordPress Settings API errors and accessible field associations. Align any
  invalid icon with the field it describes and render it only while invalid.
- Sanitize by data type, escape for the final output context, and preserve
  capability and nonce protections already supplied by the settings flow.
- Once a public version exists, add a version-gated migration when stored data
  must change. Make migrations idempotent. Before the first public release,
  keep one clean schema without compatibility aliases.

If user-facing text changes, also apply `$update-basicrum-copy`.

## Verify

Run focused tests while iterating, then run:

```bash
make lint-php
make lint
make analyse
make unit
make conventions
```

Run `make translations` for changed strings and require a second POT generation
to produce no diff. Manually verify enabled, disabled, invalid, and valid form
states when layout or browser behavior changes.
