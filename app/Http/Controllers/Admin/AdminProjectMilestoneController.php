<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProjectMilestone;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminProjectMilestoneController extends Controller
{
    public function index(Property $property)
    {
        $milestones = $property->milestones()->orderBy('sort_order')->get();
        return view('admin.milestones.index', compact('property', 'milestones'));
    }

    public function store(Request $request, Property $property)
    {
        $validated = $request->validate([
            'title'        => ['required', 'string', 'max:200'],
            'description'  => ['nullable', 'string'],
            'planned_date' => ['required', 'date'],
            'sort_order'   => ['nullable', 'integer'],
            'image'        => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:4096'],
        ]);

        $data = array_merge($validated, [
            'sort_order' => $validated['sort_order'] ?? $property->milestones()->max('sort_order') + 1,
        ]);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('milestone-images/' . $property->id, 'public');
        }

        $property->milestones()->create($data);

        return back()->with('success', 'Milestone added.');
    }

    public function update(Request $request, Property $property, ProjectMilestone $milestone)
    {
        $validated = $request->validate([
            'title'        => ['required', 'string', 'max:200'],
            'description'  => ['nullable', 'string'],
            'planned_date' => ['required', 'date'],
            'actual_date'  => ['nullable', 'date'],
            'status'       => ['required', 'in:pending,in_progress,completed,delayed'],
            'image'        => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:4096'],
        ]);

        if ($request->hasFile('image')) {
            if ($milestone->image_path) {
                Storage::disk('public')->delete($milestone->image_path);
            }
            $validated['image_path'] = $request->file('image')->store('milestone-images/' . $property->id, 'public');
        }

        $milestone->update($validated);

        return back()->with('success', 'Milestone updated.');
    }

    public function destroy(Property $property, ProjectMilestone $milestone)
    {
        $milestone->delete();
        return back()->with('success', 'Milestone deleted.');
    }

    public function reorder(Request $request, Property $property)
    {
        $request->validate(['order' => ['required', 'array']]);

        foreach ($request->order as $index => $id) {
            $property->milestones()->where('id', $id)->update(['sort_order' => $index]);
        }

        return response()->json(['success' => true]);
    }
}
