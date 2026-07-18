# Basicrum WordPress Audit Evidence (2026-07-18)

Audited ref: `0ec392e` (HEAD of main). Verified: `git diff caed894..0ec392e --
plugins/basicrum` contains exactly the basicrum.php Description line and the
readme.txt short-description + privacy paragraph, so the plugin content at
`0ec392e` is byte-identical to the audited working tree. A staleness pass
re-verified every finding anchor against HEAD on 2026-07-18: all findings
below are still valid; none were fixed by the commit; DISC-10 was ELEVATED
(see row). Runtime evidence comes from a verification lab run against the
repo's ephemeral WooCommerce stack (port 9081, immediate mode, intercepted
collector at `https://collector.basicrum.test/beacon`, 14 captured beacons)
and a deterministic race reproduction in an isolated worktree.

Confidence: high = upheld by 3 adversarial refuters and/or direct runtime
observation; medium = static evidence with 2-refuter verification.

## Corrections to the original reports (capability vs transmission)

The lab distinguished code bundled in Boomerang from features enabled by
Basicrum's configuration and fields actually observed on beacons. Corrections
to the original privacy report:

- Battery telemetry (`c.t.bat`) is bundled but NEVER transmitted: it requires
  `Continuity.monitorStats:true`, the bundle default is `monitorStats:!1`,
  and an instrumented `navigator.getBattery` recorded zero invocations across
  all page views. The original inventory listed a battery series among
  transmitted timelines - that was capability conflation. The same applies to
  the other statsMonitor timelines (`c.t.mem`, `c.t.domsz`, `c.t.domln`,
  `c.t.mut`).
- The Continuity claim "counts only - no coordinates" was too generous the
  other way: the compressed interaction log `c.l` (default `sendLog:!0`)
  carries per-event timestamps WITH x/y coordinates, and EventTiming's `et.e`
  embeds CSS selectors of interacted elements (observed:
  `input#wp-block-search__input-2`). Keystroke VALUES are still never read -
  `c.k` is a count.
- `strip_query_string: true` protects ALL FOUR URL-bearing fields including
  `restiming` - the ResourceTiming trie pipes every URL through
  `cleanupURL()` after `trimUrls`. The original remediation implied
  ResourceTiming needed separate query-string redaction; it does not.
  `trimUrls` is only needed for PATH-level redaction (e.g. order IDs in
  `/checkout/order-received/11/`).
- UA client hints (`ua.arch/model/pltv`) are bundled but never requested
  (`request_client_hints:!1`; instrumented `getHighEntropyValues` recorded
  zero calls). `net.sd` is effectively dead code (prototype-accessor check
  never passes in Chromium). `pgu` never appears on page-load beacons
  (deleted when equal to `u`; XHR-only and `instrument_xhr:false`).
- The referrer is comparatively safe: `r` appeared only on JS-initiated
  navigations and always with the query replaced by an FNV base36 hash
  (`r=http://localhost:9081/?3obn53s7`); on link-click navigations `r` is
  absent entirely.
- `secure_cookie: true` does not block the RT cookie on http sites - the
  bundle appends `Secure` only when `location.protocol === "https:"`, so on
  http the cookie is set without the Secure attribute (silent degrade).
- The compiled plugin list is exactly: BFCache, Continuity, Errors,
  EventTiming, Memory, NavigationTiming, PageParams, PaintTiming, RT,
  ResourceTiming. No Bandwidth plugin; the string `"BA"` appears zero times
  in the bundle, and no BA cookie was created across any lab run.

## Evidence rows (privacy FAILs + UX blockers/majors, deduplicated)

| ID (checklist ref) | Severity | Anchor | Type | Repro | Observed result | Conf. |
|---|---|---|---|---|---|---|
| DF-07 (privacy 1) | high | `src/Assets.php:203-213`; bundle `strip_query_string:!1` | both | `make woocommerce-e2e-up`; visit `http://localhost:9081/?s=ZZSECRETQS777` with Playwright route on `collector.basicrum.test/**`; dump POST body. Static: `grep -o 'strip_query_string:..' plugins/basicrum/assets/js/boomr/*.min.js` | Secret transmitted in `u` (load AND unload beacon), in `nu` (full clicked href incl. `?probe=ZZSECRETNU999`), and in `restiming` (navigation entry key holds the document query; every asset's `?ver=` present). With `strip_query_string:true` injected: all four fields become `?qs-redacted`; a control page missed by the rewrite still leaked, proving causality | high |
| COOKIE-07 (privacy 2) | high | consent loader `:222-236`; bundle tail init glue | both | Run `tests/javascript/consent-optout-race.spec.js` from worktree `.claude/worktrees/wf_14de6d93-758-3/` (promise-gated delayed script, no timeouts) | Race reproduced deterministically: spec FAILS against unmodified loaders (late script inits, sets cookie, beacons); passes with the 11-line fix; full 93-test suite green incl. byte-contract and global-surface tests | high |
| BR-WP-15 (privacy 3) | medium sev / high conf | `src/Setup.php:57-60`; `src/Compatibility.php:36-54` | static | `grep -rn 'purge\|rocket_clean\|w3tc_flush' plugins/basicrum/src plugins/basicrum/uninstall.php` | No purge call anywhere; deactivate() deletes one transient; the six cache-plugin exclusion filters guarantee the loader survives in cached HTML | high |
| DF-08/COOKIE-13/DISC-04 (privacy 4) | low-disclosure | `src/Admin/Privacy.php:48`; readme.txt | both | `grep -n 'first-party cookies' plugins/basicrum/src/Admin/Privacy.php; grep -ni cookie plugins/basicrum/readme.txt`. Runtime: `context.cookies()` after a page view | RT observed: 7.000-day expiry renewed on every view (rolling), SameSite=Strict, path=/, sub-values `z,dm,si,ss,sl,tt,bcn,ld`; `si` = random UUID stable across views, echoed as `rt.si` on every beacon. No public text names RT, its lifetime, or the identifier. BA never created (runtime + zero bundle occurrences) | high |
| DISC-10 (privacy 5) | medium, ELEVATED | `languages/basicrum.pot:30` vs `basicrum.php:5` | both | `make translations && git diff --stat -- plugins/basicrum/languages` (then `git checkout -- plugins/basicrum/languages`) | Regeneration modifies basicrum.pot + bg_BG.po, so the CI translations job (`git diff --exit-code`, runs on push to main/PRs) FAILS at 0ec392e, and the `package` job (`needs: translations`) is blocked. Now a live red-CI defect on main, not a pending hazard | high |
| DISC-08 (privacy 6) | low | `readme.txt:22,70`; `src/PageTypeDetector.php` | static | `sed -n '22p;70p' plugins/basicrum/readme.txt; grep -n "return '" plugins/basicrum/src/PageTypeDetector.php` | Readme lists 7+3 types; detector returns 17 incl. checkout_payment, checkout_success, account, product_category, tag, author, date_archive | high |
| DISC-09 (privacy 7) | low | `plugins/basicrum/README.md` | static | `sed -n '4p;14p;51p;86p' plugins/basicrum/README.md` | Stock template stub with placeholder text and fake security-fix changelog; excluded from ZIP by verify-release.sh | high |
| BR-WP-14 (privacy 8) | low | `uninstall.php:13-20` | static | `grep -rn is_multisite plugins/basicrum/uninstall.php plugins/basicrum/src; echo exit=$?` | Exit 1 (no match): no multisite iteration; per-site options persist on other subsites | medium |
| BR-WP-06 (privacy 10, OVERTURNED to PASS) | minor hardening | `Validate.php:110`; bundle `beacon_url_force_https:!0` | static | `grep -o 'beacon_url_force_https..' plugins/basicrum/assets/js/boomr/*.min.js` | Protocol-relative input evades the storage-layer upgrade, but the runtime force-https default prevents plaintext beacons; input hardening optional | medium |
| CI-01/BR-DOC-12 (UX 1) | blocker | `examples/integrations/` outside plugin; readme/Page.php silent | static | `grep -rni 'borlabs\|complianz\|cookieyes\|consent api' plugins/basicrum/readme.txt plugins/basicrum/src; echo exit=$?` | Exit 1: the three tested adapters are unreachable from every operator surface and absent from the ZIP (build-release.sh packages plugins/basicrum only) | medium |
| BR-DOC-07/walkthrough-07 (UX 1) | major (verifier-corrected from blocker) | `Helpers.php:38`; `Page.php:457-488` | both | `make woocommerce-e2e-up`; set consent mode; `curl -s http://localhost:9081/ \| grep -o 'consent-boomerang[^\"]*'` then observe zero collector requests in an anonymous browser | Only the consent loader is fetched; Boomerang never loads; no beacon, no cookie, no console output; wp-admin shows only "Settings saved." | high |
| walkthrough-05 (UX 6) | major | `Page.php:176`; single internal href on the page | both | On the settings page run `document.querySelectorAll('#wpbody a[href^="http"]')` | Zero external links; "Basicrum backoffice" named but never linked; account requirement stated only in readme FAQ | high |
| walkthrough-06/BR-DOC-09 (UX 3) | major | `Assets.php:80-83`; `Page.php:192` | both | `curl -s http://localhost:9081/ \| grep -c basicrum` (nonzero anonymously) vs view-source in a logged-in admin session (zero) | Admin's own pages contain no Basicrum markup with default track_admins='0'; no copy anywhere explains it | high |
| BR-DOC-08 (UX 3) | major | readme.txt FAQ 46-74 | static | `grep -rniE 'verify\|devtools\|health' plugins/basicrum/src plugins/basicrum/readme.txt` | No verification story on any surface | medium |
| BR-DOC-10/CI-03 (UX 4) | major | `Validate.php:26,140-158`; zero console statements | static | `grep -rn 'console\.' plugins/basicrum/assets/js/loaders examples/integrations; echo exit=$?` | Exit 1: all integration failure modes silent; Site ID validated by regex shape only | medium |
| CI-02 (UX 2) | major | `Page.php:699-726` | static | `sed -n '699,726p' plugins/basicrum/src/Admin/Settings/Page.php` | Inert `reportBasicrumConsent()` skeleton; no placement instructions, no event wiring | medium |
| CI-04 (UX 2) | major | `Page.php:720`; `readme.txt:66` | static | `sed -n '720p' plugins/basicrum/src/Admin/Settings/Page.php` | Abstract load-order rule with no concrete recipe | medium |
| CI-05 (UX 2) | major | `examples/integrations/cookieyes.js:4` | static | `sed -n '1,6p' examples/integrations/cookieyes.js` | `consentCategory='analytics'` hard-coded with no warning in the file; wrong-category symptom (silent no-data) documented only in the non-shipped README | medium |
| BR-DOC-13 (UX 8) | major | no purge caveat on any shipped surface | static | `grep -ni 'purge\|cached' plugins/basicrum/readme.txt; echo exit=$?` | Exit 1; the non-shipped examples README does warn about cache clearing, the shipped surfaces do not | medium |
| issue-http-strictness-inverted (UX 5, verifier-downgraded to minor) | minor | `Page.php:384,390`; `readme.txt:72-74` | static | `sed -n '384p;390p' plugins/basicrum/src/Admin/Settings/Page.php` | Label "HTTP Strictness" on a checkbox that relaxes strictness; readme FAQ repeats the inversion | medium |
| BR-DOC-11 (UX 7, verifier-downgraded to minor) | minor | `readme.txt:76-81`; `wordpress-org-assets/` | static | `sed -n '76,81p' plugins/basicrum/readme.txt; ls wordpress-org-assets/` | Four screenshot captions, zero screenshot files in the repo | high |

## Runtime capture appendix

- Beacon shape: every beacon was a POST (`application/x-www-form-urlencoded`);
  each page view emits a page-load beacon (~88-95 params) and an unload
  beacon (~29-46 params). Full param-name lists were recorded per beacon;
  raw dumps live in the session scratchpad (`beacon-lab.js`, `mini-lab.js`,
  `lab-normal.json`, `lab-strip.json`).
- Fields observed on every page-load beacon (bundle-default auto-init, no
  Basicrum config gate): `dev.mem`, `cpu.cnc`, `mem.total/used/limit`,
  `mem.lsln/lssz/ssln/sssz` (sizes only - storage contents never read onto
  the beacon), `scr.xy/bpp/orn`, `dom.ln/sz/ck` (`dom.ck` is the LENGTH of
  document.cookie) and the dom census, `mob.dl`, `mob.etype`, `ua.plt`,
  `ua.vnd`.
- Continuity (explicitly enabled by Assets.php): interaction counts plus the
  compressed `c.l` event log (timestamps + x/y coordinates) and EventTiming
  entries embedding element CSS selectors; keystroke values never read.
- RT cookie on plain http: set WITHOUT the Secure attribute (bundle appends
  Secure only on https); SameSite=Strict always; rolling 7-day expiry
  renewed on every page view; `si` stable, `sl` incrementing; transient
  navigation sub-values (`s,ul,cl,hd,nu,r`) are written at unload and
  cleared on the next load, with `nu` stored only as a hash at rest.
- Opt-out race fix (worktree `.claude/worktrees/wf_14de6d93-758-3/`,
  uncommitted): OPT_OUT sets `mainWin.basicRumBoomerangConfig = null` and a
  `OPT_OUT_BASICRUM_LOADER_WRAPPER.optedOut = true` marker (a property on the
  existing wrapper function, not a new window global); change sits entirely
  outside the BEGIN/END standard-loader block; re-grant still requires a
  reload, matching documented behavior.
