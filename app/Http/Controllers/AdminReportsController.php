<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DashboardReportService;
use Carbon\Carbon;

class AdminReportsController extends Controller
{
    public function __construct(
        private DashboardReportService $reportService,
    ) {}

    /**
     * Validated, parsed date range: bad input gets a 422 instead of a 500,
     * and only clean Y-m-d strings reach views and the download filename.
     *
     * @return array{0: string, 1: string, 2: Carbon, 3: Carbon}
     */
    private function dateRange(Request $request): array
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to'   => 'nullable|date|after_or_equal:date_from',
        ]);

        $startDate = Carbon::parse($request->input('date_from', Carbon::now()->startOfMonth()->toDateString()))->startOfDay();
        $endDate   = Carbon::parse($request->input('date_to', Carbon::now()->toDateString()))->endOfDay();

        return [$startDate->toDateString(), $endDate->toDateString(), $startDate, $endDate];
    }

    public function index(Request $request)
    {
        [$from, $to, $startDate, $endDate] = $this->dateRange($request);

        $reportData = $this->reportService->getReportData($startDate, $endDate);

        return view('Dashbord_Admin.reports', [
            'from' => $from,
            'to'   => $to,
            ...$reportData,
        ]);
    }

    public function export(Request $request)
    {
        [$from, $to, $startDate, $endDate] = $this->dateRange($request);

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="orders-report-' . $from . '-to-' . $to . '.csv"',
        ];

        return response()->stream(
            $this->reportService->streamOrdersCsv($startDate, $endDate),
            200,
            $headers
        );
    }
}
