<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\MedicalRecord;
use App\Models\PatientVisit;
use App\Models\Payment;
use App\Models\Pet;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Vaccination;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The clinic reports (capstone FR-REQ021, FR-REQ027, Fig 6.2). Every number is counted from the database.
 *
 * build() returns the same shape for every report, so one page, one print view and one CSV export
 * can show any of them:
 *   cards  : [ [icon, label, value], ... ]          the boxes at the top
 *   tables : [ [title, headers, rows], ... ]         summary tables
 *   detail : [title, headers, rows]                  the full list (also the CSV file)
 *
 * Super Admin also uses these reports, so no diagnosis or medical notes are shown (decision P1).
 */
class ClinicReports
{
    public const TYPES = [
        'appointments' => 'Appointment Report',
        'flow' => 'Patient / Customer Flow Report',
        'daily' => 'Daily Patient Count',
        'inventory' => 'Inventory Report',
        'records' => 'Pet Record Summary',
        'sales' => 'Sales & Transaction Report',
    ];

    // The "status" filter of each report (empty = no status filter)
    public const STATUSES = [
        'appointments' => ['pending' => 'Pending', 'confirmed' => 'Confirmed', 'completed' => 'Completed', 'cancelled' => 'Cancelled'],
        'flow' => ['waiting' => 'Waiting', 'ongoing' => 'Ongoing', 'completed' => 'Completed', 'cancelled' => 'Cancelled'],
        'daily' => [],
        'inventory' => ['available' => 'Available', 'low' => 'Low Stock', 'out' => 'Out of Stock'],
        'records' => ['consultation' => 'Consultation', 'treatment' => 'Treatment', 'vaccination' => 'Vaccination', 'grooming' => 'Grooming'],
        'sales' => ['unpaid' => 'Unpaid', 'partial' => 'Partial', 'paid' => 'Paid', 'void' => 'Void'],
    ];

    public function build(string $type, Carbon $from, Carbon $to, ?string $status): array
    {
        $report = $this->{$type}($from->copy()->startOfDay(), $to->copy()->endOfDay(), $status);

        return $report + ['title' => self::TYPES[$type]];
    }

    // ---------- Appointment Report ----------
    private function appointments(Carbon $from, Carbon $to, ?string $status): array
    {
        $all = Appointment::with(['customer', 'pet', 'service'])
            ->whereDate('appointment_date', '>=', $from)->whereDate('appointment_date', '<=', $to)
            ->orderBy('appointment_date')->orderBy('appointment_time')
            ->get();
        $list = $status ? $all->where('status', $status) : $all;

        $byService = $all->flatMap(fn ($a) => $a->serviceList()->map(fn ($s) => ['name' => $s->name, 'status' => $a->status]))->groupBy('name')->sortKeys()
            ->map(fn ($g, $name) => [$name, $g->count(), $g->where('status', 'completed')->count(),
                $g->whereIn('status', ['pending', 'confirmed'])->count(), $g->where('status', 'cancelled')->count()])
            ->values()->all();

        return [
            'cards' => [
                ['📅', 'Total Appointments', $all->count()],
                ['✅', 'Completed', $all->where('status', 'completed')->count()],
                ['⏳', 'Pending / Confirmed', $all->whereIn('status', ['pending', 'confirmed'])->count()],
                ['❌', 'Cancelled', $all->where('status', 'cancelled')->count()],
            ],
            'tables' => [
                ['Appointments per Service', ['Service', 'Total', 'Completed', 'Pending / Confirmed', 'Cancelled'], $byService],
            ],
            'detail' => ['Appointment List', ['Date', 'Time', 'Appointment ID', 'Pet', 'Owner', 'Service', 'Status'],
                $list->map(fn ($a) => [
                    $a->appointment_date->format('Y-m-d'), Carbon::parse($a->appointment_time)->format('g:i A'), $a->reference,
                    $a->pet?->name, $a->customer?->full_name, $a->service_names, ucfirst($a->status),
                ])->values()->all()],
        ];
    }

    // ---------- Patient / Customer Flow Report ----------
    private function flow(Carbon $from, Carbon $to, ?string $status): array
    {
        $all = PatientVisit::with(['customer', 'pet', 'service'])
            ->whereDate('visit_date', '>=', $from)->whereDate('visit_date', '<=', $to)
            ->orderBy('visit_date')->orderBy('queue_number')
            ->get();
        $list = $status ? $all->where('status', $status) : $all;

        // Minutes from check-in to start (waiting) and from start to done (service)
        $waits = $all->filter(fn ($v) => $v->started_at)->map(fn ($v) => $v->checked_in_at->diffInMinutes($v->started_at));
        $services = $all->filter(fn ($v) => $v->started_at && $v->completed_at)->map(fn ($v) => $v->started_at->diffInMinutes($v->completed_at));

        $byPurpose = $all->groupBy(fn ($v) => ucfirst($v->service?->purpose ?? 'other'))->sortKeys()
            ->map(fn ($g, $purpose) => [$purpose, $g->count(), $g->where('visit_type', 'walk_in')->count(),
                $g->where('visit_type', 'appointment')->count(), $g->where('status', 'completed')->count(), $g->where('status', 'cancelled')->count()])
            ->values()->all();

        return [
            'cards' => [
                ['🩺', 'Total Visits', $all->count()],
                ['🚶', 'Walk-ins', $all->where('visit_type', 'walk_in')->count()],
                ['⏱️', 'Average Wait', $this->minutes($waits)],
                ['✅', 'Completed', $all->where('status', 'completed')->count()],
            ],
            'tables' => [
                ['Visits by Service Type', ['Service Type', 'Visits', 'Walk-in', 'Appointment', 'Completed', 'Cancelled'], $byPurpose],
                ['Status of Visits', ['Waiting', 'Ongoing', 'Completed', 'Cancelled', 'Average Wait', 'Average Service Time'], [[
                    $all->where('status', 'waiting')->count(), $all->where('status', 'ongoing')->count(),
                    $all->where('status', 'completed')->count(), $all->where('status', 'cancelled')->count(),
                    $this->minutes($waits), $this->minutes($services),
                ]]],
            ],
            'detail' => ['Visit List', ['Date', 'Queue', 'Pet', 'Owner', 'Service', 'Type', 'Checked In', 'Started', 'Done', 'Status'],
                $list->map(fn ($v) => [
                    $v->visit_date->format('Y-m-d'), '#' . $v->queue_number, $v->pet?->name, $v->customer?->full_name, $v->service?->name,
                    $v->visit_type === 'walk_in' ? 'Walk-in' : 'Appointment', $v->checked_in_at?->format('g:i A'),
                    $v->started_at?->format('g:i A') ?? '—', $v->completed_at?->format('g:i A') ?? '—', ucfirst($v->status),
                ])->values()->all()],
        ];
    }

    // ---------- Daily Patient Count ----------
    private function daily(Carbon $from, Carbon $to, ?string $status): array
    {
        $visits = PatientVisit::whereDate('visit_date', '>=', $from)->whereDate('visit_date', '<=', $to)->get()
            ->groupBy(fn ($v) => $v->visit_date->format('Y-m-d'));
        $newCustomers = Customer::whereBetween('created_at', [$from, $to])->get()->groupBy(fn ($c) => $c->created_at->format('Y-m-d'));
        $newPets = Pet::whereBetween('created_at', [$from, $to])->get()->groupBy(fn ($p) => $p->created_at->format('Y-m-d'));

        $rows = [];
        for ($day = $from->copy(); $day->lte($to); $day->addDay()) {
            $key = $day->format('Y-m-d');
            $v = $visits->get($key, collect());
            $rows[] = [$key, $day->format('l'), $v->count(), $v->pluck('pet_id')->unique()->count(), $v->where('visit_type', 'walk_in')->count(),
                $v->where('visit_type', 'appointment')->count(), $v->where('status', 'completed')->count(),
                $newCustomers->get($key, collect())->count(), $newPets->get($key, collect())->count()];
        }

        $totalVisits = array_sum(array_column($rows, 2));
        $days = max(count($rows), 1);
        $busiest = collect($rows)->sortByDesc(2)->first();

        return [
            'cards' => [
                ['🩺', 'Total Visits', $totalVisits],
                ['📈', 'Average per Day', number_format($totalVisits / $days, 1)],
                ['🏆', 'Busiest Day', $busiest && $busiest[2] > 0 ? Carbon::parse($busiest[0])->format('M j') . ' (' . $busiest[2] . ')' : '—'],
                ['🆕', 'New Customers', $newCustomers->flatten()->count()],
            ],
            'tables' => [],
            'detail' => ['Patients per Day', ['Date', 'Day', 'Visits', 'Pets Seen', 'Walk-ins', 'Appointments', 'Completed', 'New Customers', 'New Pets'], $rows],
        ];
    }

    // ---------- Inventory Report (stock as of today + movements in the date range) ----------
    private function inventory(Carbon $from, Carbon $to, ?string $status): array
    {
        $items = InventoryItem::where('is_active', true)
            ->withSum(['batches as usable_stock' => fn ($q) => $q->where(fn ($b) => $b->whereNull('expiration_date')->orWhereDate('expiration_date', '>=', today()))], 'quantity')
            ->orderBy('category')->orderBy('name')->get()
            ->map(function ($item) {
                $stock = (int) $item->usable_stock;
                $item->report_status = $stock <= 0 ? 'out' : ($stock <= $item->reorder_level ? 'low' : 'available');

                return $item;
            });
        $list = $status ? $items->where('report_status', $status) : $items;

        $moves = InventoryMovement::whereBetween('created_at', [$from, $to])->get()->groupBy('inventory_item_id');
        $sum = fn ($itemId, $type) => (int) abs($moves->get($itemId, collect())->where('type', $type)->sum('quantity'));
        $labels = ['available' => 'Available', 'low' => 'Low Stock', 'out' => 'Out of Stock'];

        return [
            'cards' => [
                ['📦', 'Active Items', $items->count()],
                ['✅', 'Available', $items->where('report_status', 'available')->count()],
                ['⚠️', 'Low Stock', $items->where('report_status', 'low')->count()],
                ['❌', 'Out of Stock', $items->where('report_status', 'out')->count()],
            ],
            'tables' => [
                ['Stock Movements in the Period', ['Received', 'Used', 'Sold', 'Adjusted (+/−)', 'Expired / Disposed'], [[
                    $moves->flatten()->where('type', 'stock_in')->sum('quantity'),
                    abs($moves->flatten()->where('type', 'usage')->sum('quantity')),
                    abs($moves->flatten()->where('type', 'sale')->sum('quantity')),
                    $moves->flatten()->where('type', 'adjustment')->sum('quantity'),
                    abs($moves->flatten()->where('type', 'expired')->sum('quantity')),
                ]]],
            ],
            'detail' => ['Items', ['Code', 'Item', 'Category', 'Stock (not expired)', 'Unit', 'Reorder Level', 'Status', 'Received', 'Used', 'Sold', 'Expired'],
                $list->map(fn ($i) => [
                    $i->sku, $i->name, ucfirst($i->category), (int) $i->usable_stock, $i->unit, $i->reorder_level, $labels[$i->report_status],
                    $sum($i->id, 'stock_in'), $sum($i->id, 'usage'), $sum($i->id, 'sale'), $sum($i->id, 'expired'),
                ])->values()->all()],
        ];
    }

    // ---------- Pet Record Summary (counts only: no diagnosis or notes) ----------
    private function records(Carbon $from, Carbon $to, ?string $status): array
    {
        $all = MedicalRecord::with(['pet.customer', 'veterinarian'])
            ->whereDate('record_date', '>=', $from)->whereDate('record_date', '<=', $to)
            ->orderBy('record_date')->get();
        $list = $status ? $all->where('record_type', $status) : $all;
        $vaccinations = Vaccination::whereDate('date_given', '>=', $from)->whereDate('date_given', '<=', $to)->get();
        $pets = Pet::where('status', 'active')->get();

        return [
            'cards' => [
                ['📋', 'Records Written', $all->count()],
                ['🐾', 'Pets Treated', $all->pluck('pet_id')->unique()->count()],
                ['💉', 'Vaccinations Given', $vaccinations->count()],
                ['🆕', 'New Pets Registered', Pet::whereBetween('created_at', [$from, $to])->count()],
            ],
            'tables' => [
                ['Records by Type', ['Type', 'Records'], collect(MedicalRecord::TYPES)->map(fn ($t) => [ucfirst($t), $all->where('record_type', $t)->count()])->all()],
                ['Registered Pets by Species (all active pets)', ['Species', 'Pets', 'Percentage'], $pets->groupBy('species')->sortKeys()
                    ->map(fn ($g, $s) => [ucfirst($s), $g->count(), round($g->count() / max($pets->count(), 1) * 100) . '%'])->values()->all()],
            ],
            'detail' => ['Record List', ['Date', 'Pet', 'Species', 'Owner', 'Type', 'Veterinarian'],
                $list->map(fn ($r) => [
                    $r->record_date->format('Y-m-d'), $r->pet?->name, ucfirst($r->pet?->species ?? ''), $r->pet?->customer?->full_name,
                    ucfirst($r->record_type), $r->veterinarian ? 'Dr. ' . $r->veterinarian->full_name : '—',
                ])->values()->all()],
        ];
    }

    // ---------- Sales & Transaction Report (void bills are never counted as sales) ----------
    private function sales(Carbon $from, Carbon $to, ?string $status): array
    {
        $bills = Transaction::with(['customer', 'pet', 'payments'])->whereBetween('created_at', [$from, $to])->orderBy('created_at')->get();
        $list = $status ? $bills->where('status', $status) : $bills;
        $valid = $bills->where('status', '!=', 'void');

        // Money received in the period (payments of bills that were not voided)
        $payments = Payment::whereBetween('paid_at', [$from, $to])->whereHas('transaction', fn ($q) => $q->where('status', '!=', 'void'))->get();
        $methods = \App\Models\Payment::LABELS;

        $lines = TransactionItem::whereIn('transaction_id', $valid->pluck('id'))->get();
        $byItem = $lines->groupBy('description')->sortKeys()
            ->map(fn ($g, $name) => [$name, ucfirst($g->first()->item_type), $g->sum('quantity'), $this->money($g->sum('line_total'))])
            ->values()->all();

        return [
            'cards' => [
                ['🧾', 'Bills (not void)', $valid->count()],
                ['💰', 'Total Sales', '₱' . $this->money($valid->sum('total'))],
                ['💵', 'Collected', '₱' . $this->money($payments->sum('amount'))],
                ['⏳', 'Unpaid Balance', '₱' . $this->money($valid->sum('balance'))],
            ],
            'tables' => [
                ['Collected by Payment Method', ['Method', 'Payments', 'Amount (₱)'], collect($methods)
                    ->map(fn ($label, $m) => [$label, $payments->where('method', $m)->count(), $this->money($payments->where('method', $m)->sum('amount'))])->values()->all()],
                ['Sales by Service / Product', ['Item', 'Type', 'Quantity', 'Amount (₱)'], $byItem],
            ],
            'detail' => ['Bill List', ['Date', 'Receipt No.', 'Customer', 'Pet', 'Type', 'Total (₱)', 'Paid (₱)', 'Balance (₱)', 'Status'],
                $list->map(fn ($t) => [
                    $t->created_at->format('Y-m-d g:i A'), $t->receipt_number, $t->customer?->full_name ?? 'Walk-in buyer', $t->pet?->name ?? '—',
                    Transaction::TYPES[$t->transaction_type], $this->money($t->total), $this->money($t->amount_paid), $this->money($t->balance), ucfirst($t->status),
                ])->values()->all()],
        ];
    }

    private function minutes(Collection $values): string
    {
        return $values->isEmpty() ? '—' : round($values->avg()) . ' min';
    }

    private function money(mixed $amount): string
    {
        return number_format((float) $amount, 2);
    }
}
