<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\DocumentFlaggedMail;
use App\Models\PropertyDocument;
use App\Notifications\DocumentFlaggedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class AdminPropertyDocumentController extends Controller
{
    public function index()
    {
        $documents = PropertyDocument::with(['property', 'uploader'])->latest()->paginate(25);
        return view('admin.documents.index', compact('documents'));
    }

    public function verify(PropertyDocument $propertyDocument)
    {
        $propertyDocument->update([
            'status'      => 'verified',
            'flag_reason' => null,
        ]);

        return back()->with('success', 'Document marked as verified.');
    }

    public function flag(Request $request, PropertyDocument $propertyDocument)
    {
        $validated = $request->validate([
            'flag_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $propertyDocument->update([
            'status'      => 'flagged',
            'flag_reason' => $validated['flag_reason'] ?? null,
        ]);

        $uploader = $propertyDocument->uploader;
        $uploader->notify(new DocumentFlaggedNotification($propertyDocument));
        Mail::to($uploader->email)->send(new DocumentFlaggedMail($propertyDocument));

        return back()->with('success', 'Document flagged and buyer notified.');
    }

    public function unflag(PropertyDocument $propertyDocument)
    {
        $propertyDocument->update([
            'status'      => 'pending_review',
            'flag_reason' => null,
        ]);

        return back()->with('success', 'Flag cleared. Document is back under review.');
    }

    public function adminDownload(PropertyDocument $propertyDocument)
    {
        $path = Storage::disk('local')->path($propertyDocument->file_path);

        if (!file_exists($path)) {
            abort(404, 'File not found.');
        }

        return response()->download($path, $propertyDocument->original_name);
    }

    public function destroy(PropertyDocument $propertyDocument)
    {
        Storage::disk('local')->delete($propertyDocument->file_path);
        $propertyDocument->delete();
        return back()->with('success', 'Document deleted.');
    }
}
