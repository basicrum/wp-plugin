---
name: integrate-basicrum-consent
description: Integrate and test external consent tools with Basicrum's opt-in and opt-out wrappers. Use for Borlabs Cookie, WP Consent API, Complianz, CookieYes, consent events, regional defaults, withdrawal, cookie cleanup, or adapter tests.
---

# Integrate Basicrum Consent

Keep Basicrum as a small execution gate around an external consent tool's
authoritative decision.

## Establish the provider contract

1. Read the provider's current official documentation and shipped public source.
2. Use community reports to identify initialization, caching, aggregation, and
   version edge cases, not as the sole contract source.
3. Record the supported provider version and review date in documentation when
   behavior can change.
4. Confirm initial state, stored state on later page loads, event names, payload
   shapes, category IDs, callback timing, regional defaults, withdrawal, and
   whether the provider signals on every page.
5. Separate an explicit visitor opt-in from a provider reporting that monitoring
   is allowed under an opt-out regional policy.

## Build the adapter

- Keep exactly two Basicrum public callbacks:
  `OPT_IN_BASICRUM_LOADER_WRAPPER()` and
  `OPT_OUT_BASICRUM_LOADER_WRAPPER()`.
- Make the provider adapter call exactly one callback on every page after the
  consent loader is available. Check that the selected callback exists before
  calling it.
- Do not add a second Basicrum consent state, `BRUM_CONSENT` API, cookie, or
  state lookup inside the Boomerang loader.
- Keep opt-in loading idempotent. Keep opt-out idempotent, disable collection,
  and remove the documented Boomerang cookies for the current host and parent
  domains.
- Put webmaster adapters in `examples/integrations/`, outside the installable
  plugin. Do not modify a production loader merely to accommodate a test.
- Document script ordering and cache/minification exclusions. Do not claim that
  an adapter replaces legal review or consent-tool configuration.

## Test the real boundary

- Execute the exact example adapter against a thin test double that models only
  the provider's documented public API, defaults, events, and payloads.
- Cover adapter-before-provider and provider-before-adapter initialization,
  saved grant, saved denial, new grant, withdrawal, duplicate events, and
  irrelevant categories.
- Cover opt-in and opt-out regional defaults for region-aware APIs.
- Run the privacy-critical loader suite against both minified and unminified
  loader variants while intercepting Boomerang and blocking unexpected network
  requests.

```bash
make js-test
make unit
make conventions
git diff --check
```

Update `examples/integrations/README.md`, the main README, plugin readme, privacy
guidance, and translations when the administrator-visible contract changes.
