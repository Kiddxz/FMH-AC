<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Customer (pet owner) directory for the front desk (capstone FR-REQ003 / FR-REQ010).
 * Registering NEW walk-in customers comes in Phase 11.
 */
class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));

        $customers = Customer::withCount('pets')
            ->when($search !== '', function ($query) use ($search) {
                foreach (preg_split('/\s+/', $search) as $word) {
                    $query->where(function ($q) use ($word) {
                        $q->where('first_name', 'like', "%{$word}%")
                            ->orWhere('last_name', 'like', "%{$word}%")
                            ->orWhere('contact_number', 'like', "%{$word}%")
                            ->orWhere('email', 'like', "%{$word}%");
                    });
                }
            })
            ->orderBy('last_name')->orderBy('first_name')
            ->paginate(15)
            ->withQueryString();

        return view('staff.customers.index', compact('customers', 'search'));
    }

    public function show(Customer $customer): View
    {
        $customer->load([
            'pets' => fn ($q) => $q->orderBy('name'),
            'appointments' => fn ($q) => $q->with(['pet', 'service'])->orderByDesc('appointment_date')->limit(10),
        ]);

        return view('staff.customers.show', compact('customer'));
    }

    public function edit(Customer $customer): View
    {
        return view('staff.customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $rules = [
            'firstname' => ['required', 'string', 'max:100'],
            'lastname' => ['required', 'string', 'max:100'],
            'mobile' => ['required', 'regex:/^09\d{9}$/'],
            'address' => ['nullable', 'string', 'max:255'],
        ];
        // The email of a customer WITH a portal account is their login name, so it is not changed here
        if (! $customer->user_id) {
            $rules['email'] = ['nullable', 'email', 'max:255'];
        }

        $data = $request->validate($rules, [
            'mobile.regex' => 'Enter an 11-digit mobile number that starts with 09 (e.g. 09171234567).',
        ]);

        DB::transaction(function () use ($customer, $data) {
            $customer->fill([
                'first_name' => $data['firstname'],
                'last_name' => $data['lastname'],
                'contact_number' => $data['mobile'],
                'address' => $data['address'] ?? null,
            ]);
            if (! $customer->user_id) {
                $customer->email = $data['email'] ?? null;
            }
            $customer->save();

            // Keep the portal account (if any) the same
            $customer->user?->update([
                'first_name' => $data['firstname'],
                'last_name' => $data['lastname'],
                'contact_number' => $data['mobile'],
            ]);
        });

        ActivityLog::record('updated', 'Customers', 'Updated customer ' . $customer->full_name . '.', $customer);

        return redirect()->route('staff.customers.show', $customer)->with('status', 'Customer details were saved.');
    }
}
