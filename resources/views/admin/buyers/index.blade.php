<x-layouts.admin title="Buyers">

<div class="page-header mb-6">
    <div>
        <div class="eyebrow" style="margin-bottom:6px;">People</div>
        <h1 style="font-size:28px;font-weight:700;color:var(--fg);letter-spacing:-0.02em;">Buyers</h1>
        <p style="font-size:13px;color:var(--fg-mute);margin-top:2px;">{{ $buyers->total() }} registered {{ Str::plural('buyer', $buyers->total()) }}</p>
    </div>
    @if(auth()->user()->isSuperAdmin())
        <a href="{{ route('admin.buyers.create') }}" class="btn-primary">+ Add buyer</a>
    @endif
</div>

{{-- Search bar --}}
<form method="GET" class="flex items-center gap-3 mb-5">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Search name or email…"
           class="input-base" style="max-width:320px;flex:1;"/>
    <button type="submit" class="btn-ghost" style="font-size:13px;">Search</button>
    @if(request('q'))
        <a href="{{ route('admin.buyers.index') }}" style="font-size:13px;color:var(--fg-dim);text-decoration:underline;text-underline-offset:3px;">Clear</a>
    @endif
</form>

<div class="card" style="padding:0;overflow:hidden;">
    <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
            <tr style="border-bottom:1px solid var(--line);">
                <th style="padding:12px 20px;text-align:left;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Buyer</th>
                <th style="padding:12px 20px;text-align:left;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Email</th>
                <th style="padding:12px 20px;text-align:center;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">KYC</th>
                <th style="padding:12px 20px;text-align:center;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Properties</th>
                <th style="padding:12px 20px;text-align:left;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Member since</th>
                <th style="padding:12px 20px;text-align:right;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($buyers as $buyer)
                <tr style="border-top:1px solid var(--line);transition:background var(--dur-fast);"
                    onmouseover="this.style.background='var(--bg-raised)'" onmouseout="this.style.background='transparent'">
                    <td style="padding:14px 20px;">
                        <div class="flex items-center gap-3">
                            <div class="avatar" style="width:34px;height:34px;font-size:11px;flex-shrink:0;">{{ $buyer->initials }}</div>
                            <div>
                                <a href="{{ route('admin.buyers.show', $buyer) }}"
                                   style="font-weight:500;color:var(--fg);text-decoration:none;display:block;"
                                   onmouseover="this.style.color='var(--tan)'" onmouseout="this.style.color='var(--fg)'">{{ $buyer->name }}</a>
                                @if($buyer->phone)
                                    <div style="font-size:11px;color:var(--fg-dim);margin-top:1px;">{{ $buyer->phone }}</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td style="padding:14px 20px;color:var(--fg-mute);">{{ $buyer->email }}</td>
                    <td style="padding:14px 20px;text-align:center;">
                        @if($buyer->is_kyc_verified)
                            <span class="chip chip-ok" style="font-size:10px;padding:2px 8px;">✓ verified</span>
                        @else
                            <span class="chip chip-pending" style="font-size:10px;padding:2px 8px;">pending</span>
                        @endif
                    </td>
                    <td style="padding:14px 20px;text-align:center;font-weight:600;color:var(--fg);">{{ $buyer->ownerships_count }}</td>
                    <td style="padding:14px 20px;font-family:'Geist Mono',monospace;font-size:11px;color:var(--fg-dim);">{{ $buyer->created_at->format('d M Y') }}</td>
                    <td style="padding:14px 20px;text-align:right;">
                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('admin.buyers.show', $buyer) }}"
                               style="font-size:12px;color:var(--tan);text-decoration:underline;text-underline-offset:3px;">view</a>
                            @if(auth()->user()->isSuperAdmin())
                                <a href="{{ route('admin.buyers.edit', $buyer) }}"
                                   style="font-size:12px;color:var(--fg-dim);text-decoration:underline;text-underline-offset:3px;"
                                   onmouseover="this.style.color='var(--fg)'" onmouseout="this.style.color='var(--fg-dim)'">edit</a>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="padding:60px;text-align:center;color:var(--fg-dim);font-size:14px;">No buyers found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($buyers->hasPages())
    <div style="margin-top:20px;display:flex;align-items:center;justify-content:space-between;font-size:13px;color:var(--fg-dim);">
        <span>Showing {{ $buyers->firstItem() }}–{{ $buyers->lastItem() }} of {{ $buyers->total() }}</span>
        <div class="flex gap-2">
            @if($buyers->onFirstPage())
                <span class="btn-ghost" style="opacity:0.4;cursor:default;">← prev</span>
            @else
                <a href="{{ $buyers->previousPageUrl() }}" class="btn-ghost" style="font-size:12px;padding:6px 14px;">← prev</a>
            @endif
            @if($buyers->hasMorePages())
                <a href="{{ $buyers->nextPageUrl() }}" class="btn-ghost" style="font-size:12px;padding:6px 14px;">next →</a>
            @else
                <span class="btn-ghost" style="opacity:0.4;cursor:default;">next →</span>
            @endif
        </div>
    </div>
@endif

</x-layouts.admin>
