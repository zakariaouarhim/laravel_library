<?php

namespace App\Console\Commands;

use App\Services\CatalogueDraftService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-time move of the retired `pending_books` queue into the catalogue-import
 * review flow: every row still awaiting a decision becomes a `catalogue_reference`
 * row tagged `api:`, so it reappears under the dashboard's "بحث API" source
 * filter instead of being lost with the table.
 *
 * Only `enriched` and `failed` rows carry work worth keeping. `approved` ones are
 * already real books, `discarded` ones were rejected on purpose, and `duplicate`
 * ones were never approvable in the old UI anyway (the book they clashed with is
 * in the store, and the new "find this book" step surfaces it).
 *
 * Deliberately uses the query builder, not the PendingBook model: the model is
 * removed in the same change, and the migration that drops the table calls this
 * command first.
 */
class ConvertPendingBooks extends Command
{
    protected $signature = 'pending:convert-to-catalogue
        {--dry-run : Report what would be converted, write nothing}';

    protected $description = 'Convert leftover pending_books rows into api: catalogue_reference rows';

    public function handle(CatalogueDraftService $drafts): int
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('pending_books')) {
            $this->info('No pending_books table — nothing to convert.');

            return 0;
        }

        $rows = DB::table('pending_books')
            ->whereIn('status', ['enriched', 'failed'])
            ->orderBy('id')
            ->get(['id', 'title', 'author_name', 'language', 'status', 'api_results']);

        if ($rows->isEmpty()) {
            $this->info('No reviewable pending_books rows to convert.');

            return 0;
        }

        $dryRun = (bool) $this->option('dry-run');
        $this->info($rows->count() . ' reviewable row(s) found' . ($dryRun ? ' [dry run]' : '') . '.');

        $converted = $skipped = 0;

        foreach ($rows as $p) {
            $sources = json_decode($p->api_results ?: '[]', true);
            $sources = is_array($sources) ? $sources : [];

            // An ISBN-only lookup that never resolved leaves "ISBN: 978…" as the
            // title; there is no book to review in that case.
            $title = trim((string) $p->title);
            if ($title === '' || preg_match('/^ISBN:\s*[\dXx\-]+$/', $title)) {
                $this->line("  skip #{$p->id}: no usable title ({$title})");
                $skipped++;
                continue;
            }

            $language = in_array($p->language, array_keys(CatalogueDraftService::LANGUE_TOKEN), true)
                ? $p->language
                : 'arabic';

            try {
                if ($dryRun) {
                    $fields = $drafts->mergeSources($sources, $title, (string) $p->author_name);
                    $this->line(sprintf('  would convert #%d [%s] %s — %s (%d source%s)',
                        $p->id, $p->status, mb_substr($fields['title'], 0, 40),
                        $fields['author'] ?: 'بدون مؤلف', count($sources), count($sources) === 1 ? '' : 's'));
                } else {
                    $row = $drafts->adoptSources($sources, $title, (string) $p->author_name, $language);
                    $this->line("  #{$p->id} -> catalogue row #{$row->id} ({$row->dedup_key}), quality {$row->completeness}");
                }
                $converted++;
            } catch (\Throwable $e) {
                $this->warn("  skip #{$p->id}: " . $e->getMessage());
                $skipped++;
            }
        }

        $this->newLine();
        $this->info($dryRun
            ? "Dry run: {$converted} would convert, {$skipped} skipped. Nothing written."
            : "Converted {$converted}, skipped {$skipped}. Review them under the \"بحث API\" source filter.");

        return 0;
    }
}
