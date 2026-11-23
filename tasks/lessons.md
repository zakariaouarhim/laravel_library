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

## 2026-11-19 — Verify the real code path, not an equivalent call
While adding the أسير الكتب source I checked covers would download by calling
`Http::get($coverUrl)` in tinker, got 200 + bytes, and told the user the server
could fetch them. The production path is `ImageService::downloadFromUrl()`,
which used `@file_get_contents()` — and PHP's http wrapper writes the path into
the request line verbatim, so these URLs (aseeralkotb.com names every cover
file in Arabic) failed. The feature shipped broken and the user found it.

**Rule:** to claim something works, exercise the function the app actually
calls, end to end, and assert on its observable output (file on disk, DB row) —
never a hand-rolled call that merely resembles it. "I fetched the URL" is not
evidence that "the importer can fetch the URL".

**Corollary — silent failures hide this.** `@`-suppression plus
`catch { Log::error(); return null; }` let approve() create the book with no
cover and report success, so nothing surfaced until a human looked. When a
step can fail without blocking the operation, surface it in the response.

## 2026-11-19 — Check per-directory ownership under public/, not just storage/
`public/images/books/large` was root:root 755 while its siblings were
www-data, so the large variant silently never wrote on the VPS (main + thumb
did). CLAUDE.md's ownership remedy only covers `storage` + `bootstrap/cache`.

**Rule:** after any image-pipeline change, verify EVERY destination directory
is www-data-writable on the server (`find public -type d ! -user www-data`),
not just the one the happy path touches.

## 2026-11-23 — Don't report a root cause you only inferred
While verifying an unrelated deploy I noticed 405 logged "Error in user
creation" entries and told the user "registration has no length rule on
`name`". It had `max:255`. The real cause was
`Schema::defaultStringLength(191)` in AppServiceProvider making the column
`varchar(191)`, so 192-255 chars passed validation and died on INSERT. I had
read the symptom and guessed the cause, then stated the guess as fact.

The same guess appeared twice more in that investigation: the migrations say
`$table->string('name')` (255) while every real column is 191, and an
exploration agent reported 255 from the migration.

**Rule:** before naming a cause in a report, run the one command that proves
it. For a column it is `SHOW COLUMNS`, never the migration — a global
`defaultStringLength` or an out-of-band `ALTER` makes migrations lie. If there
is no time to verify, say "likely" and say what would confirm it.

**Corollary:** a logged error is the failure path. Ask what the *success* path
of the same attack looks like. Here the 405 errors were only the payloads too
long to fit; the ones that fit created two real spam accounts nobody had
noticed.

