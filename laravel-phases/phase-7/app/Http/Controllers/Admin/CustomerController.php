<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Read-only customer (pet owner) directory for the Veterinarian/Admin.
 * Customers are edited by the Staff; accounts are managed by the Super Admin (decision P6).
 */
class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $type = $request->query('type');

        $customers = Customer::withCount('pets')
            ->when($search !== '', function ($query) use ($search) {
                foreach (preg_split('/\s+/', $search) as $word) {
                    $query->where(fn ($q) => $q
                        ->where('first_name', 'like', "%{$word}%")
                        ->orWhere('last_name', 'like', "%{$word}%")
                        ->orWhere('contact_number', 'like', "%{$word}%")
                        ->orWhere('email', 'like', "%{$word}%"));
                }
            })
            ->when($type === 'portal', fn ($q) => $q->whereNotNull('user_id'))
            ->when($type === 'walk_in', fn ($q) => $q->whereNull('user_id'))
            ->orderBy('last_name')->orderBy('first_name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.customers.index', compact('customers', 'search', 'type'));
    }

    public function show(Customer $customer): View
    {
        $customer->load([
            'pets' => fn ($q) => $q->orderBy('name'),
            'appointments' => fn ($q) => $q->with(['pet', 'service'])->orderByDesc('appointment_date')->limit(10),
        ]);

        return view('admin.customers.show', compact('customer'));
    }
}
