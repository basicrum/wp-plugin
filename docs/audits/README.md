# Basicrum Audits

This folder keeps the July 2026 privacy and operator-experience review in a
form that can be worked through without losing its source evidence.

## Files

- `checklist.md` - canonical, deduplicated remediation checklist.
- `evidence.md` - finding IDs, anchors, reproduction steps, observed results,
  and corrections from runtime verification.
- `privacy-audit.md` - source privacy and compliance-readiness audit snapshot.
- `operator-experience-audit.md` - source webmaster and operator-experience
  audit snapshot.

## Checklist workflow

1. Work through `checklist.md` in P0, P1, P2, then P3 order unless a dependency
   requires a different sequence.
2. Treat the two source audits as snapshots. Record remediation progress only
   in `checklist.md` so status does not diverge across files.
3. Before checking an item, add or update automated tests, run the relevant
   repository checks, and confirm the behavior against `evidence.md`.
4. Record the implementing commit and tests beneath the completed item.
5. Keep collector/backend work and operator/legal decisions separate from
   plugin engineering. Automated tests do not constitute legal approval.
6. If new evidence overturns a finding, update `evidence.md` first and note the
   correction in the checklist rather than silently deleting the task.
