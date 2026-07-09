Here is the cleaned and properly reconstructed text from the image:

---

# Workflow Orchestration

## 1. Plan Mode Default

* Enter plan mode for ANY non-trivial task (3+ steps or architectural decisions)
* If something goes sideways, STOP and re-plan immediately — don’t keep pushing
* Use plan mode for verification steps, not just building
* Write detailed specs upfront to reduce ambiguity

---

## 2. Subagent Strategy

* Use subagents liberally to keep main context window clean
* Offload research, exploration, and parallel analysis to subagents
* For complex problems, throw more compute at it via subagents
* One task per subagent for focused execution

---

## 3. Self-Improvement Loop

* After ANY correction from the user: update `tasks/lessons.md` with the pattern
* Write rules for yourself that prevent the same mistake
* Ruthlessly iterate on these lessons until mistake rate drops
* Review lessons at session start for relevant project

---

## 4. Verification Before Done

* Never mark a task complete without proving it works
* Diff behavior between main and your changes when relevant
* Ask yourself: “Would a staff engineer approve this?”
* Run tests, check logs, demonstrate correctness

---

## 5. Demand Elegance (Balanced)

* For non-trivial changes: pause and ask “Is there a more elegant way?”
* If a fix feels hacky: “Knowing everything I know now, implement the elegant solution”
* Skip this for simple, obvious fixes — don’t over-engineer
* Challenge your own work before presenting it

---

## 6. Autonomous Bug Fixing

* When given a bug report: just fix it. Don’t ask for hand-holding
* Point at logs, errors, failing tests — then resolve them
* Zero context switching required from the user
* Go fix failing CI tests without being told how

---

# Task Management

1. **Plan First**: Write plan to `tasks/todo.md` with checkable items
2. **Verify Plan**: Check in before starting implementation
3. **Track Progress**: Mark items complete as you go
4. **Explain Changes**: High-level summary at each step
5. **Document Results**: Add review section to `tasks/todo.md`
6. **Capture Lessons**: Update `tasks/lessons.md` after corrections

---

# Core Principles

* **Simplicity First**: Make every change as simple as possible. Minimal impact code.
* **No Laziness**: Find root causes. No temporary fixes. Senior developer standards.
* **Minimal Impact**: Changes should only touch what’s necessary. Avoid introducing bugs.

---

# Deployment to the VPS

"Push to the VPS" for this project means: **commit → push to GitHub → SSH to the
server and `git pull` there** (plus migrations/cache rebuild). The server pulls
from GitHub; it is never edited directly.

## Coordinates
* **Git remote**: `origin` → `https://github.com/zakariaouarhim/laravel_library.git`, branch **`main`**.
* **VPS SSH**: `ssh root@178.104.100.242` (key auth via `~/.ssh/id_ed25519` in Windows Git Bash — no password).
* **VPS project path**: `/var/www/library_fokara`

## Steps
1. `git push origin main` (from the local repo).
2. Deploy on the server (single SSH command):
   ```bash
   ssh root@178.104.100.242 'cd /var/www/library_fokara && git pull origin main \
     && php artisan migrate --force \
     && php artisan config:clear && php artisan route:clear \
     && php artisan view:clear && php artisan view:cache'
   ```
   * Run `migrate --force` only when the commit adds migrations.
   * Blade-only changes: just `git pull` + `view:clear && view:cache`.
3. Verify on the server (e.g. `php artisan route:list | grep <name>`, or a `tinker --execute` count).

## Do NOT commit / never push
* `.claude/settings.json` (contains credentials).
* Book cover images `public/images/books/*.webp` (large, generated).
* `reader_DB/` and `storage/app/catalogue_reference/*.sql` (git-ignored source data).
* n8n workflow JSON files at the repo root.
* The **low-stock WIP** while unfinished: `AdminBookController@updateStock`,
  `resources/views/Dashbord_Admin/dashboard.blade.php`, and the
  `products.update-stock` route in `routes/web.php`. Diff every file with
  `git diff --cached` before committing.

## Data not in git (manual, one-time per environment)
* The reference catalogue lives in a git-ignored dump. To (re)load it on the VPS:
  upload `storage/app/catalogue_reference/catalogue_reference.sql`, then
  `php artisan catalogue:import`. It is already loaded (81,782 rows).
* Category **ids differ between local and the VPS** — never hardcode category
  ids in import/seed code; resolve by name at runtime.
