# Basicrum Audit Remediation Checklist (2026-07-18)

This is the canonical active checklist, deduplicated from `privacy-audit.md`
and `operator-experience-audit.md` and updated with the verification-lab
results. See `evidence.md` for per-item evidence and reproduction commands.
Audited ref: `0ec392e`. Priority: P0 = broken
now (CI/release), P1 = verified collection/consent defect, P2 = verified
operator-journey defect, P3 = minor/polish.

## A. Plugin engineering

- [x] P0 Regenerate translation catalogs and commit (`make translations`).
  Completed in `784b2bf`. Two consecutive generations produced identical
  catalogs, and the translation CI gate passed after push. (privacy 5 /
  DISC-10, lab-elevated)
- [ ] P1 Make `strip_query_string` a first-class Visitor Privacy setting,
  disabled by default by product decision and emitted as a Boomerang boolean.
  When enabled, this flag redacts `u`, `nu`, `r`, and `restiming`
  (`?qs-redacted`) - no separate ResourceTiming measure is needed for query
  strings. URL paths remain and may need a future `ResourceTiming.trimUrls`
  policy. Add a real-bundle negative beacon test with planted document and
  resource tokens, asserting the token appears in no beacon field.
  Implementation and verification are complete in the working tree; keep this
  item open until the change is committed. (privacy 1 / DF-07)
- [ ] P1 Apply the opt-out race fix. Port the deterministic test from the
  temporary worktree before cleaning it up. The minimum fix should null
  `basicRumBoomerangConfig`; do not add the proposed `optedOut` marker or any
  other consent state. Keep the two-callback contract and
  reload-before-regrant semantics, preserve the byte-for-byte loader block,
  and test both readable and minified loaders. Add one delayed-script case
  using the real bundled Boomerang asset in addition to the focused init-glue
  stub. (privacy 2 / COOKIE-07)
- [ ] P1 Define and enforce a privacy-first telemetry profile. Determine which
  optional device, memory, DOM, coordinate-log, and element-selector fields
  are necessary for Basicrum's core RUM purpose. Verify the exact Boomerang
  configuration needed to disable unnecessary fields while retaining LCP,
  INP duration, navigation timing, and required ResourceTiming data. Add a
  captured-beacon allowlist or negative-field test. Do not treat disclosure
  alone as remediation for collection that is not necessary. (privacy 9 /
  DF-09, verification-lab correction)
- [ ] P1 Purge page caches when monitoring stops. Fire on deactivation and
  on `enabled`/`consent_enabled` transitions in sanitize(), guarded:
  `rocket_clean_domain()` (WP Rocket >= 1.0), `w3tc_flush_all()` (W3TC >=
  0.9.5), `do_action('litespeed_purge_all')` (LiteSpeed >= 3.0, no guard
  needed), `sg_cachepress_purge_cache()` (Speed Optimizer >= 5.0),
  `autoptimizeCache::clearall()` (asset cache only - does not purge page
  HTML), `wpo_cache_flush()` (WP-Optimize >= 3.0, only after
  plugins_loaded). CDN/edge, host Varnish, static exports, and visitors'
  browser cache of HTML cannot be purged by the plugin - document as
  operator responsibility (see C). (privacy 3 / BR-WP-15)
- [x] P2 Ship the consent adapters inside the plugin (distribution model C):
  move `examples/integrations/*.js` to
  `plugins/basicrum/assets/js/integrations/`, render each as copyable text
  in `render_consent_info()` (webmasters paste TEXT into their consent
  tool's UI - the file on disk is not the deliverable), update the three
  spec-file paths so the tested, shipped, and displayed artifact are one
  file, and add the three files to `tools/verify-release.sh` required
  entries. Add the required-category warning header to `cookieyes.js`
  (wrong category = silent no-data; tested distinction) and equivalents to
  the other adapters. (UX 1+2 / CI-01, CI-05, BR-DOC-12)
  Completed with four accessible settings tabs, including separate generic
  opt-in and opt-out snippets, copy fallback guidance, exact packaged-adapter
  browser coverage, and release ZIP required-file checks.
- [ ] P2 Replace the proposed "persistent consent warning" with contextual
  detection hints (design analysis): in `render_consent_info()` only -
  never a site-wide notice - detect Borlabs / Complianz / CookieYes / WP
  Consent API via class/function/constant markers and show "X detected -
  use the Y adapter"; flag the one verifiable misconfiguration (Complianz
  or CookieYes active while `wp_has_consent` is unavailable); when consent
  mode is on and no known plugin is found, show one neutral sentence that
  monitoring will not start until the opt-in callback runs. A persistent
  warning would have a 100 percent false-positive rate on working
  integrations (no server-side ground truth exists without a rejected
  visitor-side ping) and would pressure admins toward Load immediately.
  (UX 1 / BR-DOC-07 + design Q-A)
- [ ] P2 Replace the abstract load-order rule with one safe recipe (Script
  Position on Header + adapter in the consent tool's custom-JS area) and
  make the admin example actionable (placement, event wiring or per-tool
  link). (UX 2 / CI-02, CI-04)
- [ ] P2 Add debug logging behind the existing Use Unminified Loaders
  toggle (callback registration + each opt-in/opt-out call), keeping the
  standard-loader byte contract; consider a test-beacon or reachability
  check on save. (UX 4 / CI-03, BR-DOC-10)
- [ ] P3 Settings copy and small fixes: rename "HTTP Strictness" to match
  its permissive action (verifier-rated minor, one-word fix); link the
  Basicrum backoffice/docs from the field description, validation error,
  and a what-you-need-first intro (currently zero external links on the
  page); keep rejected Site ID values in the field; fix the "default URL
  restored" error copy; plain-language Script Position tradeoff; drop
  `manage_options` jargon; `plugin_action_links` Settings link; explain the
  disabled-until-enabled state; guard the global `settings_errors()` call;
  accessible names for the Delay input and mode radios. (UX 5+6+9)
- [ ] P3 Multisite uninstall: iterate `get_sites()` or document the
  limitation. (privacy 8 / BR-WP-14)
- [ ] P3 Declare WooCommerce HPOS compatibility
  (`FeaturesUtil::declare_compatibility`, guarded). (UX 9)
- [ ] P3 Bulgarian catalog: complete it or drop the po/mo pair (8 of 66
  strings translated ships today). (UX 9)
- [ ] P3 Optional hardening: reject protocol-relative/relative beacon URLs
  in `Validate.php` (BR-WP-06 was overturned to PASS - the bundle's
  `beacon_url_force_https` already prevents plaintext beacons). (privacy 10)

## B. Documentation

- [ ] P1 Name the RT cookie precisely in `Privacy.php` suggested text and a
  readme FAQ, using the lab-verified facts: first-party `RT`, path=/,
  SameSite=Strict, Secure on https only (on http sites it is set WITHOUT
  Secure - do not overclaim), rolling 7-day expiry renewed per page view,
  30-minute session window, contains a random session UUID linking visits,
  removed on opt-out; BA is legacy and only ever deleted. (privacy 4 /
  DF-08, COOKIE-13, DISC-04)
- [ ] P1 Correct interaction-data disclosure wording: keystroke COUNTS only
  (values never read), but the Continuity log transmits per-event
  timestamps with x/y coordinates and EventTiming embeds element CSS
  selectors; battery is NOT collected (bundled, disabled, never invoked -
  lab-verified). Use the evidence-table capability matrix as the canonical
  what-is-sent list. (corrects the original DF-09/inventory wording)
- [ ] P2 Verify-it-works + troubleshooting FAQs: how to check (private
  window because of the admin-exclusion default, DevTools network tab,
  backoffice with a time expectation) and "No data is arriving" checklist
  (admin tracking off, consent mode with no adapter, wrong CookieYes
  category, stale caches). (UX 3 / BR-DOC-08, BR-DOC-09, walkthrough-06)
- [ ] P2 Lifecycle docs: purge caches after disabling/deactivating (cached
  pages keep the loader; browser-cached HTML cannot be purged at all);
  what deactivation keeps vs uninstall removes; migration notes; multisite
  guidance. (UX 8 / BR-DOC-13, BR-DOC-17, BR-DOC-18, BR-DOC-20)
- [ ] P2 wp.org listing: add the four screenshots or drop the section;
  fix Installation step order; add a Support section; align page-type
  lists with the detector; replace the stock `plugins/basicrum/README.md`
  stub. (privacy 6+7, UX 7 / DISC-08, DISC-09, BR-DOC-11, BR-DOC-14,
  BR-DOC-19)
- [ ] P3 Soften or verify the "reviewed against Complianz" claim in the
  integrations README. (UX residual)

## C. Collector / backend (outside this repo)

- [ ] Define and document collector-side retention, deletion path, IP
  handling, and access controls; the plugin can neither see nor erase
  collector data and correctly does not claim to. (privacy 9 / DF-11)
- [ ] Provide the data-subject rights path (access/erasure) for beacon data
  and request logs; publish it so site operators can reference it.
  (privacy 9 / BR-WP-12)
- [ ] Document the ingested beacon schema using the lab capability matrix
  (what arrives by default vs what `strip_query_string`/`trimUrls` remove),
  so operator disclosures and collector docs stay in sync.

## D. Operator / legal decisions (site-specific, not code)

- [ ] Necessity/proportionality of the transmitted device and interaction
  telemetry - judged against what is ACTUALLY sent (see capability matrix):
  device memory, CPU cores, screen, heap and storage SIZES, connection
  type/downlink, DOM census incl. cookie-string length, interaction counts
  + coordinate log + element selectors. Battery and client hints are NOT
  sent and need no assessment. (privacy 9 / DF-09)
- [ ] Lawfulness of immediate mode for the site's jurisdictions (off by
  default, warned). (DF-10)
- [ ] ePrivacy classification of the RT cookie and validity of
  opt-out-region defaults for setting it. (COOKIE-11)
- [ ] Retention, legal basis, controller/processor roles, transfers for the
  operator-configured collector, reflected in the published policy.
  (DISC-13)
