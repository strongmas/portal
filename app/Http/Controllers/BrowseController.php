<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\PropertyDocument;
use Illuminate\Http\Request;

class BrowseController extends Controller
{
    public function index(Request $request)
    {
        $all = Property::with([
            'units.images',
            'units.ownerships',
            'images',
        ])->get()->map(function ($property) {
            $property->units->each(function ($unit) {
                $unit->available = max(0, $unit->total_units - $unit->ownerships->count());
            });
            $property->total_available = $property->units->sum('available');
            return $property;
        });

        $available = $all->where('total_available', '>', 0)->values();
        $comingSoon = $all->where('total_available', 0)->values();

        $myInterests = $request->user()
            ->interestRegistrations()
            ->get(['id', 'property_unit_id'])
            ->keyBy('property_unit_id');

        return view('browse.index', compact('available', 'comingSoon', 'myInterests'));
    }

    public function show(Request $request, Property $property)
    {
        $property->load([
            'units.images',
            'units.ownerships',
            'images',
        ]);

        $property->units->each(function ($unit) {
            $soldCount       = $unit->ownerships->count();
            $unit->available = max(0, $unit->total_units - $soldCount);
        });

        // keyed by property_unit_id => interest id, for delete links
        $myInterests = $request->user()
            ->interestRegistrations()
            ->where('property_id', $property->id)
            ->get(['id', 'property_unit_id'])
            ->keyBy('property_unit_id');

        $propertyDocuments = PropertyDocument::where('property_id', $property->id)
            ->whereNull('ownership_id')
            ->where('is_buyer_visible', true)
            ->latest()
            ->get();

        return view('browse.show', compact('property', 'myInterests', 'propertyDocuments'));
    }
}
