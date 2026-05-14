<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\ProjectUpdate;
use App\Models\UpdateComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProjectUpdateController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $propertyIds = $user->ownerships()->pluck('property_id')
            ->merge($user->memberOwnerships()->pluck('ownerships.property_id'))
            ->unique();

        $properties = Property::whereIn('id', $propertyIds)
            ->with([
                'projectUpdates' => fn($q) => $q->latest()->limit(5),
                'milestones'     => fn($q) => $q->orderBy('sort_order'),
                'ownerships'     => fn($q) => $q->where('user_id', $user->id),
            ])
            ->get();

        // Keyed by property_id for the view to look up quickly
        $ownershipsMap = $user->ownerships()->with('property')->get()
            ->merge($user->memberOwnerships()->with('property')->get())
            ->unique('id')
            ->keyBy('property_id');

        return view('project-updates.index', compact('properties', 'ownershipsMap'));
    }

    public function show(ProjectUpdate $projectUpdate)
    {
        // Verify user has access to this property
        $user = auth()->user();
        $propertyIds = $user->ownerships()->pluck('property_id')
            ->merge($user->memberOwnerships()->pluck('ownerships.property_id'))
            ->unique();

        abort_unless($propertyIds->contains($projectUpdate->property_id), 403);

        $projectUpdate->load(['documents', 'comments.user', 'poster', 'property.milestones']);

        return view('project-updates.show', compact('projectUpdate'));
    }

    public function comment(Request $request, ProjectUpdate $projectUpdate)
    {
        $user = auth()->user();
        $propertyIds = $user->ownerships()->pluck('property_id')
            ->merge($user->memberOwnerships()->pluck('ownerships.property_id'))
            ->unique();

        abort_unless($propertyIds->contains($projectUpdate->property_id), 403);

        // Viewer-only members cannot post comments
        $ownershipForProperty = \App\Models\Ownership::where('property_id', $projectUpdate->property_id)
            ->whereIn('id', function ($q) use ($user) {
                $q->select('ownership_id')->from('ownership_members')->where('user_id', $user->id);
            })->first();

        if ($ownershipForProperty && $user->isViewerOnly($ownershipForProperty)) {
            abort(403, 'Viewer access is read-only. You cannot post comments.');
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:2000'],
        ]);

        UpdateComment::create([
            'project_update_id' => $projectUpdate->id,
            'user_id'           => $user->id,
            'body'              => $validated['body'],
        ]);

        return back()->with('success', 'Comment posted.');
    }

    public function downloadDocument(string $document)
    {
        $doc = \App\Models\ProjectUpdateDocument::findOrFail($document);

        // Verify access
        $user = auth()->user();
        $propertyIds = $user->ownerships()->pluck('property_id')
            ->merge($user->memberOwnerships()->pluck('ownerships.property_id'))
            ->unique();

        $update = $doc->update()->with('property')->first();
        abort_unless($propertyIds->contains($update->property_id), 403);

        abort_unless(Storage::disk('private')->exists($doc->file_path), 404);

        return Storage::disk('private')->download($doc->file_path, $doc->original_name);
    }
}
