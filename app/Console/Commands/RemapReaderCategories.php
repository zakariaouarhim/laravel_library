<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\ReaderStagingBook;
use App\Services\ReaderImportMapper;
use Illuminate\Console\Command;

/**
 * Repairs staged reader rows whose suggested category points at a category id
 * that doesn't exist in THIS environment (the old mapper hardcoded dev-DB ids;
 * on the VPS some of those ids are absent, which made approval fail with
 * "The selected category_ids.0 is invalid"). Recomputes the suggestion by name
 * for broken, not-yet-imported rows only — imported rows and rows with a valid
 * category are left untouched.
 */
class RemapReaderCategories extends Command
{
    protected $signature = 'reader:remap-categories {--dry-run : Report what would change without saving}';

    protected $description = 'Fix staged reader rows whose category id is missing in this environment';

    public function handle(ReaderImportMapper $mapper): int
    {
        $valid = Category::pluck('id')->flip();
        $dry   = $this->option('dry-run');

        $fixed = 0; $unresolved = 0;

        $rows = ReaderStagingBook::where('status', '!=', 'imported')->get();
        foreach ($rows as $r) {
            $catIds    = array_map('intval', $r->category_ids ?? []);
            $primaryOk = $r->primary_category_id && isset($valid[$r->primary_category_id]);
            $catsOk    = !empty($catIds) && collect($catIds)->every(fn($id) => isset($valid[$id]));
            if ($primaryOk && $catsOk) {
                continue; // already valid
            }

            $sid = $mapper->suggestCategoryId($r->source_categories ?? [], $r->language ?? 'arabic');
            if (!$sid) {
                $unresolved++;
                continue;
            }

            $this->line("#{$r->id} '" . mb_substr($r->name, 0, 30) . "': " . json_encode($catIds) . " -> [{$sid}]");
            if (!$dry) {
                $r->update(['category_ids' => [$sid], 'primary_category_id' => $sid, 'suggested_category_id' => $sid]);
            }
            $fixed++;
        }

        $this->info(($dry ? '[dry-run] ' : '') . "Fixed {$fixed} row(s); {$unresolved} unresolved; " . ($rows->count() - $fixed - $unresolved) . " already valid.");

        return 0;
    }
}
