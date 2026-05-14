<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminSupportController extends Controller
{
    public function index()
    {
        $buyers = User::where('is_admin', false)
            ->whereHas('supportMessages')
            ->with(['supportMessages' => fn($q) => $q->latest()->take(1)])
            ->latest()
            ->get();

        $openCount = SupportTicket::whereIn('status', ['open', 'in_progress'])->count();

        return view('admin.support.index', compact('buyers', 'openCount'));
    }

    public function reply(Request $request, User $user)
    {
        $validated = $request->validate([
            'body'       => ['required_without:attachment', 'nullable', 'string', 'max:2000'],
            'attachment' => ['nullable', 'file', 'max:5120', 'mimes:png,jpg,jpeg,pdf'],
        ]);

        $data = [
            'user_id' => $user->id,
            'sender'  => 'agent',
            'body'    => $validated['body'] ?? '',
        ];

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store('support-attachments/' . $user->id, 'private');
            $data['attachment_path'] = $path;
            $data['attachment_name'] = $file->getClientOriginalName();
            $data['attachment_size'] = $file->getSize();
            $data['attachment_mime'] = $file->getMimeType();
        }

        $msg = SupportMessage::create($data);

        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', 'Reply sent.');
    }

    public function tickets()
    {
        $tickets = SupportTicket::with(['user', 'property'])
            ->latest()
            ->paginate(20);

        $counts = [
            'open'        => SupportTicket::where('status', 'open')->count(),
            'in_progress' => SupportTicket::where('status', 'in_progress')->count(),
            'resolved'    => SupportTicket::where('status', 'resolved')->count(),
            'closed'      => SupportTicket::where('status', 'closed')->count(),
        ];

        return view('admin.tickets.index', compact('tickets', 'counts'));
    }

    public function ticketShow(SupportTicket $ticket)
    {
        $ticket->load(['user', 'property']);

        $messages = SupportMessage::where('user_id', $ticket->user_id)
            ->orderBy('created_at')
            ->get();

        return view('admin.tickets.show', compact('ticket', 'messages'));
    }

    public function updateTicket(Request $request, SupportTicket $ticket)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:open,in_progress,resolved,closed'],
        ]);

        $ticket->update($validated);
        return back()->with('success', 'Ticket status updated.');
    }

    public function ticketReply(Request $request, SupportTicket $ticket)
    {
        $validated = $request->validate(['body' => ['required', 'string', 'max:2000']]);

        SupportMessage::create([
            'user_id' => $ticket->user_id,
            'sender'  => 'agent',
            'body'    => $validated['body'],
        ]);

        if ($ticket->status === 'open') {
            $ticket->update(['status' => 'in_progress']);
        }

        return back()->with('success', 'Reply sent to buyer.');
    }
}
