<?php

namespace App\Http\Controllers;

use App\Models\SearchQuery;
use Illuminate\Http\Request;

/**
 * Surfaces aggregated SearchQuery data to admins. Used as a vocabulary source
 * for category editorial copy (top queries) and inventory gap detection
 * (zero-result queries).
 */
class AdminSearchInsightsController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'window' => 'nullable|in:7,30,90,365',
            'source' => 'nullable|in:all,page,autocomplete',
        ]);

        $windowDays = (int) ($request->input('window') ?: 30);
        $source     = $request->input('source', 'all');
        $since      = now()->subDays($windowDays);

        $baseQuery = SearchQuery::query()->since($since);
        if ($source !== 'all') {
            $baseQuery->where('source', $source);
        }

        $topQueries = (clone $baseQuery)
            ->selectRaw('normalized_query, COUNT(*) as hits, AVG(result_count) as avg_results, MAX(query) as sample_query, MAX(created_at) as last_seen')
            ->groupBy('normalized_query')
            ->orderByDesc('hits')
            ->limit(50)
            ->get();

        $zeroResultQueries = (clone $baseQuery)
            ->zeroResults()
            ->selectRaw('normalized_query, COUNT(*) as hits, MAX(query) as sample_query, MAX(created_at) as last_seen')
            ->groupBy('normalized_query')
            ->orderByDesc('hits')
            ->limit(50)
            ->get();

        $totals = [
            'all_searches'    => (clone $baseQuery)->count(),
            'unique_queries'  => (clone $baseQuery)->distinct('normalized_query')->count('normalized_query'),
            'zero_result_pct' => (function () use ($baseQuery) {
                $total = (clone $baseQuery)->count();
                if ($total === 0) return 0;
                $zero = (clone $baseQuery)->zeroResults()->count();
                return round(($zero / $total) * 100, 1);
            })(),
        ];

        $perDay = (clone $baseQuery)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as hits')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        return view('Dashbord_Admin.search_insights', compact(
            'topQueries', 'zeroResultQueries', 'totals', 'perDay', 'windowDays', 'source'
        ));
    }
}
