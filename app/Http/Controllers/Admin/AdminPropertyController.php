<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminPropertyController extends Controller
{
    public function index()
    {
        $properties = Property::withCount('ownerships')
            ->with(['units', 'ownerships.payments'])
            ->orderBy('development_name')
            ->get()
            ->map(function ($p) {
                $p->total_collected_kobo = $p->ownerships->sum(fn($o) => $o->payments->sum('amount_kobo'));
                return $p;
            });

        return view('admin.properties.index', compact('properties'));
    }

    public function show(Property $property)
    {
        $property->load([
            'units.ownerships',
            'units.images',
            'ownerships.user',
            'ownerships.unit',
            'ownerships.payments',
            'ownerships.instalments',
            'images',
            'documents',
        ]);

        return view('admin.properties.show', compact('property'));
    }

    public function create()
    {
        return view('admin.properties.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'             => ['required', 'string', 'max:50', 'unique:properties,code'],
            'development_name' => ['required', 'string', 'max:255'],
            'address'          => ['required', 'string', 'max:500'],
        ]);

        $validated['code'] = strtoupper($validated['code']);

        $property = Property::create($validated);

        if ($request->input('next') === 'link') {
            return redirect()->route('admin.ownerships.create', ['property_id' => $property->id])
                ->with('success', 'Property created. Now link a buyer.');
        }

        return redirect()->route('admin.properties.show', $property)
            ->with('success', 'Development created. Add unit types from the property page.');
    }

    public function edit(Property $property)
    {
        return view('admin.properties.edit', compact('property'));
    }

    public function update(Request $request, Property $property)
    {
        $validated = $request->validate([
            'code'             => ['required', 'string', 'max:50', 'unique:properties,code,' . $property->id],
            'development_name' => ['required', 'string', 'max:255'],
            'address'          => ['required', 'string', 'max:500'],
            'description'      => ['nullable', 'string', 'max:2000'],
            'lat'              => ['nullable', 'numeric', 'between:-90,90'],
            'lng'              => ['nullable', 'numeric', 'between:-180,180'],
            'is_off_plan'      => ['boolean'],
            'project_status'   => ['nullable', 'in:planning,foundation,construction,finishing,completed'],
        ]);

        $validated['code']        = strtoupper($validated['code']);
        $validated['is_off_plan'] = $request->boolean('is_off_plan');
        $property->update($validated);

        return redirect()->route('admin.properties.show', $property)
            ->with('success', 'Property updated.');
    }

    public function destroy(Property $property)
    {
        if ($property->ownerships()->exists()) {
            return back()->with('error', 'This property cannot be deleted because buyers are currently linked to it.');
        }

        // Delete associated image files from disk
        foreach ($property->images as $image) {
            Storage::disk('public')->delete($image->path);
        }

        // Delete associated document files from disk
        foreach ($property->documents as $doc) {
            Storage::disk('local')->delete($doc->file_path);
        }

        $property->delete();

        return redirect()->route('admin.properties.index')
            ->with('success', 'Property deleted successfully.');
    }

    // ── Unit management ─────────────────────────────────────────────────────

    public function storeUnit(Request $request, Property $property)
    {
        $validated = $request->validate([
            'bedrooms'        => ['nullable', 'integer', 'min:0', 'max:20'],
            'unit_type'       => ['required', 'in:apartment,terrace,maisonette,duplex,penthouse,bungalow,detached,studio'],
            'list_price_kobo' => ['required', 'string'],
            'total_units'     => ['required', 'integer', 'min:1', 'max:9999'],
            'notes'           => ['nullable', 'string', 'max:255'],
        ]);

        $property->units()->create([
            'bedrooms'        => $validated['bedrooms'] ?: null,
            'unit_type'       => $validated['unit_type'],
            'list_price_kobo' => kobo($validated['list_price_kobo']),
            'total_units'     => $validated['total_units'],
            'notes'           => $validated['notes'] ?? null,
        ]);

        return redirect()->route('admin.properties.show', $property)
            ->with('success', 'Unit type added.');
    }

    public function updateUnit(Request $request, Property $property, PropertyUnit $unit)
    {
        abort_if($unit->property_id !== $property->id, 403);

        $validated = $request->validate([
            'bedrooms'        => ['nullable', 'integer', 'min:0', 'max:20'],
            'unit_type'       => ['required', 'in:apartment,terrace,maisonette,duplex,penthouse,bungalow,detached,studio'],
            'list_price_kobo' => ['required', 'string'],
            'total_units'     => ['required', 'integer', 'min:1', 'max:9999'],
            'notes'           => ['nullable', 'string', 'max:255'],
        ]);

        $unit->update([
            'bedrooms'        => $validated['bedrooms'] ?: null,
            'unit_type'       => $validated['unit_type'],
            'list_price_kobo' => kobo($validated['list_price_kobo']),
            'total_units'     => $validated['total_units'],
            'notes'           => $validated['notes'] ?? null,
        ]);

        return redirect()->route('admin.properties.show', $property)
            ->with('success', 'Unit type updated.');
    }

    public function destroyUnit(Property $property, PropertyUnit $unit)
    {
        abort_if($unit->property_id !== $property->id, 403);
        $unit->delete();

        return redirect()->route('admin.properties.show', $property)
            ->with('success', 'Unit type removed.');
    }
}
