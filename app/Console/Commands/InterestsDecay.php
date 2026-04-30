<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InterestsDecay extends Command
{
    protected $signature = 'interests:decay';

    protected $description = 'Apply ~3% weekly decay to dormant interest scores (rows untouched for 7+ days)';

    public function handle(): int
    {
        // Daily multiplier: 0.97^(1/7) ≈ 0.9956 → ~3% per week → ~22-week half-life.
        // Floor at 0 so negative scores aren't amplified, and zero out anything < 0.5.
        $affected = DB::update("
            UPDATE user_category_interests
            SET score = GREATEST(0, ROUND(score * 0.9956, 2))
            WHERE last_interaction_at IS NULL
               OR last_interaction_at < (NOW() - INTERVAL 7 DAY)
        ");

        DB::update("
            UPDATE user_category_interests
            SET score = 0
            WHERE score > 0 AND score < 0.5
        ");

        $this->info("Decay applied to {$affected} rows.");

        return self::SUCCESS;
    }
}
