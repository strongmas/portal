<?php

namespace App\Http\Controllers;

use App\Models\InterestRegistration;
use App\Models\Property;
use App\Models\PropertyUnit;
use Illuminate\Http\Request;

class InterestRegistrationController extends Controller
{
    public function store(Request $request, Property $property)
    {
        $request->validate([
            'property_unit_id' => ['nullable', 'exists:property_units,id'],
        ]);

        $unitId = $request->property_unit_id ?: null;

        if ($unitId) {
            $unit = PropertyUnit::where('id', $unitId)
                ->where('property_id', $property->id)
                ->firstOrFail();
        }

        InterestRegistration::firstOrCreate([
            'user_id'          => $request->user()->id,
            'property_id'      => $property->id,
            'property_unit_id' => $unitId,
        ]);

        return back()->with('success', 'Your interest has been registered. Our team will be in touch.');
    }

    public function destroy(Request $request, InterestRegistration $interest)
    {
        abort_if($interest->user_id !== $request->user()->id, 403);
        $interest->delete();

        return back()->with('success', 'Interest removed.');
    }
}
