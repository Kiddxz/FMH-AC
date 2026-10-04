<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Services\ClinicReports;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Reports for Staff (/staff/reports), Vet/Admin (/admin/reports) and Super Admin (/superadmin/reports).
 * Capstone FR-REQ021, FR-REQ027 and Fig 6.2: choose a report, filter by date range and status,
 * the filter is validated, then view it, print it (or "Save as PDF") or export it as a CSV file.
 */
class ReportController extends Controller
{
    public function __invoke(Request $request, ClinicReports $reports): mixed
    {
        $type = $request->query('report', 'appointments');
        $type = array_key_exists($type, ClinicReports::TYPES) ? $type : 'appointments';

        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'status' => ['nullable', Rule::in(array_keys(ClinicReports::STATUSES[$type]))],
        ], [
            'to.after_or_equal' => 'The "to" date must be the same as or after the "from" date.',
            'status.in' => 'Choose a status from the list.',
        ]);

        // Default: this month. At most one year at a time, so a report stays quick.
        $from = isset($data['from']) ? Carbon::parse($data['from']) : today()->startOfMonth();
        $to = isset($data['to']) ? Carbon::parse($data['to']) : today();
        if ($from->diffInDays($to) > 366) {
            return back()->withErrors(['to' => 'Please choose a date range of one year or less.'])->withInput();
        }

        $status = $data['status'] ?? null;
        $report = $reports->build($type, $from, $to, $status);
        $area = explode('.', (string) $request->route()->getName())[0];
        $filters = ['report' => $type, 'from' => $from->toDateString(), 'to' => $to->toDateString(), 'status' => $status];

        if ($request->query('format') === 'csv') {
            ActivityLog::record('exported', 'Reports', 'Exported ' . $report['title'] . ' (' . $filters['from'] . ' to ' . $filters['to'] . ') as CSV.');

            return $this->csv($report, $filters);
        }

        $view = $request->query('format') === 'print' ? 'reports.print' : 'reports.index';

        return view($view, compact('area', 'report', 'filters', 'type'));
    }

    // The full list of the report as a CSV file (opens in Excel)
    private function csv(array $report, array $filters): StreamedResponse
    {
        $name = trim(preg_replace('/[^a-z]+/', '-', strtolower($report['title'])), '-') . '_' . $filters['from'] . '_to_' . $filters['to'] . '.csv';

        return response()->streamDownload(function () use ($report, $filters) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");   // lets Excel read ñ and ₱ correctly
            fputcsv($out, ['FMH Animal Clinic - ' . $report['title']]);
            fputcsv($out, ['Period', $filters['from'] . ' to ' . $filters['to'], 'Status', $filters['status'] ? ucfirst($filters['status']) : 'All']);
            fputcsv($out, []);
            foreach ($report['cards'] as [, $label, $value]) {
                fputcsv($out, [$label, $value]);
            }
            fputcsv($out, []);
            fputcsv($out, $report['detail'][1]);
            foreach ($report['detail'][2] as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
