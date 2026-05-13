<x-layouts.admin title="Buyer Documents">

<div class="flex items-center justify-between mb-6">
    <div>
        <div class="eyebrow" style="margin-bottom:4px;">Admin</div>
        <h1 style="font-size:24px;font-weight:700;color:var(--fg);letter-spacing:-0.02em;">Buyer Documents</h1>
        <p style="font-size:13px;color:var(--fg-dim);margin:4px 0 0;">Documents uploaded by buyers for review. Flag any document that does not match what was sent.</p>
    </div>
</div>

@if(session('success'))
<div style="background:#d1fae5;border:1px solid #6ee7b7;border-radius:10px;padding:12px 16px;margin-bottom:20px;font-size:13px;color:#065f46;">
    {{ session('success') }}
</div>
@endif

<div class="card" style="padding:0;overflow:hidden;">
    <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
            <tr style="border-bottom:1px solid var(--line);background:var(--bg-raised);">
                <th style="padding:10px 20px;text-align:left;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Document</th>
                <th style="padding:10px 20px;text-align:left;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Property</th>
                <th style="padding:10px 20px;text-align:left;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Uploaded by</th>
                <th style="padding:10px 20px;text-align:left;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Status</th>
                <th style="padding:10px 20px;text-align:left;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Uploaded</th>
                <th style="padding:10px 20px;"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($documents as $doc)
            <tr x-data="{ showFlag: false }" style="border-top:1px solid var(--line);" onmouseover="this.style.background='var(--bg-raised)'" onmouseout="this.style.background='transparent'">
                <td style="padding:12px 20px;">
                    <p style="font-weight:600;color:var(--fg);margin:0 0 2px;">{{ Str::limit($doc->title, 40) }}</p>
                    <p style="font-size:11px;color:var(--fg-dim);margin:0;text-transform:capitalize;">{{ $doc->type_label }} · v{{ $doc->version }}</p>
                    @if($doc->flag_reason)
                    <p style="font-size:11px;color:#ca8a04;margin:4px 0 0;font-style:italic;">"{{ Str::limit($doc->flag_reason, 60) }}"</p>
                    @endif
                </td>
                <td style="padding:12px 20px;color:var(--fg-dim);">{{ $doc->property->development_name }}</td>
                <td style="padding:12px 20px;color:var(--fg-dim);">
                    {{ $doc->uploader?->name ?? '—' }}
                    <span style="display:block;font-size:11px;color:var(--fg-mute);">{{ $doc->uploader?->email }}</span>
                </td>
                <td style="padding:12px 20px;">
                    @if($doc->status === 'flagged')
                        <span class="chip" style="background:#fef9c3;color:#713f12;border:1px solid #fde047;font-size:10px;padding:2px 8px;">Flagged</span>
                    @elseif($doc->status === 'verified')
                        <span class="chip chip-ok" style="font-size:10px;padding:2px 8px;">Verified</span>
                    @else
                        <span class="chip chip-dark" style="font-size:10px;padding:2px 8px;">Pending Review</span>
                    @endif
                </td>
                <td style="padding:12px 20px;color:var(--fg-mute);">{{ $doc->created_at->format('d M Y') }}</td>
                <td style="padding:12px 20px;text-align:right;vertical-align:top;">
                    <div style="display:flex;align-items:center;gap:12px;justify-content:flex-end;flex-wrap:wrap;">
                        {{-- Download --}}
                        <a href="{{ route('admin.documents.download', $doc) }}"
                           style="font-size:12px;font-weight:600;color:var(--hi);text-decoration:none;white-space:nowrap;display:flex;align-items:center;gap:4px;">
                            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                            Download
                        </a>

                        {{-- Verify --}}
                        @if($doc->status !== 'verified')
                        <form method="POST" action="{{ route('admin.documents.verify', $doc) }}" style="display:inline;">
                            @csrf @method('PATCH')
                            <button type="submit" style="font-size:12px;color:var(--ok);background:none;border:none;cursor:pointer;text-decoration:underline;text-underline-offset:3px;white-space:nowrap;">Verify</button>
                        </form>
                        @endif

                        {{-- Flag / Unflag --}}
                        @if($doc->status === 'flagged')
                        <form method="POST" action="{{ route('admin.documents.unflag', $doc) }}" style="display:inline;">
                            @csrf @method('PATCH')
                            <button type="submit" style="font-size:12px;color:var(--fg-dim);background:none;border:none;cursor:pointer;text-decoration:underline;text-underline-offset:3px;white-space:nowrap;">Clear Flag</button>
                        </form>
                        @elseif($doc->status !== 'verified')
                        <button type="button" @click="showFlag = !showFlag"
                                style="font-size:12px;color:#ca8a04;background:none;border:none;cursor:pointer;text-decoration:underline;text-underline-offset:3px;white-space:nowrap;">
                            Flag
                        </button>
                        @endif

                        {{-- Delete --}}
                        <form method="POST" action="{{ route('admin.documents.destroy', $doc) }}" onsubmit="return confirm('Delete this document?')" style="display:inline;">
                            @csrf @method('DELETE')
                            <button type="submit" style="font-size:12px;color:var(--danger);background:none;border:none;cursor:pointer;text-decoration:underline;text-underline-offset:3px;">Delete</button>
                        </form>
                    </div>

                    {{-- Inline flag form --}}
                    <div x-show="showFlag" x-cloak style="margin-top:10px;">
                        <form method="POST" action="{{ route('admin.documents.flag', $doc) }}" style="display:flex;flex-direction:column;gap:8px;min-width:220px;">
                            @csrf @method('PATCH')
                            <textarea name="flag_reason" rows="2" class="input-base" placeholder="Reason (optional)…" style="font-size:12px;resize:none;"></textarea>
                            <div style="display:flex;gap:8px;justify-content:flex-end;">
                                <button type="button" @click="showFlag = false" style="font-size:12px;color:var(--fg-dim);background:none;border:none;cursor:pointer;">Cancel</button>
                                <button type="submit" class="btn-primary" style="font-size:12px;padding:5px 14px;background:#ca8a04;border-color:#ca8a04;">Flag &amp; Notify</button>
                            </div>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6">
                    <div style="padding:60px;text-align:center;color:var(--fg-dim);font-size:13px;">No documents uploaded by buyers yet.</div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $documents->links() }}</div>

</x-layouts.admin>
