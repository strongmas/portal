<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SalesRep;
use App\Models\User;
use Illuminate\Http\Request;

class AdminSalesRepController extends Controller
{
    public function index()
    {
        $reps = SalesRep::withCount('buyers')->latest()->paginate(20);
        return view('admin.sales-reps.index', compact('reps'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:sales_reps,email'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        SalesRep::create($validated);

        return back()->with('success', 'Sales rep added.');
    }

    public function update(Request $request, SalesRep $salesRep)
    {
        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:100'],
            'email'     => ['required', 'email', 'unique:sales_reps,email,' . $salesRep->id],
            'phone'     => ['nullable', 'string', 'max:30'],
            'is_active' => ['boolean'],
        ]);

        $salesRep->update($validated);

        return back()->with('success', 'Sales rep updated.');
    }

    public function destroy(SalesRep $salesRep)
    {
        $salesRep->buyers()->update(['sales_rep_id' => null]);
        $salesRep->delete();

        return back()->with('success', 'Sales rep deleted.');
    }

    public function assignToBuyer(Request $request, User $buyer)
    {
        $validated = $request->validate([
            'sales_rep_id' => ['nullable', 'exists:sales_reps,id'],
        ]);

        $buyer->update(['sales_rep_id' => $validated['sales_rep_id']]);

        return back()->with('success', 'Sales rep updated for buyer.');
    }
}
