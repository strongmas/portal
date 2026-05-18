<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\DocumentFlaggedMail;
use App\Models\Property;
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

    public function store(Request $request, Property $property)
    {
        $validated = $request->validate([
            'title'            => ['required', 'string', 'max:200'],
            'document_type'    => ['required', 'in:contract,deed,survey,approval,report,receipt,offer_letter,other'],
            'is_buyer_visible' => ['boolean'],
            'file'             => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        $file = $request->file('file');
        $path = $file->store('property-documents/' . $property->id . '/admin', 'private');

        $existing = PropertyDocument::where('property_id', $property->id)
            ->whereNull('ownership_id')
            ->where('document_type', $validated['document_type'])
            ->where('title', $validated['title'])
            ->max('version');

        PropertyDocument::create([
            'property_id'      => $property->id,
            'ownership_id'     => null,
            'title'            => $validated['title'],
            'file_path'        => $path,
            'original_name'    => $file->getClientOriginalName(),
            'document_type'    => $validated['document_type'],
            'version'          => $existing ? $existing + 1 : 1,
            'is_buyer_visible' => $request->boolean('is_buyer_visible', true),
            'uploaded_by'      => auth()->id(),
            'status'           => 'verified',
        ]);

        return back()->with('success', 'Document uploaded and published to buyers.');
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
        if (!Storage::disk('private')->exists($propertyDocument->file_path)) {
            abort(404, 'File not found.');
        }

        return Storage::disk('private')->download($propertyDocument->file_path, $propertyDocument->original_name);
    }

    public function destroy(PropertyDocument $propertyDocument)
    {
        Storage::disk('private')->delete($propertyDocument->file_path);
        $propertyDocument->delete();
        return back()->with('success', 'Document deleted.');
    }
}
