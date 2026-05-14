<?php

namespace App\Http\Controllers;

use App\Models\DocumentAccessLog;
use App\Models\PropertyDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PropertyDocumentController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $ownerships = $user->ownerships()->with('property', 'unit')->get();
        $ownershipIds = $ownerships->pluck('id');

        $documents = PropertyDocument::whereIn('ownership_id', $ownershipIds)
            ->with('property')
            ->latest()
            ->get()
            ->groupBy('ownership_id');

        return view('documents.index', compact('documents', 'ownerships'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'ownership_id'  => ['required', 'exists:ownerships,id'],
            'title'         => ['required', 'string', 'max:200'],
            'document_type' => ['required', 'in:contract,deed,survey,approval,report,receipt,offer_letter,other'],
            'file'          => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        // Ensure the user owns this ownership (primary owner only)
        $ownership = $user->ownerships()->findOrFail($validated['ownership_id']);

        $file = $request->file('file');
        $path = $file->store('property-documents/' . $ownership->property_id, 'private');

        $existing = PropertyDocument::where('ownership_id', $ownership->id)
            ->where('document_type', $validated['document_type'])
            ->where('title', $validated['title'])
            ->max('version');

        PropertyDocument::create([
            'property_id'      => $ownership->property_id,
            'ownership_id'     => $ownership->id,
            'title'            => $validated['title'],
            'file_path'        => $path,
            'original_name'    => $file->getClientOriginalName(),
            'document_type'    => $validated['document_type'],
            'version'          => $existing ? $existing + 1 : 1,
            'is_buyer_visible' => true,
            'uploaded_by'      => $user->id,
            'status'           => 'pending_review',
        ]);

        return back()->with('success', 'Document uploaded successfully. Our team will review it shortly.');
    }

    public function download(PropertyDocument $document)
    {
        $this->authorize('download', $document);

        DocumentAccessLog::create([
            'property_document_id' => $document->id,
            'user_id'              => auth()->id(),
            'ip_address'           => request()->ip(),
            'accessed_at'          => now(),
        ]);

        if (!Storage::disk('private')->exists($document->file_path)) {
            abort(404, 'File not found.');
        }

        return Storage::disk('private')->download($document->file_path, $document->original_name);
    }

    public function destroy(PropertyDocument $document)
    {
        // Only the uploader can delete their own document
        if ($document->uploaded_by !== auth()->id()) {
            abort(403);
        }

        Storage::disk('private')->delete($document->file_path);
        $document->delete();

        return back()->with('success', 'Document deleted.');
    }
}
