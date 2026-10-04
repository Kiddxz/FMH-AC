<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Activity log viewer for the Super Admin (capstone NFR-REQ023, NFR-REQ024, SCOPE-12):
 * who did what, in which module, when and from which computer (IP address).
 * Filters: text, module, action, user and date range. The filtered list can be exported as CSV.
 * The log is read-only: there is no edit or delete anywhere.
 */
class ActivityLogController extends Controller
{
    public function __invoke(Request $request): mixed
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'module' => ['nullable', 'string', 'max:50'],
            'action' => ['nullable', 'string', 'max:50'],
            'user' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ], ['to.after_or_equal' => 'The "to" date must be the same as or after the "from" date.']);
        $filters['search'] = trim((string) ($filters['search'] ?? ''));

        $query = $this->filtered($filters);

        if ($request->query('format') === 'csv') {
            ActivityLog::record('exported', 'Activity Logs', 'Exported ' . (clone $query)->count() . ' activity log entries as CSV.');

            return $this->csv($query);
        }

        return view('superadmin.activity-logs', [
            'logs' => $query->paginate(25)->withQueryString(),
            'filters' => $filters,
            'modules' => ActivityLog::select('module')->distinct()->orderBy('module')->pluck('module'),
            'actions' => ActivityLog::select('action')->distinct()->orderBy('action')->pluck('action'),
            'users' => User::orderBy('first_name')->orderBy('last_name')->get(['id', 'first_name', 'last_name']),
            'stats' => [
                'today' => ActivityLog::whereDate('created_at', today())->count(),
                'logins' => ActivityLog::where('action', 'login')->whereDate('created_at', today())->count(),
                'failed' => ActivityLog::whereIn('action', ['login_failed', 'login_blocked', 'login_throttled'])->where('created_at', '>=', now()->subDay())->count(),
                'total' => ActivityLog::count(),
            ],
        ]);
    }

    private function filtered(array $filters): Builder
    {
        $search = $filters['search'];

        return ActivityLog::with('user.role')
            ->when($filters['module'] ?? null, fn ($q, $module) => $q->where('module', $module))
            ->when($filters['action'] ?? null, fn ($q, $action) => $q->where('action', $action))
            ->when($filters['user'] ?? null, fn ($q, $userId) => $q->where('user_id', $userId))
            ->when($filters['from'] ?? null, fn ($q, $from) => $q->where('created_at', '>=', Carbon::parse($from)->startOfDay()))
            ->when($filters['to'] ?? null, fn ($q, $to) => $q->where('created_at', '<=', Carbon::parse($to)->endOfDay()))
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('description', 'like', "%{$search}%")
                ->orWhere('ip_address', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($u) => $u->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%"))))
            ->latest('created_at')
            ->latest('id');
    }

    // The filtered entries as a CSV file (opens in Excel), newest first
    private function csv(Builder $query): StreamedResponse
    {
        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");   // lets Excel read ñ and ₱ correctly
            fputcsv($out, ['Date', 'Time', 'User', 'Role', 'Module', 'Action', 'Activity', 'IP Address']);
            $query->chunk(500, function ($logs) use ($out) {
                foreach ($logs as $log) {
                    fputcsv($out, [
                        $log->created_at?->format('Y-m-d'), $log->created_at?->format('g:i:s A'),
                        $log->user?->full_name ?? 'System / Guest', $log->user?->role?->name ?? '—',
                        $log->module, $log->action, $log->description, $log->ip_address ?? '—',
                    ]);
                }
            });
            fclose($out);
        }, 'activity-logs_' . now()->format('Y-m-d_His') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
