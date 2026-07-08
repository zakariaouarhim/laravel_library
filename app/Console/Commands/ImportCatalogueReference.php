<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Load the reference catalogue (~81k books scraped from almouggar.com) into the
 * `catalogue_reference` table from a mysqldump. The dump itself carries
 * DROP TABLE IF EXISTS + CREATE TABLE + INSERTs, so the table's schema is defined
 * by the dump (single source of truth) and the import is fully idempotent — run
 * it again to refresh. The .sql lives under storage/app/catalogue_reference and
 * is git-ignored (uploaded to the VPS separately).
 */
class ImportCatalogueReference extends Command
{
    protected $signature = 'catalogue:import
        {--file= : Path to the mysqldump .sql (default: storage/app/catalogue_reference/catalogue_reference.sql)}';

    protected $description = 'Import the reference catalogue (used to enrich our books) from a mysqldump';

    public function handle(): int
    {
        $file = $this->option('file')
            ?: storage_path('app/catalogue_reference/catalogue_reference.sql');

        if (!is_file($file)) {
            $this->error("Dump not found: {$file}");
            return 1;
        }

        $this->info('Importing catalogue reference from ' . basename($file) . ' ...');

        $handle = fopen($file, 'r');
        if (!$handle) {
            $this->error('Could not open the dump file.');
            return 1;
        }

        // mysqldump keeps every statement on complete lines (newlines inside
        // string literals are escaped), so accumulate lines until one ends with
        // ';' and run that statement. This avoids a single 57MB exec.
        $buffer     = '';
        $statements = 0;
        $inserts    = 0;

        // Foreign key checks off for a clean drop/recreate regardless of order.
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            while (($line = fgets($handle)) !== false) {
                $trimmed = rtrim($line);

                // Skip empty lines and standalone SQL comments.
                if ($trimmed === '' || str_starts_with(ltrim($trimmed), '--')) {
                    continue;
                }

                $buffer .= $line;

                if (substr($trimmed, -1) === ';') {
                    DB::unprepared($buffer);
                    $statements++;
                    if (stripos($buffer, 'INSERT INTO') !== false) {
                        $inserts++;
                        $this->output->write('.');
                    }
                    $buffer = '';
                }
            }
        } finally {
            fclose($handle);
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $this->newLine();
        $count = DB::table('catalogue_reference')->count();
        $this->info("Done. {$statements} statements, {$inserts} INSERT batches → {$count} rows in catalogue_reference.");

        return 0;
    }
}
