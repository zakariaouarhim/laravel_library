<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

/**
 * Retire the `pending_books` staging queue.
 *
 * The "إضافة كتاب من API" page it backed is gone: searching the web sources is
 * now a step inside the catalogue-import review page, which writes its results
 * straight into `catalogue_reference` as `api:` rows. That removed the separate
 * ingest page, its two review screens, and a second Book-creation path that had
 * drifted behind the shared importer (no ISBN normalisation into a varchar(17)
 * column, no cover zoom, no duplicate override, search indexed before the
 * author and categories existed).
 *
 * Rows still awaiting a decision are converted first, so queued work reappears
 * under the dashboard's "بحث API" source filter instead of vanishing with the
 * table. The conversion is idempotent, so running it by hand beforehand (with
 * --dry-run first) is encouraged and changes nothing here.
 *
 * Irreversible by design: down() recreates the table empty rather than
 * pretending the rows can come back.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pending_books')) {
            return;
        }

        Artisan::call('pending:convert-to-catalogue');
        // Surface the command's report in the migration output, so a deploy log
        // records how many rows were carried over.
        foreach (preg_split('/\R/', trim(Artisan::output())) as $line) {
            if (trim($line) !== '') {
                echo '    ' . $line . PHP_EOL;
            }
        }

        Schema::drop('pending_books');
    }

    public function down(): void
    {
        if (Schema::hasTable('pending_books')) {
            return;
        }

        // The shape as it stood after 2026_05_09_120001_pending_books_multi_source
        // and 2026_05_12_120001_pending_books_add_author_id. Empty: the rows were
        // converted into catalogue_reference, they are not recoverable here.
        Schema::create('pending_books', function ($table) {
            $table->id();
            $table->string('title');
            $table->string('author_name')->nullable();
            $table->unsignedBigInteger('author_id')->nullable();
            $table->string('language', 20)->default('french');
            $table->string('status', 20)->default('enriched')->index();
            $table->json('api_results')->nullable();
            $table->json('staging_images')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedBigInteger('existing_book_id')->nullable();
            $table->unsignedBigInteger('approved_book_id')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }
};
