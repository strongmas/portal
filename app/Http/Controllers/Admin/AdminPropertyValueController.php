<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyUnit;
use App\Models\PropertyValueHistory;
use Illuminate\Http\Request;

class AdminPropertyValueController extends Controller
{
    public function index()
    {
        $properties = Property::with('units.valueHistories')->orderBy('development_name')->get();
        return view('admin.property-values.index', compact('properties'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'property_unit_id' => ['required', 'exists:property_units,id'],
            'value'            => ['required', 'string'],
            'recorded_at'      => ['required', 'date'],
            'notes'            => ['nullable', 'string', 'max:500'],
        ]);

        PropertyValueHistory::create([
            'property_unit_id' => $validated['property_unit_id'],
            'value_kobo'       => kobo($validated['value']),
            'notes'            => $validated['notes'] ?? null,
            'recorded_by'      => auth()->id(),
            'recorded_at'      => $validated['recorded_at'],
        ]);

        return back()->with('success', 'Value entry recorded.');
    }

    public function destroy(PropertyValueHistory $propertyValueHistory)
    {
        $propertyValueHistory->delete();
        return back()->with('success', 'Entry removed.');
    }
}
