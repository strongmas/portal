<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminPropertyImageController extends Controller
{
    public function store(Request $request, Property $property)
    {
        $request->validate([
            'images'   => ['required', 'array', 'max:10'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ]);

        $nextOrder = $property->images()->max('sort_order') + 1;

        foreach ($request->file('images') as $file) {
            $path = ImageService::store($file, 'property-images/' . $property->id, 'public', 1920, 82);
            $property->images()->create([
                'path'       => $path,
                'sort_order' => $nextOrder++,
                'is_cover'   => false,
            ]);
        }

        return back()->with('success', 'Images uploaded.');
    }

    public function setCover(Property $property, PropertyImage $image)
    {
        $property->images()->update(['is_cover' => false]);
        $image->update(['is_cover' => true]);
        return back()->with('success', 'Cover image updated.');
    }

    public function reorder(Request $request, Property $property)
    {
        $request->validate(['order' => ['required', 'array']]);

        foreach ($request->order as $index => $imageId) {
            $property->images()->where('id', $imageId)->update(['sort_order' => $index]);
        }

        return response()->json(['success' => true]);
    }

    public function destroy(Property $property, PropertyImage $image)
    {
        Storage::disk('public')->delete($image->path);
        $image->delete();
        return back()->with('success', 'Image deleted.');
    }
}
