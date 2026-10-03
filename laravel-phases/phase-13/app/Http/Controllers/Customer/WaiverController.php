<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Clinic\WaiverController as ClinicWaiverController;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Waiver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * "My Waivers" in the customer portal: read, sign online and print (decision P5).
 * Only the waivers of the owner's OWN pets are shown (WaiverPolicy).
 */
class WaiverController extends Controller
{
    public function index(Request $request): View
    {
        $customer = $request->user()->customer;
        abort_unless($customer, 403, 'Your account has no pet owner profile. Please contact the clinic.');

        $waivers = $customer->waivers()->with(['pet', 'template'])
            ->orderByRaw("status = 'pending' desc")
            ->latest()
            ->get();

        return view('customer.waivers.index', compact('waivers'));
    }

    public function show(Waiver $waiver): View
    {
        Gate::authorize('view', $waiver);
        $waiver->load(['pet', 'template']);

        return view('customer.waivers.show', compact('waiver'));
    }

    public function sign(Request $request, Waiver $waiver): RedirectResponse
    {
        Gate::authorize('sign', $waiver);
        $data = ClinicWaiverController::validateSignature($request);

        ClinicWaiverController::markSigned($waiver, $data['signer_name'], 'portal', $request->ip());
        ActivityLog::record('signed', 'Waivers', $waiver->reference . ' was signed in the portal by ' . $data['signer_name'] . '.', $waiver);

        return back()->with('status', 'Thank you! ' . $waiver->reference . ' was signed.');
    }
}
