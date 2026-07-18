# Basicrum WordPress Privacy Audit (2026-07-18)

Evidence-based privacy engineering and compliance-readiness audit of
`plugins/basicrum/` at version 1.0.2, including the uncommitted privacy-first
header and readme edits. Method: four parallel code tracers over data flow,
cookies/consent, WordPress integration, and disclosures; every claimed defect
adversarially verified by three independent refuters (majority rule); full test
suites executed; current official primary sources consulted. Full report with
per-finding evidence:
<https://claude.ai/code/artifact/ca2ba3ad-b502-4fcd-97ae-f8b20ff8c5d9>

This audit does not certify the plugin or any site as GDPR- or cookie-law
compliant, and no passing test below is legal approval. Jurisdiction-, purpose-,
contract-, or configuration-dependent items are recorded as legal decisions for
the site operator and qualified counsel.

Result: 42 PASS, 10 FAIL (verified), 6 NEEDS LEGAL DECISION, 2 NOT APPLICABLE.

## 1. Redact query strings from beacon URLs (DF-07)

The bundled Boomerang defaults `strip_query_string:false` and
`Assets::build_config_js()` never enables it, so `u=`, `pgu=`, `nu=` (full
clicked destination URL), and `restiming=` carry full URLs verbatim, including
WooCommerce order keys (`?key=wc_order_...`) and search terms (`?s=...`).

- [ ] Set `strip_query_string: true` in the injected Boomerang config by
  default, or expose it as a setting that defaults to on (keep `Helpers`,
  `Admin\Settings\Page`, and `Admin\Settings\Validate` in sync).
- [ ] Consider `ResourceTiming.trimUrls` for known sensitive paths.
- [ ] Add a browser test asserting a beacon from a URL with `?s=` and `?key=`
  contains `qs-redacted` and not the raw query string.
- [ ] Update `tests/unit/AssetsTest.php` config assertions.

## 2. Fix the consent opt-out race before Boomerang executes (COOKIE-07)

`OPT_OUT_BASICRUM_LOADER_WRAPPER()` only disables an already-executed Boomerang
and records no opted-out state. Withdrawal while `boomerang.js` is still
downloading is a near no-op: the script arrives, auto-initializes from the
still-present `window.basicRumBoomerangConfig`, recreates the RT cookie, and
beacons until reload.

- [ ] Make opt-out clear or neutralize the pending config and set a flag the
  bundle's init glue checks before `BOOMR.init()`.
- [ ] Keep readable and minified consent loaders equivalent and preserve the
  byte-for-byte standard-loader block contract.
- [ ] Add a Playwright test that delays the stubbed Boomerang response, opts
  out mid-flight, and asserts no execution, no RT cookie, and no beacon.
- [ ] Disclose the bounded residual case: a beacon already queued via
  `sendBeacon` still transmits after opt-out.

## 3. Purge page caches when monitoring stops (BR-WP-15)

`Compatibility.php` excludes Basicrum scripts from six cache and optimizer
plugins, so cached HTML keeps the loader and inline config. Nothing purges
caches on deactivation, uninstall, or `enabled`/`consent_enabled` transitions,
so cached pages keep beaconing after the operator turns monitoring off.

- [ ] Fire the known purge hooks (WP Rocket, W3TC, LiteSpeed, Autoptimize,
  SG Optimizer, WP-Optimize) on deactivation, guarded by
  `function_exists()`/`has_action()`.
- [ ] Fire the same purges when `sanitize()` transitions `enabled` or
  `consent_enabled`.
- [ ] Document the residual cache window in `readme.txt` and the settings page.
- [ ] Add unit tests following the existing Brain\Monkey patterns.

## 4. Name the RT cookie in public disclosures (DF-08, COOKIE-13, DISC-04)

The one persistent identifier - first-party cookie `RT`, rolling 7-day expiry,
30-minute session window, Secure and SameSite=Strict, carrying a random session
UUID that links page views and repeat visits and is echoed on every beacon as
`rt.si`/`rt.ss`/`rt.sl` - is disclosed only as "Boomerang may use first-party
cookies to maintain measurement state".

- [ ] Extend the Privacy Policy Guide suggested text in `Admin/Privacy.php`
  with the cookie name, purpose, lifetime, attributes, session identifier, and
  opt-out removal.
- [ ] Add a `readme.txt` FAQ entry with the same concrete cookie facts.
- [ ] Describe `BA` as a legacy cookie that this build never creates and only
  ever deletes.
- [ ] Update `tests/unit/PrivacyTest.php` string assertions.
- [ ] Keep the wording editable and qualified; no compliance claims.

## 5. Regenerate translation catalogs for the pending header edit (DISC-10)

The uncommitted `basicrum.php` Description change makes
`languages/basicrum.pot` and the bg_BG catalogs stale; the CI translation job
runs `git diff --exit-code` on `plugins/basicrum/languages` and will fail on
this working tree.

- [ ] Run `make translations` and commit the regenerated `basicrum.pot`,
  `basicrum-bg_BG.po`, and `basicrum-bg_BG.mo` together with the header and
  readme edits.
- [ ] Fold in the new strings from items 3 and 4 when those land.

## 6. Match public page-type lists to the detector (DISC-08)

`readme.txt` enumerates closed lists that omit most of the 17 `p_type` values
`PageTypeDetector.php` actually sends, including `checkout_payment`,
`checkout_success`, `account`, `product_category`, `tag`, `author`, and
`date_archive`.

- [ ] Update the feature list and the WooCommerce FAQ in `readme.txt` to the
  full detected taxonomy.

## 7. Replace the stock plugin README stub (DISC-09)

`plugins/basicrum/README.md` is the unmodified WordPress readme template with
placeholder text and fake changelog entries. It is public repository
documentation, though excluded from the release ZIP.

- [ ] Replace it with a real README mirroring the privacy-first `readme.txt`
  content, or delete it.

## 8. Clean up multisite uninstall residue (BR-WP-14)

`uninstall.php` deletes options only on the site where uninstall runs. On
multisite networks, `basicrum_settings` and `basicrum_version` rows persist on
other subsites. Low severity: the residue is operator configuration, not
visitor personal data.

- [ ] Iterate `get_sites()` with `switch_to_blog()`/`restore_current_blog()`
  on multisite, or document the limitation in `readme.txt`.

## 9. Needs legal decision (site operator and counsel; not code changes)

- [ ] DF-09: decide whether the device, network, interaction, and
  terminal-state telemetry (battery series, device memory, CPU cores, screen
  and connection census, storage sizes, interaction counts) is necessary and
  proportionate for the site's performance purpose; optionally add plugin
  toggles to disable Continuity/Memory/netinfo for narrower footprints.
- [ ] DF-10: decide whether immediate mode is lawful for the site's
  jurisdictions before enabling it (off by default, warned in the UI).
- [ ] DF-11, BR-WP-12: define collector-side retention, deletion, IP handling,
  controller/processor roles, and the data-subject rights path; the plugin
  cannot see or erase collector data.
- [ ] COOKIE-11: classify the RT cookie in the site's cookie policy (proposed:
  non-essential analytics) and decide whether an opt-out-region consent
  default may set it.
- [ ] DISC-13: confirm collector, purposes, legal basis, recipients,
  retention, and transfers before publishing the suggested policy text.

## 10. Verified passing boundaries

- [x] New installations are inert: `enabled='0'`, consent-controlled loading
  default, both `beacon_url` and `brum_site_id` required before any injection
  (DF-01, COOKIE-02, BR-WP-01, BR-WP-02).
- [x] Administrators excluded by default; `basicrum_should_track` can only
  veto, never enable (BR-WP-03, BR-WP-04).
- [x] Consent mode is silent pre-consent: zero requests, zero executions, zero
  cookies before opt-in, runtime-verified for readable and minified loaders
  (DF-02, COOKIE-03).
- [x] No third-party endpoint: Boomerang loads only from the plugin's own
  assets; the bundle contains no external URLs; beacons go only to the
  operator's collector; `BOOMR.url` prototype pollution guarded (DF-03,
  COOKIE-14).
- [x] Opt-in is idempotent; opt-out disables collection and removes RT/BA at
  the host and all parent domains, runtime-verified (COOKIE-04, COOKIE-06,
  DF-04).
- [x] Validation fails closed: invalid consent input selects consent mode;
  retired `consent_mode` cannot be re-saved; migrations never flip consent
  posture or silently enable monitoring (BR-WP-09, BR-WP-10).
- [x] HTTPS enforced: `http://` beacon URLs upgrade unless development mode is
  explicitly on and warned; the bundle also defaults `beacon_url_force_https`.
  Tracer claim BR-WP-06 (protocol-relative bypass) was overturned in
  verification; optional hardening: reject protocol-relative and relative
  input in `Validate.php` (DF-12, BR-WP-05, BR-WP-06).
- [x] Cookie hardening beyond upstream defaults: `secure_cookie: true` and
  `same_site_cookie: 'Strict'` injected (DF-06).
- [x] No persistent Web Storage; storage read for sizes only, values never
  transmitted (DF-14).
- [x] Loader parity contract enforced by tests; minified variants equivalent
  (COOKIE-05).
- [x] Consent adapters (Borlabs v3, CookieYes, WP Consent API) call one
  callback per decision, block denial, clear cookies on withdrawal, and fail
  closed when the tool is absent; CookieYes does not conflate Performance with
  Analytics consent (COOKIE-09).
- [x] Immediate mode is an intentional, plainly warned operator choice and is
  never described as automatically lawful (COOKIE-10, DISC-06).
- [x] Pending privacy-first header/readme claims are true in code, qualified,
  and free of blanket compliance claims; version consistency, ASCII hyphens,
  and short-description length pass (DISC-01, DISC-02, DISC-03, DISC-07,
  BR-WP-18).
- [x] No phone-home, no remote assets, no wp-admin tracking; single-site
  uninstall removes plugin data without overclaiming remote erasure
  (BR-WP-16, BR-WP-13, DISC-12).
- [x] Privacy Policy Guide text registered via
  `wp_add_privacy_policy_content()`, accurate on data categories, recipients,
  both modes, and the withdrawal limitation (BR-WP-17, DISC-05, DF-15).
- [x] Basicrum persists no consent state itself; the external tool is
  authoritative on every page (COOKIE-12).

## 11. Not applicable (evidenced)

- [x] WordPress personal-data exporter/eraser callbacks (BR-WP-11): the plugin
  stores no visitor personal data anywhere in WordPress; the only persistence
  is operator configuration in options. Rights handling shifts to the remote
  collector (see item 9).
- [x] BA bandwidth cookie (COOKIE-08): the bundled build compiles no Bandwidth
  plugin and never creates `BA`; it appears only in defensive opt-out cleanup.

## 12. Tests executed in this audit

- [x] `make unit`: 96 tests, 253 assertions, pass.
- [x] `make js-test`: 89 Playwright tests, pass; pre-consent silence,
  idempotent opt-in, denial, withdrawal, host and parent-domain cookie
  cleanup, loader byte contract, three adapters, both loader variants; the
  harness aborts every non-allowlisted request so no production collector can
  be contacted.
- [x] `make woocommerce-e2e`: 6 tests, pass; live intercepted beacons on the
  real WordPress/WooCommerce stack assert `p_type`, `p_gen=wp`, and the
  configured `brum_site_id`; collector is a reserved `.test` host answered
  with HTTP 204.
- [x] `make conventions`: ASCII hyphen and version consistency checks pass.

## 13. Residual test gaps (future work)

- [ ] Consent suites exercise a Boomerang stub: add live assertions for real
  RT-cookie attributes and post-`disable()` beacon silence.
- [ ] No negative beacon-content assertions exist; add tests for what a beacon
  must not contain (compounds item 1).
- [ ] No E2E consent-mode run: the WooCommerce stack provisions immediate
  mode only; add a live pre-consent-silence and withdrawal E2E.
- [ ] HTTP-origin behavior verified only as PHP validation, never in a
  browser; sibling-subdomain cookie visibility helper exists but is uncalled.
- [ ] Regional consent defaults tested only for the WP Consent API adapter;
  add region specs for Borlabs and CookieYes or record why not applicable.
- [ ] The "reviewed against Complianz" claim in
  `examples/integrations/README.md` rides on generic WP Consent API evidence;
  verify or soften it.
- [ ] Run the release packaging verification so the new privacy copy provably
  lands in the 1.0.2 ZIP before shipping.

## 14. Primary sources (accessed 2026-07-18)

- WordPress Plugin Handbook, Privacy:
  <https://developer.wordpress.org/plugins/privacy/>
- Plugin Handbook, Suggesting text for the site privacy policy:
  <https://developer.wordpress.org/plugins/privacy/suggesting-text-for-the-site-privacy-policy/>
- Plugin Directory Detailed Guidelines (7: no tracking without consent):
  <https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/>
- GDPR, Regulation (EU) 2016/679:
  <https://eur-lex.europa.eu/legal-content/EN/TXT/?uri=CELEX%3A32016R0679>
- ePrivacy Directive 2002/58/EC, consolidated, Article 5(3):
  <https://eur-lex.europa.eu/legal-content/EN/TXT/?uri=CELEX%3A02002L0058-20091219>
- EDPB Guidelines 05/2020 on consent:
  <https://www.edpb.europa.eu/our-work-tools/our-documents/guidelines/guidelines-052020-consent-under-regulation-2016679_en>
- WP29 Opinion 04/2012 on cookie consent exemption (WP 194):
  <https://ec.europa.eu/justice/article-29/documentation/opinion-recommendation/files/2012/wp194_en.pdf>
