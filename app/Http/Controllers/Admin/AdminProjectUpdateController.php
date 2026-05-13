<?php

namespace App\Http\Controllers\Admin;

use App\Events\ProjectUpdatePosted;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ProjectUpdate;
use App\Models\ProjectUpdateDocument;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminProjectUpdateController extends Controller
{
    public function index()
    {
        $properties = Property::where('is_off_plan', true)
            ->with(['projectUpdates' => fn($q) => $q->latest()->limit(1)])
            ->orderBy('development_name')
            ->get();

        return view('admin.project-updates.index', compact('properties'));
    }

    public function create(Property $property)
    {
        return view('admin.project-updates.create', compact('property'));
    }

    public function store(Request $request, Property $property)
    {
        $validated = $request->validate([
            'title'                => ['required', 'string', 'max:200'],
            'body'                 => ['required', 'string'],
            'progress_percentage'  => ['required', 'integer', 'min:0', 'max:100'],
            'stage'                => ['nullable', 'string', 'max:100'],
            'youtube_url'          => ['nullable', 'url', 'max:500'],
            'estimated_completion' => ['nullable', 'date'],
            'documents'            => ['nullable', 'array'],
            'documents.*'          => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'doc_titles'           => ['nullable', 'array'],
            'doc_titles.*'         => ['string', 'max:200'],
        ]);

        $update = ProjectUpdate::create([
            'property_id'          => $property->id,
            'title'                => $validated['title'],
            'body'                 => $validated['body'],
            'progress_percentage'  => $validated['progress_percentage'],
            'stage'                => $validated['stage'] ?? null,
            'youtube_url'          => $validated['youtube_url'] ?? null,
            'estimated_completion' => $validated['estimated_completion'] ?? null,
            'posted_by'            => auth()->id(),
        ]);

        // Also update property project_status if progress is 100
        if ($validated['progress_percentage'] >= 100) {
            $property->update(['project_status' => 'completed', 'is_off_plan' => false]);
        }

        // Upload documents
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $i => $file) {
                $path = $file->store('project-update-documents/' . $update->id, 'local');
                ProjectUpdateDocument::create([
                    'project_update_id' => $update->id,
                    'title'             => $request->input("doc_titles.{$i}", $file->getClientOriginalName()),
                    'file_path'         => $path,
                    'original_name'     => $file->getClientOriginalName(),
                    'uploaded_by'       => auth()->id(),
                ]);
            }
        }

        ProjectUpdatePosted::dispatch($update);

        return redirect()->route('admin.project-updates.show', [$property, $update])
            ->with('success', 'Update posted and buyers notified.');
    }

    public function show(Property $property, ProjectUpdate $projectUpdate)
    {
        $projectUpdate->load(['documents', 'comments.user', 'poster']);
        $milestones = $property->milestones()->orderBy('sort_order')->get();
        return view('admin.project-updates.show', compact('property', 'projectUpdate', 'milestones'));
    }

    public function destroyDocument(ProjectUpdateDocument $document)
    {
        Storage::disk('local')->delete($document->file_path);
        $document->delete();
        return back()->with('success', 'Document removed.');
    }
}
