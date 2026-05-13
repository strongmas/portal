<x-layouts.admin title="Interest Registrations">

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 style="font-size:28px;font-weight:700;color:var(--fg);letter-spacing:-0.02em;">Interest Registrations</h1>
        <p style="font-size:13px;color:var(--fg-mute);margin-top:2px;">Buyers who have expressed interest in available properties and units.</p>
    </div>
</div>

@if(session('success'))
<div style="background:var(--ok-bg,#f0fdf4);border:1px solid var(--ok-border,#86efac);border-radius:8px;padding:12px 16px;margin-bottom:20px;font-size:13px;color:var(--ok-text,#166534);">
    {{ session('success') }}
</div>
@endif

<div class="card" style="padding:0;overflow:visible;">
    @if($interests->isEmpty())
        <div style="padding:60px;text-align:center;color:var(--fg-dim);font-size:13px;border-radius:14px;">No interest registrations yet.</div>
    @else
    <div style="overflow-x:auto;border-radius:14px;">
    <table style="width:100%;min-width:780px;border-collapse:collapse;font-size:13px;">
        <thead>
            <tr style="border-bottom:1px solid var(--line);background:var(--bg-raised);">
                @foreach(['Buyer','Property','Unit','Registered','Status','Sales Rep','Actions'] as $h)
                <th style="padding:10px 20px;text-align:{{ $loop->last ? 'right' : 'left' }};font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;white-space:nowrap;">{{ $h }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($interests as $interest)
            <tr style="border-top:1px solid var(--line);transition:background var(--dur-fast);"
                onmouseover="this.style.background='var(--bg-raised)'" onmouseout="this.style.background='transparent'">
                <td style="padding:12px 20px;white-space:nowrap;">
                    <div style="font-weight:500;color:var(--fg);">{{ $interest->buyer->name }}</div>
                    <div style="font-size:11px;color:var(--fg-dim);">{{ $interest->buyer->email }}</div>
                    @if($interest->buyer->phone)
                    <div style="font-size:11px;color:var(--fg-dim);">{{ $interest->buyer->phone }}</div>
                    @endif
                </td>
                <td style="padding:12px 20px;">
                    <a href="{{ route('admin.properties.show', $interest->property) }}"
                       style="font-weight:500;color:var(--fg);text-decoration:none;"
                       onmouseover="this.style.color='var(--tan)'" onmouseout="this.style.color='var(--fg)'">
                        {{ $interest->property->development_name }}
                    </a>
                    <div style="font-size:11px;color:var(--fg-dim);">{{ $interest->property->address }}</div>
                </td>
                <td style="padding:12px 20px;font-size:12px;color:var(--fg-dim);white-space:nowrap;">
                    {{ $interest->unit?->label ?? '— Any unit —' }}
                </td>
                <td style="padding:12px 20px;font-size:12px;color:var(--fg-dim);white-space:nowrap;">
                    {{ $interest->created_at->format('d M Y') }}
                </td>
                <td style="padding:12px 20px;white-space:nowrap;">
                    @if($interest->status === 'forwarded')
                        <span class="chip chip-ok" style="font-size:10px;padding:2px 8px;">Forwarded</span>
                        @if($interest->forwarded_at)
                        <div style="font-size:10px;color:var(--fg-dim);margin-top:3px;">{{ $interest->forwarded_at->format('d M Y') }}</div>
                        @endif
                    @else
                        <span class="chip chip-dark" style="font-size:10px;padding:2px 8px;">Pending</span>
                    @endif
                </td>
                <td style="padding:12px 20px;font-size:12px;color:var(--fg-dim);white-space:nowrap;">
                    {{ $interest->salesRep?->name ?? '—' }}
                </td>
                <td style="padding:12px 20px;text-align:right;">
                    @if($interest->status === 'pending')
                    <div x-data="{ open: false, top: 0, right: 0 }" style="display:inline-block;">
                        <button type="button"
                                @click="open = !open; if(open){ let r = $el.getBoundingClientRect(); top = r.bottom + 6; right = window.innerWidth - r.right; }"
                                class="btn-primary" style="font-size:12px;padding:5px 12px;white-space:nowrap;">
                            Forward to rep ▾
                        </button>
                        <div x-show="open" x-cloak @click.outside="open = false"
                             :style="`position:fixed;top:${top}px;right:${right}px;background:var(--bg-card);border:1px solid var(--line);border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,0.18);z-index:500;min-width:220px;padding:12px;`">
                            <form method="POST" action="{{ route('admin.interests.forward', $interest) }}">
                                @csrf
                                <label class="eyebrow" style="display:block;margin-bottom:6px;font-size:10px;">Select sales rep</label>
                                <select name="sales_rep_id" class="input-base" style="font-size:12px;margin-bottom:10px;" required>
                                    <option value="">— choose —</option>
                                    @foreach($salesReps as $rep)
                                    <option value="{{ $rep->id }}">{{ $rep->name }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn-primary" style="font-size:12px;width:100%;">Send &amp; Forward</button>
                            </form>
                        </div>
                    </div>
                    @else
                    <form method="POST" action="{{ route('admin.interests.forward', $interest) }}"
                          style="display:inline-flex;align-items:center;gap:6px;">
                        @csrf
                        <select name="sales_rep_id" class="input-base" style="font-size:11px;padding:4px 8px;" required>
                            @foreach($salesReps as $rep)
                            <option value="{{ $rep->id }}" {{ $interest->sales_rep_id == $rep->id ? 'selected' : '' }}>{{ $rep->name }}</option>
                            @endforeach
                        </select>
                        <button type="submit" style="font-size:11px;color:var(--fg-dim);text-decoration:underline;text-underline-offset:3px;background:none;border:none;cursor:pointer;white-space:nowrap;"
                                onmouseover="this.style.color='var(--fg)'" onmouseout="this.style.color='var(--fg-dim)'">Reassign</button>
                    </form>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    </div>

    @if($interests->hasPages())
    <div style="padding:16px 20px;border-top:1px solid var(--line);">
        {{ $interests->links() }}
    </div>
    @endif
    @endif
</div>

</x-layouts.admin>
