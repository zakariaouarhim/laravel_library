# Lessons

## 2026-07-17 — Partial staging: review the WHOLE staged diff, never grep for known markers
Two incidents in one session while committing around the low-stock WIP:
(1) a chained `git add` after a failed `git apply` silently never ran → pushed
a half-feature commit (controller without its service/UI); (2) verifying a
partially-staged file by grepping for the KNOWN WIP marker (`updateStock`)
missed OTHER uncommitted work in the same file (counter routes) → deployed
routes pointing at a controller that doesn't exist in production, then while
fixing that, re-introduced the update-stock route the same way.

**Rules:**
- Before every commit, read the ENTIRE `git diff --cached` output and check
  every hunk against intent. Grepping for a known marker only finds the WIP
  you remember — `routes/web.php` and other shared files accumulate WIP from
  several features at once.
- Never chain `git add` behind a command that can fail (`x && git add …`);
  stage in its own command and verify with `git status --short` after.
- After any deploy that touched `routes/web.php`, run `php artisan route:list`
  ON THE SERVER as a smoke test — it fatals if a route references a missing
  controller/method.

## 2026-07-15 — Image "zoom/crop" features: ask for the target shape first
Built a uniform zoom (same crop factor both axes) for cutting white margins off
covers; the admin actually wanted to reshape squarish sources into rectangular
book covers, which needs independent width/height cropping. Uniform zoom can
never change the aspect ratio, so it couldn't solve the real problem.

**Rule:** when an admin asks to "zoom/crop" an image, the requirement is almost
always a *target shape* (book-cover rectangle), not magnification. Ask "what
should the final image look like?" before choosing the crop model, and prefer
per-axis controls over a single uniform factor.
