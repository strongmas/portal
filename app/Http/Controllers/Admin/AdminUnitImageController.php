<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyUnit;
use App\Models\UnitTypeImage;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminUnitImageController extends Controller
{
    public function store(Request $request, Property $property, PropertyUnit $unit)
    {
        abort_if((int) $unit->property_id !== (int) $property->id, 403);

        $request->validate([
            'images'      => ['required', 'array', 'max:10'],
            'images.*'    => ['image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'room_type'   => ['required', 'in:exterior,bedroom,kitchen,bathroom,living_area'],
        ]);

        $nextOrder = $unit->images()->where('room_type', $request->room_type)->max('sort_order') + 1;

        foreach ($request->file('images') as $file) {
            $path = ImageService::store($file, "unit-images/{$property->id}/{$unit->id}", 'public', 1920, 82);
            $unit->images()->create([
                'room_type'  => $request->room_type,
                'path'       => $path,
                'sort_order' => $nextOrder++,
            ]);
        }

        return back()->with('success', 'Images uploaded.');
    }

    public function destroy(Property $property, PropertyUnit $unit, UnitTypeImage $image)
    {
        abort_if((int) $unit->property_id !== (int) $property->id, 403);
        abort_if((int) $image->property_unit_id !== (int) $unit->id, 403);

        Storage::disk('public')->delete($image->path);
        $image->delete();

        return back()->with('success', 'Image deleted.');
    }
}
