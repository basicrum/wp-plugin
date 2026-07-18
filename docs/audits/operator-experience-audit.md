# Basicrum WordPress Operator Experience Audit (2026-07-18)

Webmaster operating-comfort audit of `plugins/basicrum/` at version 1.0.2:
how comfortable, clear, and safe the plugin is for a WordPress-competent
non-developer to install, configure, verify, operate, and maintain. Method:
four tracers (settings UX and copy, live wp-admin walkthrough on the real
Docker WordPress/WooCommerce stack, consent-integration burden, documentation
and lifecycle coherence) benchmarked against the bundled Plausible reference
plugin; every blocker/major claim adversarially verified by two independent
refuters. This complements the privacy audit in `privacy-audit.md`. Full
report:
<https://claude.ai/code/artifact/3455e55b-b1dc-4d67-b868-62552b966519>

Result: 23 good practices confirmed, 24 distinct issues after deduplication
(1 blocker, 11 major, the rest minor/polish). Overall: the settings screen
mechanics are excellent - better than the Plausible benchmark in places - but
the operator journey falls off a cliff right after Save: on the default path a
correctly configured site can collect nothing, silently, forever, and no
surface tells the webmaster how to notice or fix that.

## 1. Fix the silent consent dead-end (blocker cluster)

New installs default to consent-controlled loading. Without a wired consent
tool the consent loader waits forever: zero data, no warning, indefinitely.
The enabled-but-inactive notice only checks Beacon URL and Brum Site ID. The
three tested copy-paste adapters in `examples/integrations/` are outside the
release ZIP and are never mentioned by readme.txt or the settings page, so the
likely escape hatch for a stuck webmaster is switching to Load immediately,
which the plugin itself warns may be unlawful. (CI-01, BR-DOC-07, BR-DOC-12,
walkthrough-07)

- [ ] Add a plain-language consequence sentence to the consent panel: until
  the consent tool calls the opt-in callback, Basicrum will not load and no
  data will be collected.
- [ ] Link the ready-made adapters (per tool) from the consent info box and a
  new readme FAQ naming Borlabs Cookie, WP Consent API (Complianz), and
  CookieYes.
- [ ] Consider a persistent settings hint when consent mode is selected and
  no integration has been observed.

## 2. Make consent integration actionable for non-developers

- [ ] Replace the inert `reportBasicrumConsent()` skeleton with per-tool
  instructions or links; say concretely where the code goes (consent tool's
  custom-JavaScript field or a footer snippet plugin). (CI-02)
- [ ] Replace the abstract load-order rule ("after the configured Script
  Position; calls before registration are not replayed") with one safe
  concrete recipe: keep Script Position on Header and add the adapter in the
  consent tool's custom-JavaScript area. (CI-04)
- [ ] Add a header comment to `cookieyes.js` (and each adapter) stating the
  required category and the symptom of getting it wrong: filing Basicrum
  under Performance means monitoring never starts. (CI-05)
- [ ] Explain the consequence of violating the one-callback-per-page
  contract (consent granted on a later page never starts monitoring if the
  adapter only fires on change events). (CI-06)
- [ ] Mention the WP Consent API route as the lowest-code path and how to
  tell whether an installed consent plugin supports it. (CI-07)

## 3. Add a verify-it-works story

There is no documented way on any surface to confirm monitoring works, and
the live walkthrough confirmed the trap: with default settings the logged-in
admin's own pages contain no Basicrum markup at all, so the natural first
check (view-source) looks exactly like a broken install. (walkthrough-06,
BR-DOC-08, BR-DOC-09, walkthrough-15)

- [ ] Extend the Track Admin Users description: while off, the script is not
  injected for your own logged-in visits - test in a private window.
- [ ] Add a readme FAQ "How do I check that monitoring is working?"
  (devtools network tab, what a beacon looks like, data appears in the
  backoffice, with a time expectation).
- [ ] Add a troubleshooting FAQ "No data is arriving" with a checklist:
  admin tracking off, consent mode with no adapter, wrong CookieYes
  category, caches not purged.
- [ ] Add a post-save hint distinguishing "monitoring active for visitors"
  from a bare "Settings saved.".

## 4. Give silent failures a diagnostic surface

A down collector fails only in visitors' browsers; a wrong-but-well-formed
Brum Site ID passes the regex and loses data forever; a mis-wired adapter is
skipped silently; no loader or adapter logs anything to the console.
(BR-DOC-10, CI-03)

- [ ] Add a debug affordance (e.g. when Use Unminified Loaders is on, log
  callback registration and each opt-in/opt-out call), keeping the
  byte-for-byte standard-loader block contract intact.
- [ ] Consider a test-beacon button or collector-reachability check on save,
  or at minimum document the failure symptoms and where to look.

## 5. Fix the inverted HTTP Strictness naming

Checking the box named "HTTP Strictness" RELAXES strictness (allows HTTP
beacon URLs; internal key `development_mode`), and the readme FAQ compounds
it ("Enable HTTP Strictness ... to preserve HTTP beacon URLs"). Verifiers
rated the severity minor (the checkbox's own sentence tells the truth and the
field is buried in Developer Settings), but it is a one-word fix with
outsized confusion potential. (issue-http-strictness-inverted, BR-DOC-15)

- [ ] Rename the row label to match the action, e.g. "Allow HTTP Beacon URL
  (development only)"; keep the stored option key unchanged.
- [ ] Rewrite the readme FAQ sentence accordingly.

## 6. Link the Basicrum ecosystem from the admin screen

The settings page contains zero external links. "Copy the Brum Site ID from
the Basicrum backoffice" names a place the webmaster cannot find; that a
Basicrum account/collector is needed at all is discoverable only in the
readme. The Plausible benchmark links docs from multiple points on its
settings screen. (issue-backoffice-unlinked, walkthrough-05, BR-DOC-16)

- [ ] Add a what-you-need-first intro block linking hosted signup,
  self-hosting docs, and the backoffice.
- [ ] Link the backoffice from the Brum Site ID description and its
  validation error; show the expected UUID format with an example.
- [ ] Keep typed invalid values in the field for correction instead of
  wiping them. (walkthrough-09)

## 7. Repair the wp.org listing surfaces

- [ ] readme.txt promises 4 screenshots; `wordpress-org-assets/` contains
  none. Capture screenshot-1..4.png or remove the section. (BR-DOC-11)
- [ ] Installation steps 4-5 are in the reverse order of the UI dependency
  (fields unlock only after Enable is checked). (BR-DOC-14)
- [ ] Add a Support section (wp.org forum, basicrum.com contact).
  (BR-DOC-19)
- [ ] Align page-type lists across surfaces with the detector (also privacy
  audit item 6). (BR-DOC-22)
- [ ] Replace the stock-template `plugins/basicrum/README.md` stub (also
  privacy audit item 7). (BR-DOC-21)
- [ ] Keep the dev file `phpstan.neon.dist` out of the release ZIP.
  (BR-DOC-23)

## 8. Document the operating lifecycle

- [ ] State that disabling or deactivating Basicrum does not immediately
  stop the script on cached pages; tell operators to purge page/CDN caches
  (documentation side of privacy audit item 3). (BR-DOC-13)
- [ ] State what deactivation keeps (settings) and uninstall removes.
  (BR-DOC-17)
- [ ] Surface a notice after silent upgrade migrations (e.g. legacy installs
  kept on immediate loading). (BR-DOC-18)
- [ ] Add multisite guidance; network activation leaves every subsite
  unconfigured. (BR-DOC-20)

## 9. Small engineering fixes

- [ ] Guard the global `settings_errors()` call so core Settings screens do
  not show duplicated notices. (issue-duplicate-notices)
- [ ] Give the Delay number input and Monitoring Start radios proper
  accessible names (label_for / fieldset legend / aria-describedby), matching
  the text fields' rigor. (issue-number-radio-a11y)
- [ ] Add a Settings action link to the Plugins list row.
  (issue-no-plugins-list-link, walkthrough-12)
- [ ] Declare WooCommerce HPOS (custom order tables) compatibility via
  `FeaturesUtil::declare_compatibility()`; the plugin advertises WooCommerce
  support but makes no declaration. (critic)
- [ ] Resolve the Bulgarian catalog: 8 of 66 strings translated yet a
  compiled .mo ships, giving Bulgarian admins a mixed-language UI. Complete
  it or drop the pair until substantially complete. (critic)
- [ ] Explain in plain language why fields are grayed out before Enable is
  checked. (issue-disabled-state-unexplained)
- [ ] Soften residual developer jargon: `manage_options capability`,
  `wp_head`/`wp_footer`, unexplained "Boomerang"/"beacon" on first use.
  (issue-boomerang-beacon-jargon, issue-script-position-jargon,
  issue-residual-dev-jargon, walkthrough-11)

## 10. Verified good practices (keep these)

- [x] Dependency gating is excellent: all fields disabled until Enable is
  checked, live JS sync, hidden mirrors preserve values without JS -
  stronger than the Plausible reference. (good-dependency-gating,
  walkthrough-01)
- [x] Validation is polite and accessible: errors only on submit or
  server-flagged state; icon, message, and row highlight clear on
  correction; aria-required/invalid/describedby wired; not color-only.
  (good-live-validation, good-field-accessibility, walkthrough-14)
- [x] Enabled-but-incomplete state is loudly surfaced on every admin screen,
  names the exact missing field, and links to the fix. (good-inactive-warning,
  walkthrough-03, BR-DOC-02)
- [x] Saving on the top-level menu page still shows "Settings saved." and
  validation notices after redirect. (good-save-feedback)
- [x] Every default is the safe starting point; malformed consent input
  fails closed with an explanatory notice. (good-safe-defaults)
- [x] The silent HTTPS upgrade is communicated with an info notice, verified
  live. (walkthrough-04)
- [x] Privacy copy is honest and readable by a non-lawyer; immediate mode
  carries a proportionate warning. (good-privacy-copy)
- [x] The consent contract is honestly documented at the point of choice,
  including irreversibility and no-replay caveats. (walkthrough-08, CI-10)
- [x] The adapters themselves are trustworthy copy-paste material: all three
  fail closed on any doubt. (CI-08)
- [x] `examples/integrations/README.md` is decision-ready, tool-specific
  operator documentation. (CI-09)
- [x] The admin page is fast and clean: ~116 ms DOMContentLoaded locally,
  zero console errors. (walkthrough-13)
- [x] readme.txt is honest (no compliance claims; "Go to Basicrum in the
  admin sidebar" matches reality). (BR-DOC-01)
- [x] A corrupt install aborts with an actionable notice instead of a white
  screen. (BR-DOC-03)
- [x] Upgrade Notice sections truthfully describe migration behavior.
  (BR-DOC-04)
- [x] The Privacy Policy Guide contribution reflects the live configuration.
  (BR-DOC-05)

## 11. Residual gaps (not audited)

- [ ] Non-English operator experience beyond the bg_BG catalog spot-check.
- [ ] WooCommerce-specific operator concerns beyond the walkthrough stack
  (HPOS noted above).
- [ ] Performance-anxiety copy ("will this slow my site?") - no FAQ
  addresses frontend impact; the only weight disclosure is the Boomerang
  Version row.
- [ ] GOOD findings were not adversarially verified (only issues were);
  runtime GOOD claims come from a single live walkthrough.
- [ ] Full-page-cache interplay with settings changes (stale config in
  cached HTML after changing Brum Site ID or mode) beyond the documented
  purge caveat.
