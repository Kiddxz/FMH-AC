<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Pet;
use App\Models\Waiver;
use App\Models\WaiverTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Digital waiver and consent forms for the clinic (capstone FR-REQ022 - FR-REQ024):
 *  - Staff (/staff/waivers): prepare a waiver, let the owner sign it at the clinic, cancel unsigned ones
 *  - Vet/Admin (/admin/waivers): review signed waivers
 *  - Super Admin (/superadmin/waivers): view only (decision P1)
 * Once signed, the text of a waiver is locked: there is no edit page at all.
 */
class WaiverController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $status = in_array($request->query('status'), Waiver::STATUSES, true) ? $request->query('status') : null;
        $templateId = $request->integer('template') ?: null;

        $waivers = Waiver::with(['customer', 'pet', 'template'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($templateId, fn ($q) => $q->where('waiver_template_id', $templateId))
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('reference', 'like', "%{$search}%")
                ->orWhereHas('pet', fn ($p) => $p->where('name', 'like', "%{$search}%"))
                ->orWhereHas('customer', fn ($c) => $c->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%"))))
            ->latest()
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('waivers.index', [
            'area' => $this->area($request),
            'waivers' => $waivers,
            'templates' => WaiverTemplate::orderBy('title')->get(),
            'counts' => Waiver::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'search' => $search,
            'status' => $status,
            'templateId' => $templateId,
        ]);
    }

    public function show(Request $request, Waiver $waiver): View
    {
        Gate::authorize('view', $waiver);
        $waiver->load(['customer', 'pet', 'template', 'preparer', 'reviewer']);

        return view('waivers.show', ['area' => $this->area($request), 'waiver' => $waiver]);
    }

    // A clean page to print or "Save as PDF" from the browser
    public function print(Request $request, Waiver $waiver): View
    {
        Gate::authorize('view', $waiver);
        $waiver->load(['customer', 'pet', 'preparer', 'reviewer']);

        return view('waivers.print', compact('waiver'));
    }

    // ---------- Staff: prepare, sign at the clinic, cancel ----------

    public function create(Request $request): View
    {
        return view('waivers.create', [
            'area' => 'staff',
            'templates' => WaiverTemplate::where('is_active', true)->orderBy('title')->get(),
            'pets' => Pet::with('customer')->where('status', 'active')->orderBy('name')->get(),
            'selectedPet' => $request->integer('pet') ?: null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'waiver_template_id' => ['required', Rule::exists('waiver_templates', 'id')->where('is_active', true)],
            'pet_id' => ['required', Rule::exists('pets', 'id')->where('status', 'active')->whereNull('deleted_at')],
        ], [], ['waiver_template_id' => 'form', 'pet_id' => 'pet']);

        $waiver = Waiver::prepare(WaiverTemplate::findOrFail($data['waiver_template_id']), Pet::findOrFail($data['pet_id']), $request->user()->id);
        ActivityLog::record('prepared', 'Waivers', 'Prepared ' . $waiver->reference . ' (' . $waiver->template->title . ') for ' . $waiver->pet->name . '.', $waiver);

        return redirect()->route('staff.waivers.show', $waiver)
            ->with('status', $waiver->reference . ' is ready. The owner can sign it here at the clinic or in the customer portal.');
    }

    public function signAtClinic(Request $request, Waiver $waiver): RedirectResponse
    {
        Gate::authorize('sign', $waiver);
        $data = $this->validateSignature($request);

        $this->markSigned($waiver, $data['signer_name'], 'clinic', $request->ip());
        ActivityLog::record('signed', 'Waivers', $waiver->reference . ' was signed at the clinic by ' . $data['signer_name'] . '.', $waiver);

        return back()->with('status', $waiver->reference . ' was signed. The text is now locked.');
    }

    public function destroy(Request $request, Waiver $waiver): RedirectResponse
    {
        Gate::authorize('delete', $waiver);
        $reference = $waiver->reference;
        $waiver->delete();
        ActivityLog::record('cancelled', 'Waivers', 'Cancelled unsigned waiver ' . $reference . '.');

        return redirect()->route('staff.waivers.index')->with('status', $reference . ' was cancelled.');
    }

    // ---------- Vet: review ----------

    public function review(Request $request, Waiver $waiver): RedirectResponse
    {
        Gate::authorize('review', $waiver);
        $data = $request->validate(['review_notes' => ['nullable', 'string', 'max:1000']]);

        $waiver->status = 'reviewed';
        $waiver->reviewed_by = $request->user()->id;
        $waiver->reviewed_at = now();
        $waiver->review_notes = $data['review_notes'] ?? null;
        $waiver->save();
        ActivityLog::record('reviewed', 'Waivers', 'Reviewed ' . $waiver->reference . '.', $waiver);

        return back()->with('status', $waiver->reference . ' was marked as reviewed.');
    }

    // ---------- shared with the customer portal ----------

    public static function validateSignature(Request $request): array
    {
        return $request->validate([
            'signer_name' => ['required', 'string', 'max:150'],
            'agree' => ['accepted'],
        ], [
            'signer_name.required' => 'Please type the full name of the person signing.',
            'agree.accepted' => 'Please tick the box to confirm that the form was read and agreed to.',
        ]);
    }

    public static function markSigned(Waiver $waiver, string $signer, string $via, ?string $ip): void
    {
        $waiver->status = 'signed';
        $waiver->signer_name = $signer;
        $waiver->signed_at = now();
        $waiver->signed_via = $via;
        $waiver->signer_ip = $ip;
        $waiver->save();
    }

    // 'staff', 'admin' or 'superadmin', taken from the address that was opened
    private function area(Request $request): string
    {
        return explode('.', (string) $request->route()->getName())[0];
    }
}
