<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\Payment;
use App\Models\Service;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * "View POS System" of the use case diagrams (Figures 5.1 - 5.3), for the Vet/Admin (/admin/pos) and the
 * Super Admin (/superadmin/pos): VIEW ONLY. It shows what the cashier's POS sells (services and products with
 * their prices and stock) and today's POS sales. Only the Staff/Cashier records bills and payments (decision P2).
 */
class PosOverviewController extends Controller
{
    public function __invoke(Request $request): View
    {
        $area = $request->routeIs('superadmin.*') ? 'superadmin' : 'admin';
        $todayBills = Transaction::with(['customer', 'pet', 'payments'])->whereDate('created_at', today())->latest()->latest('id')->get();
        $todayPayments = Payment::whereDate('paid_at', today())->whereHas('transaction', fn ($q) => $q->where('status', '!=', 'void'))->get();
        $active = $todayBills->where('status', '!=', 'void');

        return view('pos.overview', [
            'area' => $area,
            'services' => Service::where('is_active', true)->orderBy('name')->get(),
            'products' => InventoryItem::where('is_active', true)->whereNotNull('selling_price')
                ->withSum(['batches as stock' => fn ($q) => $q->where(fn ($b) => $b->whereNull('expiration_date')->orWhereDate('expiration_date', '>=', today()))], 'quantity')
                ->orderBy('name')->get(),
            'todayBills' => $todayBills,
            'stats' => [
                'bills' => $active->count(),
                'billed' => (float) $active->sum('total'),
                'collected' => (float) $todayPayments->sum('amount'),
                'balance' => (float) $active->sum('balance'),
            ],
            'byMethod' => collect(Payment::LABELS)->map(fn ($label, $method) => [
                'label' => $label,
                'count' => $todayPayments->where('method', $method)->count(),
                'amount' => (float) $todayPayments->where('method', $method)->sum('amount'),
            ])->filter(fn ($row) => $row['count'] > 0),
        ]);
    }
}
