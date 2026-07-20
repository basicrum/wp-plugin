# WordPress.org Submission Checklist (2026-07-19)

Factuality and ambiguity audit of the submission surfaces (readme.txt,
basicrum.php header, wordpress-org-assets, release ZIP) at version 0.0.8,
ahead of the plugin-directory submission. Method: four auditors (line-by-line
readme factuality vs code, current wp.org handbook requirements, two-persona
ambiguity sweep, hands-on official readme validator + reviewer-style ZIP
inspection); every non-clean verdict adversarially re-checked by two
refuters. 84 claims audited: 52 clean, 24 upheld findings, 8 overturned.

## 1. Blockers - must be resolved before submission

- [x] RESOLVED 2026-07-20: user registered the wordpress.org account `basicrum`; readme.txt Contributors updated to `basicrum`. Original finding: Contributors username did not exist. The OFFICIAL wp.org readme
  validator returned verbatim: "The following contributors listed were
  ignored, as the WordPress.org user could not be found. tstoychev."
  Register the wordpress.org account with exactly that username (or change
  readme.txt line 2 to the registered account that will submit), and
  confirm profiles.wordpress.org/tstoychev resolves. (D1a, R02, B6)
- [x] RESOLVED 2026-07-20: Boomerang BSD LICENSE.txt now ships at assets/js/boomr/LICENSE.txt; readme.txt Third-party section and THIRD-PARTY-NOTICES.txt name the source commit and repositories. UPGRADED 2026-07-20: build reproduced BYTE-IDENTICAL (SHA-256 90e8a1c8...) from basicrum/boomerang master commit ead2783a with Node 12 + npm ci + grunt clean build --build-flavor=cutting-edge --build-number=815; the in-file banner stamps parent commit 564759ed because the final continuity.js change was uncommitted at original build time - docs now state both hashes; tools/verify-boomerang-provenance.sh guards bundle/docs sync via make conventions. Original finding: human-readable source for the bundled Boomerang (guideline 4:
  reviewers require public, maintained access to source and build tools
  for minified files). assets/js/boomr/ ships only the .min.js; its header
  says "See the accompanying LICENSE.txt" and none accompanies it. Add to
  readme.txt (Development or Third-party section) and THIRD-PARTY-NOTICES.txt:
  the upstream repository, the exact source commit (the bundle header
  carries 564759ed70de7801bb64de5e2025fb6ac049ff5f), and the build
  procedure; ship Boomerang's BSD license text alongside the bundle. (B2)
- [x] RESOLVED 2026-07-20: phpstan.neon.dist added to .distignore and the verify-release dev-file regex; ZIP rebuilt and verified clean. Original finding: dev file leaks into the ZIP: release/basicrum.zip contains
  basicrum/phpstan.neon.dist. Add it to plugins/basicrum/.distignore and
  to the verify-release.sh dev-file regex, rebuild. Plugin Check would
  flag it. (D2a, B7)

## 2. Reviewer-flag items - fix to avoid review friction

- [x] RESOLVED 2026-07-20: External services section added to readme.txt. Original: add an "External services" readme section in the current
  reviewer-requested format: name the service (operator-configured
  collector; basicrum.com hosted option), what data is sent and when
  (performance beacons: URLs, timings, page type, site id; IP and user
  agent visible to the collector), and links to the service terms/privacy
  pages. The FAQ covers parts of this but not in the expected form. (B4)
- [x] RESOLVED 2026-07-20: "Does Basicrum set cookies?" FAQ added (RT named with attributes and lifetime; BA described as legacy removal); RT also named in the Privacy Policy Guide text (Privacy.php) with test assertions. Original: add a cookies FAQ: the readme never names the first-party RT and BA
  cookies the listing's own privacy story depends on ("Does Basicrum set
  cookies?" - names, purpose, consent-mode behavior, opt-out removal).
  (C10)
- [x] RESOLVED 2026-07-20: changelog collapsed to a single first-release entry. Original: collapse the 0.0.8 changelog into a single first-release feature
  entry; the current six bullets describe diffs against never-published
  0.0.x builds and can be misread as shipped-version history. Decide
  whether 0.0.7/0.0.6 entries stay (internal history) or fold in. (C13,
  C14)
- [ ] Run Plugin Check against the BUILT release/basicrum.zip (not the
  repo tree) after the fixes and keep the output for the submission. (B7)
- [ ] Tested up to 7.0: internally defensible (blocking CI rows for wp
  7.0 exist; format correct - verdict overturned by refuters), but
  re-confirm on submission day that WordPress 7.0 is the current released
  stable. (R05)

## 3. Ambiguity fixes - upheld copy issues

- [x] RESOLVED 2026-07-20: FAQ reworded (hosted account is one way; self-hosted needs no account). Original: account FAQ answered "Yes." then contradicts
  itself: self-hosted collectors need no account. Reword: collector
  endpoint + Brum Site ID required; hosted account is one way to get
  them. (R24, C04)
- [x] Vocabulary bridge: the compliance FAQ says "immediate and
  consent-controlled loading" while the settings radios say "Monitor
  without consent" / "Require consent before monitoring". Bridge both
  vocabularies once, then use the radio labels. Resolved by removing the
  competing terminology. (C02)
- [x] Define the server-side nouns once: collector (receives beacons) vs
  backoffice (dashboard where the Brum Site ID lives) vs account (hosted
  option); "backoffice" is currently undefined jargon. Resolved by using
  collector, Beacon URL, and hosted service consistently. (C15)
- [x] Disambiguate "Basicrum" = plugin vs company vs service in the
  query-string FAQ: data goes only to the operator-configured Beacon URL;
  the plugin makes no requests to basicrum.com. (C16)
- [x] HTTP Strictness FAQ still inverts the semantics (enabling
  "Strictness" relaxes enforcement). Rewrite the FAQ to lead with the
  default (auto-upgrade to HTTPS) and what the toggle actually allows;
  the label rename remains open from the operator-experience audit. (C08)
- [ ] "eligible pages" in the contributed privacy-policy text
  (Privacy.php immediate-mode sentence) is undefined for site owners;
  spell out: frontend pages, admins excluded unless Track Admin Users.
  (C12)
- [x] Define "connected" CookieYes at first use. Resolved by removing the
  ambiguous implementation detail from the customer-facing overview. (C07)
- [x] Replace "fails closed" jargon with plain language. (C19)
- [x] Align the installation order with the enabled-field dependency and
  remove the redundant "How it works" sequence. (C22)
- [x] Remove the undefined Script Position forward reference from the
  consent overview. (C23)

## 4. Optional but recommended

- [x] Screenshots: four current WordPress 7.0.2 settings captures use the exact
  Visitor Consent and Consent Tool Connection labels, privacy-safe example
  values, and matching numbered captions in `readme.txt`. (C24)
- [ ] Spot-check the two basicrum.com URLs (home, /contact/) resolve;
  reviewers click them. (R45)
- [ ] CookieYes "modern ... runtime" - one refuter pair split on this;
  consider "CookieYes 3.x" with a one-line legacy note for precision.
  (C06)

## 5. Verified clean (highlights)

- [x] Version consistency: header, BASICRUM_VERSION, Stable tag, top
  changelog all 0.0.8; no git tags, consistent with first release. (R07)
- [x] Short description 95 chars (under 150), byte-identical to the
  header Description; privacy-first claim backed by defaults. (R09, C01
  overturned)
- [x] MIT license declared consistently (readme, header, LICENSE.md,
  composer.json); GPL-compatible; Boomerang BSD is GPL-compatible;
  THIRD-PARTY-NOTICES.txt scopes correctly. (R08, B1)
- [x] All five tags valid and implemented; Requires at least 6.0 and
  Requires PHP 7.4 match headers, composer, and CI matrix. (R03, R04, R06)
- [x] Feature claims verified against code: page-type values verbatim in
  PageTypeDetector.php with correctly hedged non-exhaustive lists;
  detection markers and version floors match ConsentIntegration.php;
  cache-plugin list matches Compatibility.php; guideline 7 satisfied
  (off by default, consent-controlled default). (R12-R15, B4-part)
- [x] Plugin header complete: Requires at least, Requires PHP, Text
  Domain, Domain Path present; validator returned only the Contributors
  warning - readme parses cleanly otherwise. (D1a)
- [x] ZIP contents otherwise reviewer-clean: no repo README, tests,
  docs/, node_modules, or scratch; readable+minified loader pairs and all
  five adapters present; production-only vendor/. (D2)

## User-only actions before submission day

1. Register/confirm the wordpress.org username matching Contributors.
2. Re-confirm WordPress 7.0 is the current released stable.
3. Verify basicrum.com pages linked from the listing are live.
