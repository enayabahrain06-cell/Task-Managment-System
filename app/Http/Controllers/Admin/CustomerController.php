<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Setting;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::withCount(['projects', 'tasks'])->with('creator:id,name');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('company', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%");
            });
        }

        $customers = $query->orderBy('name')->paginate(20)->withQueryString();

        return view('admin.customers.index', compact('customers'));
    }

    public function create()
    {
        return view('admin.customers.create');
    }

    public function store(Request $request)
    {
        $maxMb = (int) Setting::get('max_upload_mb', 5);

        $request->validate([
            'name'    => 'required|string|max:255',
            'company' => 'nullable|string|max:255',
            'email'   => 'nullable|email|max:255',
            'phone'   => 'nullable|string|max:50',
            'notes'   => 'nullable|string',
            'logo'    => 'nullable|image|mimes:jpg,jpeg,png,webp,svg|max:' . ($maxMb * 1024),
        ]);

        $data = [
            'name'       => $request->name,
            'company'    => $request->company,
            'email'      => $request->email,
            'phone'      => $request->phone,
            'notes'      => $request->notes,
            'created_by' => auth()->id(),
        ];

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('customer-logos', 'public');
        }

        $customer = Customer::create($data);

        AuditLogger::log(
            'customer.created',
            $customer,
            'Customer "' . $customer->name . '" created',
            ['customer_id' => $customer->id]
        );

        return redirect()->route('admin.customers.index')->with('success', 'Customer created successfully.');
    }

    public function show(Customer $customer)
    {
        $customer->load([
            'projects' => fn($q) => $q->withCount('tasks')->orderBy('created_at', 'desc'),
            'tasks.assignee',
            'tasks.project:id,name',
        ]);

        return view('admin.customers.show', compact('customer'));
    }

    public function edit(Customer $customer)
    {
        return view('admin.customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $maxMb = (int) Setting::get('max_upload_mb', 5);

        $request->validate([
            'name'        => 'required|string|max:255',
            'company'     => 'nullable|string|max:255',
            'email'       => 'nullable|email|max:255',
            'phone'       => 'nullable|string|max:50',
            'notes'       => 'nullable|string',
            'logo'        => 'nullable|image|mimes:jpg,jpeg,png,webp,svg|max:' . ($maxMb * 1024),
            'remove_logo' => 'nullable|boolean',
        ]);

        $data = $request->only('name', 'company', 'email', 'phone', 'notes');

        if ($request->hasFile('logo')) {
            if ($customer->logo) {
                Storage::disk('public')->delete($customer->logo);
            }
            $data['logo'] = $request->file('logo')->store('customer-logos', 'public');
        } elseif ($request->boolean('remove_logo')) {
            if ($customer->logo) {
                Storage::disk('public')->delete($customer->logo);
            }
            $data['logo'] = null;
        }

        $customer->update($data);

        AuditLogger::log(
            'customer.updated',
            $customer,
            'Customer "' . $customer->name . '" updated',
            ['customer_id' => $customer->id]
        );

        return redirect()->route('admin.customers.index')->with('success', 'Customer updated.');
    }

    public function destroy(Customer $customer)
    {
        if ($customer->logo) {
            Storage::disk('public')->delete($customer->logo);
        }

        $name = $customer->name;
        AuditLogger::log(
            'customer.deleted',
            $customer,
            'Customer "' . $name . '" deleted',
            ['customer_id' => $customer->id]
        );
        $customer->delete();

        return redirect()->route('admin.customers.index')->with('success', "Customer \"{$name}\" deleted.");
    }
}
