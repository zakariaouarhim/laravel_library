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

    public function index(Request $request)
    {
        $from = $request->input('date_from', Carbon::now()->startOfMonth()->toDateString());
        $to   = $request->input('date_to', Carbon::now()->toDateString());

        $startDate = Carbon::parse($from)->startOfDay();
        $endDate   = Carbon::parse($to)->endOfDay();

        $reportData = $this->reportService->getReportData($startDate, $endDate);

        return view('Dashbord_Admin.reports', [
            'from' => $from,
            'to'   => $to,
            ...$reportData,
        ]);
    }

    public function export(Request $request)
    {
        $from = $request->input('date_from', Carbon::now()->startOfMonth()->toDateString());
        $to   = $request->input('date_to', Carbon::now()->toDateString());

        $startDate = Carbon::parse($from)->startOfDay();
        $endDate   = Carbon::parse($to)->endOfDay();

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
