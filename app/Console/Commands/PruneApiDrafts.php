<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Delete `api:` catalogue rows nobody ever acted on.
 *
 * The catalogue-import "find this book" panel writes one row when an admin
 * adopts an API result, so the whole existing review UI can work on it. If the
 * admin then walks away, the row sits there forever. A row with a
 * catalogue_reviews entry has been imported or deliberately skipped, so it is
 * never touched here.
 *
 * Replaces `staging:prune-images`, which cleaned up after the retired
 * pending_books ingest flow.
 */
class PruneApiDrafts extends Command
{
    protected $signature = 'catalogue:prune-api-drafts
        {--days=30 : Delete unreviewed api: rows older than N days}
        {--dry-run : Report what would go, delete nothing}';

    protected $description = 'Delete api: catalogue rows older than N days that were never reviewed';

    public function handle(): int
    {
        $days   = max(1, (int) $this->option('days'));
        $cutoff = now()->subDays($days);

        $stale = DB::table('catalogue_reference')
            ->where('dedup_key', 'like', 'api:%')
            ->where('catalogue_reference.created_at', '<', $cutoff)
            ->whereNotExists(fn($q) => $q
                ->from('catalogue_reviews')
                ->whereColumn('catalogue_reviews.catalogue_reference_id', 'catalogue_reference.id'));

        $ids = $stale->pluck('catalogue_reference.id')->all();

        if ($ids === []) {
            $this->info("Nothing to prune: no unreviewed api: rows older than {$days} days.");

            return 0;
        }

        if ($this->option('dry-run')) {
            $this->info(count($ids) . " unreviewed api: row(s) older than {$days} days would be deleted (dry run).");
            foreach (DB::table('catalogue_reference')->whereIn('id', array_slice($ids, 0, 10))->pluck('title') as $t) {
                $this->line('  - ' . mb_substr((string) $t, 0, 80));
            }

            return 0;
        }

        $deleted = 0;
        foreach (array_chunk($ids, 500) as $chunk) {
            $deleted += DB::table('catalogue_reference')->whereIn('id', $chunk)->delete();
        }

        $this->info("Pruned {$deleted} unreviewed api: row(s) older than {$days} days.");

        return 0;
    }
}
