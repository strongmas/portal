<?php

namespace App\Http\Controllers;

use App\Mail\StaffAlertMail;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class SupportController extends Controller
{
    public function index(Request $request)
    {
        $user     = $request->user();
        $canChat  = $user->hasOwnerAccess();

        $messages   = $user->supportMessages()->orderBy('created_at')->get();
        $tickets    = $user->supportTickets()->with('property')->latest()->get();

        // Ticket dropdown: owned + co-owned ownerships only
        $ownerships = $user->ownerships()->with(['property', 'unit'])->get()
            ->merge(
                $user->memberOwnerships()->wherePivot('role', 'owner')->with(['property', 'unit'])->get()
            )->unique('id');

        return view('support', compact('messages', 'tickets', 'ownerships', 'canChat'));
    }

    public function messages(Request $request)
    {
        $messages = $request->user()->supportMessages()
            ->orderBy('created_at')
            ->get()
            ->map(fn($m) => [
                'id'     => $m->id,
                'sender' => $m->sender,
                'body'   => $m->body,
                'time'   => $m->created_at->format('H:i'),
            ]);

        return response()->json($messages);
    }

    public function sendMessage(Request $request)
    {
        abort_unless($request->user()->hasOwnerAccess(), 403, 'Only property owners and co-owners can use support chat.');

        $validated = $request->validate([
            'body'       => ['required_without:attachment', 'nullable', 'string', 'max:2000'],
            'attachment' => ['nullable', 'file', 'max:5120', 'mimes:png,jpg,jpeg,pdf'],
        ]);

        $data = [
            'user_id' => $request->user()->id,
            'sender'  => 'buyer',
            'body'    => $validated['body'] ?? '',
        ];

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store('support-attachments/' . $request->user()->id, 'private');
            $data['attachment_path'] = $path;
            $data['attachment_name'] = $file->getClientOriginalName();
            $data['attachment_size'] = $file->getSize();
            $data['attachment_mime'] = $file->getMimeType();
        }

        $msg = SupportMessage::create($data);

        // Notify staff with 'support' permission
        $buyer = $request->user();
        User::withAdminPermission('support')->each(function ($staff) use ($buyer) {
            Mail::to($staff->email)->send(new StaffAlertMail(
                alertSubject: "New chat message from {$buyer->name}",
                heading: "New support message",
                body: "{$buyer->name} sent a new chat message.\nCheck the support chat to respond.",
                actionUrl: route('admin.support.index'),
                actionLabel: 'View Chat',
            ));
        });

        return response()->json([
            'id'             => $msg->id,
            'sender'         => 'buyer',
            'body'           => $msg->body,
            'time'           => $msg->created_at->format('H:i'),
            'has_attachment' => $msg->has_attachment,
            'attachment_name'=> $msg->attachment_name,
        ]);
    }

    public function downloadAttachment(SupportMessage $message)
    {
        $user = auth()->user();

        // Buyer can only download from their own chat; admin can download any
        if (!$user->is_admin && $message->user_id !== $user->id) {
            abort(403);
        }

        if (!$message->has_attachment) {
            abort(404);
        }

        $path = $message->attachment_path ?? 'support-attachments/' . $message->user_id . '/' . $message->attachment_name;

        if (!Storage::disk('private')->exists($path)) {
            abort(404);
        }

        return Storage::disk('private')->download($path, $message->attachment_name);
    }

    public function storeTicket(Request $request)
    {
        abort_unless($request->user()->hasOwnerAccess(), 403, 'Only property owners and co-owners can submit support tickets.');

        $validated = $request->validate([
            'category'    => ['required', 'string', 'max:100'],
            'property_id' => ['nullable', 'exists:properties,id'],
            'body'        => ['required', 'string', 'max:3000'],
        ]);

        $count = SupportTicket::count();
        $ticket = SupportTicket::create([
            'user_id'     => $request->user()->id,
            'property_id' => $validated['property_id'] ?? null,
            'case_no'     => 'CASE-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT),
            'category'    => $validated['category'],
            'body'        => $validated['body'],
        ]);

        // Notify staff with 'tickets' permission
        $buyer = $request->user();
        User::withAdminPermission('tickets')->each(function ($staff) use ($buyer, $ticket) {
            Mail::to($staff->email)->send(new StaffAlertMail(
                alertSubject: "New support ticket [{$ticket->case_no}] from {$buyer->name}",
                heading: "New support ticket: {$ticket->case_no}",
                body: "Category: {$ticket->category}\nFrom: {$buyer->name}\n\n{$ticket->body}",
                actionUrl: route('admin.tickets.show', $ticket),
                actionLabel: 'View Ticket',
            ));
        });

        if ($request->wantsJson()) {
            return response()->json(['case_no' => $ticket->case_no]);
        }

        return back()->with('success', 'Complaint submitted. We\'ll respond within 48 hours.');
    }
}
