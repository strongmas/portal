<x-layouts.admin title="Tickets">

<div class="page-header mb-6">
    <div>
        <div class="eyebrow" style="margin-bottom:6px;">Support</div>
        <h1 style="font-size:28px;font-weight:700;color:var(--fg);letter-spacing:-0.02em;">Complaint tickets</h1>
        <p style="font-size:13px;color:var(--fg-mute);margin-top:2px;">{{ $tickets->total() }} total tickets</p>
    </div>
    <div class="flex items-center gap-3">
        <span class="chip chip-danger" style="font-size:11px;">{{ $counts['open'] }} open</span>
        <span class="chip chip-pending" style="font-size:11px;">{{ $counts['in_progress'] }} in progress</span>
        <span class="chip chip-ok" style="font-size:11px;">{{ $counts['resolved'] }} resolved</span>
        <span class="chip chip-dark" style="font-size:11px;">{{ $counts['closed'] }} closed</span>
    </div>
</div>

<form method="GET" class="flex items-center gap-3 mb-5">
    <select name="status" class="input-base" style="max-width:200px;">
        <option value="">All statuses</option>
        <option value="open"        {{ request('status') === 'open'        ? 'selected' : '' }}>Open</option>
        <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In progress</option>
        <option value="resolved"    {{ request('status') === 'resolved'    ? 'selected' : '' }}>Resolved</option>
        <option value="closed"      {{ request('status') === 'closed'      ? 'selected' : '' }}>Closed</option>
    </select>
    <button type="submit" class="btn-ghost" style="font-size:13px;">Filter</button>
    @if(request('status'))
        <a href="{{ route('admin.tickets.index') }}" style="font-size:13px;color:var(--fg-dim);text-decoration:underline;text-underline-offset:3px;">Clear</a>
    @endif
</form>

<div class="card" style="padding:0;overflow:hidden;">
    <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
            <tr style="border-bottom:1px solid var(--line);">
                <th style="padding:12px 20px;text-align:left;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Case #</th>
                <th style="padding:12px 20px;text-align:left;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Buyer</th>
                <th style="padding:12px 20px;text-align:left;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Category</th>
                <th style="padding:12px 20px;text-align:left;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Property</th>
                <th style="padding:12px 20px;text-align:left;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Submitted</th>
                <th style="padding:12px 20px;text-align:center;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Status</th>
                <th style="padding:12px 20px;text-align:right;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tickets as $ticket)
                <tr style="border-top:1px solid var(--line);transition:background var(--dur-fast);"
                    onmouseover="this.style.background='var(--bg-raised)'" onmouseout="this.style.background='transparent'">
                    <td style="padding:14px 20px;font-family:'Geist Mono',monospace;font-size:11px;color:var(--fg-dim);">{{ $ticket->case_no }}</td>
                    <td style="padding:14px 20px;">
                        <a href="{{ route('admin.buyers.show', $ticket->user) }}" style="font-weight:500;color:var(--fg);text-decoration:none;" onmouseover="this.style.color='var(--tan)'" onmouseout="this.style.color='var(--fg)'">{{ $ticket->user->name }}</a>
                    </td>
                    <td style="padding:14px 20px;color:var(--fg-mute);">{{ $ticket->category }}</td>
                    <td style="padding:14px 20px;font-size:12px;color:var(--fg-dim);">{{ $ticket->property->full_name ?? '—' }}</td>
                    <td style="padding:14px 20px;font-size:12px;color:var(--fg-dim);">{{ $ticket->created_at->format('d M Y') }}</td>
                    <td style="padding:14px 20px;text-align:center;">
                        @php
                            $chipClass = match($ticket->status) {
                                'open'        => 'chip-danger',
                                'in_progress' => 'chip-pending',
                                'resolved'    => 'chip-ok',
                                default       => 'chip-dark',
                            };
                        @endphp
                        <span class="chip {{ $chipClass }}" style="font-size:10px;padding:2px 8px;">{{ $ticket->status }}</span>
                    </td>
                    <td style="padding:14px 20px;text-align:right;">
                        <a href="{{ route('admin.tickets.show', $ticket) }}"
                           style="font-size:12px;color:var(--tan);text-decoration:underline;text-underline-offset:3px;font-weight:500;">
                            {{ in_array($ticket->status, ['open','in_progress']) ? 'Respond →' : 'View →' }}
                        </a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" style="padding:60px;text-align:center;color:var(--fg-dim);font-size:14px;">No tickets found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($tickets->hasPages())
    <div style="margin-top:20px;display:flex;align-items:center;justify-content:space-between;font-size:13px;color:var(--fg-dim);">
        <span>Showing {{ $tickets->firstItem() }}–{{ $tickets->lastItem() }} of {{ $tickets->total() }}</span>
        <div class="flex gap-2">
            @if($tickets->onFirstPage())
                <span class="btn-ghost" style="opacity:0.4;cursor:default;">← prev</span>
            @else
                <a href="{{ $tickets->previousPageUrl() }}" class="btn-ghost" style="font-size:12px;padding:6px 14px;">← prev</a>
            @endif
            @if($tickets->hasMorePages())
                <a href="{{ $tickets->nextPageUrl() }}" class="btn-ghost" style="font-size:12px;padding:6px 14px;">next →</a>
            @else
                <span class="btn-ghost" style="opacity:0.4;cursor:default;">next →</span>
            @endif
        </div>
    </div>
@endif

</x-layouts.admin>
