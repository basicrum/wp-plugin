---
name: audit-basicrum-privacy
description: Audit Basicrum's privacy implementation and disclosures. Use for GDPR, ePrivacy, cookies, consent, telemetry data flows, collector roles, retention, transfers, privacy-policy text, plugin descriptions, or release privacy reviews.
---

# Audit Basicrum Privacy

## Purpose

Perform an evidence-based privacy engineering and compliance-readiness review of
the WordPress plugin, its bundled browser code, and its documented collector
relationship. Do not certify the plugin or a site as "GDPR compliant" and do not
replace advice from qualified counsel.

## Establish Scope

1. Identify the deployment model: hosted or self-hosted collector, applicable
   jurisdictions, target visitors, and enabled Basicrum features.
2. Identify the likely roles of the site operator, Basicrum, hosting providers,
   and other recipients. Record unknown controller, processor, joint-controller,
   subprocessor, contract, transfer, retention, and lawful-basis questions as
   `NEEDS LEGAL DECISION`; do not guess.
3. Review current official primary sources. At minimum, consult the WordPress
   Plugin Handbook privacy guidance, GDPR text, ePrivacy Article 5(3), and EDPB
   consent guidance. Record source links, jurisdiction, and access date.
4. Treat ePrivacy storage or terminal-access rules separately from GDPR lawful
   basis. Do not assume that a GDPR lawful basis automatically permits cookies.

## Trace the Real Data Flow

Inspect code and runtime behavior rather than relying on documentation alone.
Include `src/Assets.php`, `src/Admin/Privacy.php`, settings and validation code,
both loader variants, bundled Boomerang code and plugins, consent adapters,
uninstall behavior, PHP tests, browser tests, and an intercepted representative
beacon.

Create an inventory with one row per data element or storage operation:

- source and trigger;
- exact field, identifier, URL component, or cookie/storage key;
- purpose and feature dependency;
- recipient and onward recipient;
- transmission security and access boundary;
- retention and deletion path;
- behavior before consent, after opt-in, after denial, and after withdrawal;
- corresponding public disclosure and unresolved legal decision.

Pay particular attention to full page and resource URLs, query strings,
referrers, IP addresses observed by the collector, user agents, device and
network attributes, performance and interaction timings, Brum Site ID, page
type, cross-page identifiers, and accidental sensitive data in URLs. Minimize or
remove collection that is not necessary for the documented purpose.

## Audit Cookies and Consent

1. Enumerate every cookie and browser-storage value read, created, refreshed,
   or removed by Basicrum or bundled code. Record name, host/domain, path,
   lifetime, Secure, SameSite, purpose, and proposed essential/non-essential
   classification.
2. Verify that a new installation uses the privacy-protective loading default.
3. In consent-controlled mode, verify that no monitoring request, identifier,
   or non-essential storage occurs before the external tool's authoritative
   opt-in callback.
4. Verify opt-in is idempotent and cannot load duplicate instrumentation.
5. Verify denial and withdrawal stop future collection and remove Basicrum
   cookies across the supported host and parent-domain cases. Clearly document
   any collection that cannot be undone without a page reload.
6. Verify immediate mode is intentional, plainly warned, and never described as
   automatically lawful.
7. Test granted, denied, unknown, and region-dependent consent states for each
   supported adapter, using both readable and minified production loaders.

## Audit WordPress Integration and Disclosures

Compare actual behavior with all public and administrator-facing statements:

- the plugin header `Description`;
- the `readme.txt` short and long descriptions and FAQ;
- repository documentation and consent integration examples;
- settings labels, warnings, and help text;
- editable WordPress Privacy Policy Guide content registered through
  `wp_add_privacy_policy_content()`.

Require an explicit privacy-first statement in the plugin header and WordPress
readme, but reject blanket compliance claims. Disclose the actual data
categories, purposes, recipients, cookies, consent modes, withdrawal limits,
and site-operator responsibilities in language a webmaster can act on.

Determine from evidence whether WordPress personal-data exporter and eraser
callbacks apply. If the plugin stores no visitor personal data in WordPress,
mark them `NOT APPLICABLE` with evidence and separately assess rights handling
at the remote collector. Verify uninstall removes plugin settings without
silently claiming that remote telemetry was erased.

## Verify With Tests

Add or run proportionate tests for every changed privacy boundary:

- PHP tests for privacy-safe defaults, disabled/missing-setting runtime gates,
  settings validation, and Privacy Policy Guide content;
- browser tests for pre-consent silence, one-time opt-in, denial, withdrawal,
  cookie cleanup, HTTP/HTTPS behavior, subdomains, and both loader variants;
- runtime interception that records the actual request URL, headers, payload,
  cookies, and browser storage without contacting a production collector;
- packaging checks that confirm the reviewed privacy text and production
  assets are present in the release ZIP.

Run the narrow tests first, then the repository checks affected by the change.
Never treat a passing automated test as legal approval.

## Report the Audit

Use only these statuses:

- `PASS` - code, runtime evidence, tests, and disclosure agree.
- `FAIL` - a concrete privacy, consent, cookie, security, or disclosure defect.
- `NEEDS LEGAL DECISION` - the answer depends on jurisdiction, contracts,
  purpose, legal basis, retention, transfers, or operator configuration.
- `NOT APPLICABLE` - supported by evidence, not assumption.

For each item, report requirement or risk, status, code/runtime evidence,
current official source, remediation, and owner. Separate plugin engineering
work from collector/backend work and site-operator or counsel decisions. End
with residual risks and the exact tests run. Never conclude with an unqualified
claim of GDPR or cookie-law compliance.
