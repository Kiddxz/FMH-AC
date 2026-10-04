<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\InventoryBatch;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Supplier;
use App\Services\InventoryAlerts;
use App\Services\InventoryStock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Inventory monitoring (capstone FR-REQ014 - FR-REQ017), shared by Staff and Vet/Admin.
 *  - Staff (inventory.manage): items, stock-in, count corrections, disposing expired stock (decision P3)
 *  - Staff and Vet (inventory.record_usage): record what was used
 *  - Everyone with inventory.view: list, alerts, usage log
 * Items are never deleted (they have history); they are deactivated instead.
 */
class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $category = in_array($request->query('category'), InventoryItem::CATEGORIES, true) ? $request->query('category') : null;
        $status = in_array($request->query('status'), ['available', 'low', 'out', 'expiring', 'inactive'], true) ? $request->query('status') : null;
        $stock = self::stockSql();

        $items = InventoryItem::query()
            ->select('inventory_items.*')
            ->selectRaw("{$stock} as stock_total")
            ->withMin(['batches as next_expiry' => fn ($q) => $q->where('quantity', '>', 0)], 'expiration_date')
            ->when($status !== 'inactive', fn ($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($category, fn ($q) => $q->where('category', $category))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%")))
            ->when($status === 'available', fn ($q) => $q->whereRaw("{$stock} > reorder_level"))
            ->when($status === 'low', fn ($q) => $q->whereRaw("{$stock} > 0 and {$stock} <= reorder_level"))
            ->when($status === 'out', fn ($q) => $q->whereRaw("{$stock} <= 0"))
            ->when($status === 'expiring', fn ($q) => $q->whereHas('batches', fn ($b) => $b->where('quantity', '>', 0)
                ->whereDate('expiration_date', '<=', today()->addDays(InventoryAlerts::expiringDays()))))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $active = InventoryItem::where('is_active', true);
        $stats = [
            'total' => (clone $active)->count(),
            'available' => (clone $active)->whereRaw("{$stock} > reorder_level")->count(),
            'low' => (clone $active)->whereRaw("{$stock} > 0 and {$stock} <= reorder_level")->count(),
            'out' => (clone $active)->whereRaw("{$stock} <= 0")->count(),
        ];

        return view('inventory.index', [
            'area' => $this->area($request),
            'items' => $items,
            'stats' => $stats,
            'alerts' => InventoryAlerts::summary(),
            'search' => $search,
            'category' => $category,
            'status' => $status,
        ]);
    }

    public function show(Request $request, InventoryItem $item): View
    {
        $item->load('supplier');

        return view('inventory.show', [
            'area' => $this->area($request),
            'item' => $item,
            'batches' => $item->batches()->with('supplier')
                ->orderByRaw('quantity = 0')->orderByRaw('expiration_date IS NULL')->orderBy('expiration_date')->get(),
            'movements' => $item->movements()->with(['user', 'batch'])->latest('created_at')->latest('id')->paginate(15),
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function log(Request $request): View
    {
        $type = in_array($request->query('type'), ['stock_in', 'usage', 'sale', 'adjustment', 'expired'], true) ? $request->query('type') : null;
        $search = trim((string) $request->query('search'));
        $from = $request->date('from');
        $to = $request->date('to');

        $movements = InventoryMovement::with(['item', 'user', 'batch'])
            ->when($type, fn ($q) => $q->where('type', $type))
            ->when($search !== '', fn ($q) => $q->whereHas('item', fn ($i) => $i->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%")))
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
            ->latest('created_at')->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('inventory.log', [
            'area' => $this->area($request),
            'movements' => $movements,
            'type' => $type,
            'search' => $search,
            'from' => $from?->format('Y-m-d'),
            'to' => $to?->format('Y-m-d'),
        ]);
    }

    // ---------- Items (Staff: inventory.manage) ----------

    public function create(Request $request): View
    {
        return view('inventory.form', [
            'area' => $this->area($request),
            'item' => new InventoryItem(['is_active' => true, 'reorder_level' => 10]),
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $item = InventoryItem::create($this->validateItem($request));
        ActivityLog::record('created', 'Inventory', 'Added inventory item ' . $item->name . ' (' . $item->sku . ').', $item);

        return redirect()->route($this->area($request) . '.inventory.show', $item)
            ->with('status', $item->name . ' was added. Use "Stock In" to record the first delivery.');
    }

    public function edit(Request $request, InventoryItem $item): View
    {
        return view('inventory.form', [
            'area' => $this->area($request),
            'item' => $item,
            'suppliers' => Supplier::where('is_active', true)->orWhere('id', $item->supplier_id)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, InventoryItem $item): RedirectResponse
    {
        $item->update($this->validateItem($request, $item));
        ActivityLog::record('updated', 'Inventory', 'Updated inventory item ' . $item->name . ' (' . $item->sku . ').', $item);

        return redirect()->route($this->area($request) . '.inventory.show', $item)->with('status', 'Item details saved.');
    }

    // ---------- Stock changes ----------

    public function stockIn(Request $request, InventoryItem $item, InventoryStock $stock): RedirectResponse
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'batch_number' => ['nullable', 'string', 'max:100'],
            'expiration_date' => ['nullable', 'date', 'after:today'],
            'received_date' => ['required', 'date', 'before_or_equal:today'],
            'unit_cost' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'supplier_id' => ['nullable', Rule::exists('suppliers', 'id')],
            'remarks' => ['nullable', 'string', 'max:255'],
        ], [
            'expiration_date.after' => 'The expiration date must be after today. Expired stock cannot be received.',
        ]);

        $stock->stockIn($item, $data, $request->user()->id);
        ActivityLog::record('stock_in', 'Inventory', "Received {$data['quantity']} {$item->unit} of {$item->name}.", $item);

        return back()->with('status', "Stock in saved: +{$data['quantity']} {$item->unit}.");
    }

    public function recordUsage(Request $request, InventoryItem $item, InventoryStock $stock): RedirectResponse
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'remarks' => ['required', 'string', 'max:255'],
        ], [
            'remarks.required' => 'Please say what it was used for (e.g. "Max - consultation").',
        ]);

        $stock->deduct($item, (int) $data['quantity'], 'usage', $data['remarks'], $request->user()->id);
        ActivityLog::record('usage', 'Inventory', "Used {$data['quantity']} {$item->unit} of {$item->name}: {$data['remarks']}", $item);

        return back()->with('status', "Usage recorded: -{$data['quantity']} {$item->unit}.");
    }

    public function correctCount(Request $request, InventoryBatch $batch, InventoryStock $stock): RedirectResponse
    {
        $data = $request->validate([
            'counted' => ['required', 'integer', 'min:0', 'max:100000'],
            'reason' => ['required', 'string', 'max:255'],
        ], [
            'reason.required' => 'Please give the reason for the correction (e.g. "monthly count", "damaged").',
        ]);

        $difference = $stock->correctCount($batch, (int) $data['counted'], $data['reason'], $request->user()->id);
        if ($difference === 0) {
            return back()->with('status', 'The count matches the system. Nothing was changed.');
        }

        ActivityLog::record('adjustment', 'Inventory', 'Corrected ' . $batch->item->name . ' batch ' . ($batch->batch_number ?: '#' . $batch->id)
            . ' by ' . sprintf('%+d', $difference) . ': ' . $data['reason'], $batch->item);

        return back()->with('status', 'Count corrected (' . sprintf('%+d', $difference) . ' ' . $batch->item->unit . ').');
    }

    public function disposeExpired(Request $request, InventoryBatch $batch, InventoryStock $stock): RedirectResponse
    {
        $quantity = $stock->disposeExpired($batch, $request->user()->id);
        ActivityLog::record('expired', 'Inventory', "Disposed {$quantity} expired {$batch->item->unit} of {$batch->item->name}.", $batch->item);

        return back()->with('status', "{$quantity} expired {$batch->item->unit} disposed.");
    }

    // ---------- helpers ----------

    private function validateItem(Request $request, ?InventoryItem $item = null): array
    {
        $request->merge(['sku' => strtoupper(trim((string) $request->input('sku')))]);

        $data = $request->validate([
            'sku' => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9-]+$/', Rule::unique('inventory_items', 'sku')->ignore($item)],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(InventoryItem::CATEGORIES)],
            'unit' => ['required', 'string', 'max:50'],
            'reorder_level' => ['required', 'integer', 'min:0', 'max:100000'],
            'selling_price' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'supplier_id' => ['nullable', Rule::exists('suppliers', 'id')],
            'is_active' => ['boolean'],
        ], [
            'sku.regex' => 'Use letters, numbers and dashes only (e.g. MED-AMOX).',
            'sku.unique' => 'This item code is already used.',
        ], [
            'sku' => 'item code', 'reorder_level' => 'low-stock level', 'supplier_id' => 'supplier',
        ]);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    // Stock that can still be used = the batches that are not expired (expired stock is not counted)
    public static function stockSql(): string
    {
        $today = today()->toDateString();   // a date made by the system, never user input

        return "(select coalesce(sum(b.quantity), 0) from inventory_batches b where b.inventory_item_id = inventory_items.id"
            . " and (b.expiration_date is null or b.expiration_date >= '{$today}'))";
    }

    // 'staff' or 'admin', taken from the address that was opened
    private function area(Request $request): string
    {
        return str_starts_with((string) $request->route()->getName(), 'admin.') ? 'admin' : 'staff';
    }
}
