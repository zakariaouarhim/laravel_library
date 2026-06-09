<?php

namespace App\Observers;

use App\Models\Series;
use Illuminate\Support\Facades\Cache;

class SeriesObserver
{
    public function created(Series $series): void
    {
        $this->forgetSeriesCaches();
    }

    public function updated(Series $series): void
    {
        $this->forgetSeriesCaches();
    }

    public function deleted(Series $series): void
    {
        $this->forgetSeriesCaches();
    }

    /**
     * Bust the cached homepage series carousels.
     */
    private function forgetSeriesCaches(): void
    {
        Cache::forget('arabic_series_home');
        Cache::forget('english_series_home');
    }
}
