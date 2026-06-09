# Queue worker setup — Library Fokara VPS

The codebase ships `ProcessBookCoverJob` so book-cover processing can run
asynchronously. With `QUEUE_CONNECTION=sync` (the current default) the job
runs inline at dispatch — code is correct but there's no perf benefit. To
actually offload work, run a real queue worker.

## One-time setup

```bash
# 1. Switch from sync to database driver (cheapest — no Redis dependency).
#    Edit .env on the VPS:
QUEUE_CONNECTION=database

# 2. Create the jobs table.
php artisan queue:table
php artisan migrate

# 3. Verify dispatch lands in the table.
php artisan tinker --execute="dispatch(new App\Jobs\UpdateInterestScoresJob(1));"
php artisan tinker --execute="echo DB::table('jobs')->count();"  # > 0

# 4. Drain it manually once to confirm worker code path runs.
php artisan queue:work --once
```

## Supervisor (production)

`/etc/supervisor/conf.d/library-fokara-worker.conf`:

```ini
[program:library-fokara-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/library_fokara/artisan queue:work --queue=default --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/supervisor/library-fokara-worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start library-fokara-worker:*
sudo supervisorctl status library-fokara-worker:*   # both processes RUNNING
```

## Deploy hook

After each `git pull` + `php artisan optimize:clear`, restart workers so they
pick up new code (workers are long-lived PHP processes — they cache the
loaded classes until killed):

```bash
php artisan queue:restart
# supervisor's autorestart immediately spawns fresh workers
```

Add `php artisan queue:restart` to your existing deploy script (somewhere
between `composer install` and the final cache warmup).

## Where to use `ProcessBookCoverJob`

The job is forward-compatible code — it does nothing today (runs inline
under sync). Wire it where image processing currently blocks the request:

- **API-triggered enrichment** in `BookEnrichmentService` (line ~524 calls
  `$this->imageService->downloadFromUrl`) — convert to:
  ```php
  dispatch(new ProcessBookCoverJob($book->id, $imageUrl, 'images/books', 'api'));
  ```
  Book row keeps its current `image` (often the default placeholder) until
  the worker finishes — usually <10s. Public pages show the placeholder in
  the meantime.

Do **NOT** wire it for:
- Admin one-book uploads (`AdminBookController`) — UX needs the image URL
  in the response.
- The staging image fetch in `BookIngestionService` (line ~799) — staging
  images are previews the admin picks from before saving.
- The `ImportBooksFromCovers` CLI command — already runs outside HTTP.

## Monitoring

```bash
# Stuck jobs? Failed jobs land here:
php artisan queue:failed

# Retry a single failed job:
php artisan queue:retry <uuid>

# Retry all failed jobs:
php artisan queue:retry all

# Real-time worker logs:
tail -f /var/log/supervisor/library-fokara-worker.log
```
