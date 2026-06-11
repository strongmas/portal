<?php

namespace App\Http\Controllers\Admin;

use App\Events\ProjectUpdatePosted;
use App\Http\Controllers\Controller;
use App\Models\ProjectUpdateDocument;
use App\Models\ProjectUpdateImage;
use App\Models\ProjectUpdate;
use App\Models\Property;
use App\Models\UpdateComment;
use App\Services\ImageService;
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
            'video'                => ['nullable', 'file', 'mimetypes:video/mp4,video/webm,video/quicktime', 'max:30720'],
            'estimated_completion' => ['nullable', 'date'],
            'images'               => ['nullable', 'array', 'max:10'],
            'images.*'             => ['image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'captions'             => ['nullable', 'array'],
            'captions.*'           => ['nullable', 'string', 'max:200'],
        ]);

        $update = ProjectUpdate::create([
            'property_id'          => $property->id,
            'title'                => $validated['title'],
            'body'                 => $validated['body'],
            'progress_percentage'  => $validated['progress_percentage'],
            'stage'                => $validated['stage'] ?? null,
            'youtube_url'          => $validated['youtube_url'] ?? null,
            'posted_by'            => auth()->id(),
        ]);

        if ($validated['progress_percentage'] >= 100) {
            $property->update(['project_status' => 'completed', 'is_off_plan' => false]);
        }

        if ($request->hasFile('video')) {
            $path = 'project-update-videos/' . $update->id . '/' . uniqid() . '.mp4';
            Storage::disk('public')->putFileAs(
                dirname($path),
                $request->file('video'),
                basename($path)
            );
            $update->update([
                'video_path' => $path,
                'video_expires_at' => now()->addDays(14),
            ]);
        }

        if ($request->hasFile('images')) {
            $order = 0;
            foreach ($request->file('images') as $i => $file) {
                $path = ImageService::store(
                    $file,
                    'project-update-images/' . $update->id,
                    'public',
                    1920,
                    82
                );
                ProjectUpdateImage::create([
                    'project_update_id' => $update->id,
                    'path'              => $path,
                    'caption'           => $request->input("captions.{$i}") ?: null,
                    'sort_order'        => $order++,
                ]);
            }
        }

        ProjectUpdatePosted::dispatch($update);

        return redirect()->route('admin.project-updates.show', [$property, $update])
            ->with('success', 'Update posted and buyers notified.');
    }

    public function show(Property $property, ProjectUpdate $projectUpdate)
    {
        $projectUpdate->load(['images', 'documents', 'comments.user', 'poster']);
        $milestones = $property->milestones()->orderBy('sort_order')->get();
        return view('admin.project-updates.show', compact('property', 'projectUpdate', 'milestones'));
    }

    public function storeComment(Request $request, Property $property, ProjectUpdate $projectUpdate)
    {
        $validated = $request->validate(['body' => ['required', 'string', 'min:2', 'max:2000']]);

        UpdateComment::create([
            'project_update_id' => $projectUpdate->id,
            'user_id'           => auth()->id(),
            'body'              => $validated['body'],
        ]);

        return back()->with('success', 'Reply posted.');
    }

    public function destroyImage(ProjectUpdateImage $image)
    {
        Storage::disk('public')->delete($image->path);
        $image->delete();
        return back()->with('success', 'Image removed.');
    }

    // Kept for backward-compatibility with existing document data
    public function destroyDocument(ProjectUpdateDocument $document)
    {
        Storage::disk('private')->delete($document->file_path);
        $document->delete();
        return back()->with('success', 'Document removed.');
    }

    public function destroyVideo(ProjectUpdate $projectUpdate)
    {
        if ($projectUpdate->video_path) {
            Storage::disk('public')->delete($projectUpdate->video_path);
            $projectUpdate->update(['video_path' => null, 'video_expires_at' => null]);
        }
        return back()->with('success', 'Video removed.');
    }
}
