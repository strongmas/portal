<x-layouts.admin title="{{ $ticket->case_no }}">

<div class="flex items-center gap-2 mb-6" style="font-size:13px;color:var(--fg-dim);">
    <a href="{{ route('admin.tickets.index') }}" style="color:var(--fg-dim);text-decoration:none;" onmouseover="this.style.color='var(--tan)'" onmouseout="this.style.color='var(--fg-dim)'">Tickets</a>
    <span>/</span>
    <span style="color:var(--fg);font-family:'Geist Mono',monospace;">{{ $ticket->case_no }}</span>
</div>

<div class="grid gap-6" style="grid-template-columns:1fr 320px;">

    {{-- Left: Chat thread --}}
    <div class="flex flex-col gap-4">

        {{-- Ticket header --}}
        <div class="card" style="padding:20px;">
            <div class="flex items-start justify-between gap-4">
                <div style="flex:1;">
                    <div class="flex items-center gap-3" style="margin-bottom:10px;">
                        <span style="font-family:'Geist Mono',monospace;font-size:11px;color:var(--fg-dim);">{{ $ticket->case_no }}</span>
                        @php
                            $chipClass = match($ticket->status) {
                                'open'        => 'chip-danger',
                                'in_progress' => 'chip-pending',
                                'resolved'    => 'chip-ok',
                                default       => 'chip-dark',
                            };
                        @endphp
                        <span class="chip {{ $chipClass }}" style="font-size:11px;padding:3px 9px;">{{ $ticket->status }}</span>
                    </div>
                    <h1 style="font-size:20px;font-weight:700;color:var(--fg);margin-bottom:4px;">{{ $ticket->category }}</h1>
                    @if($ticket->property)
                        <p style="font-size:13px;color:var(--fg-mute);margin-bottom:12px;">Property: {{ $ticket->property->full_name }}</p>
                    @endif
                    <p style="font-size:14px;color:var(--fg-mute);line-height:1.6;border-left:2px solid var(--line-tan);padding-left:14px;">{{ $ticket->body }}</p>
                </div>
                <div style="text-align:right;font-size:12px;color:var(--fg-dim);flex-shrink:0;">
                    <div>{{ $ticket->created_at->format('d M Y') }}</div>
                    <div style="margin-top:2px;">{{ $ticket->created_at->diffForHumans() }}</div>
                </div>
            </div>
        </div>

        {{-- Chat thread --}}
        <div style="border-radius:20px;border:1px solid rgba(255,255,255,0.09);overflow:hidden;background:var(--bg-card);box-shadow:0 12px 48px rgba(0,0,0,0.45),0 2px 8px rgba(0,0,0,0.3),inset 0 1px 0 rgba(255,255,255,0.04);">

            {{-- Thread header --}}
            <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 22px;background:linear-gradient(135deg,var(--bg-raised) 0%,rgba(15,27,51,0.98) 100%);border-bottom:1px solid rgba(255,255,255,0.07);">
                <div style="display:flex;align-items:center;gap:12px;">
                    <div style="position:relative;flex-shrink:0;">
                        <div style="width:40px;height:40px;border-radius:50%;background:linear-gradient(145deg,#f5d5b5 0%,var(--tan) 45%,var(--tan-dark) 100%);display:grid;place-items:center;font-weight:900;color:#0a1428;font-size:10px;letter-spacing:0.04em;box-shadow:0 0 0 2px var(--bg-raised),0 0 0 3px rgba(239,197,157,0.3);">SR</div>
                        <span style="position:absolute;bottom:1px;right:1px;width:10px;height:10px;border-radius:50%;background:#22c55e;border:2px solid var(--bg-raised);display:block;"></span>
                    </div>
                    <div>
                        <div style="font-size:14px;font-weight:700;color:var(--fg);letter-spacing:-0.01em;">Conversation with {{ $ticket->user->name }}</div>
                        <div style="font-size:11px;color:var(--fg-dim);margin-top:2px;font-family:'Geist Mono',monospace;">{{ $ticket->case_no }} · {{ $messages->count() }} {{ Str::plural('message', $messages->count()) }}</div>
                    </div>
                </div>
                @php
                    $chipClass = match($ticket->status) {
                        'open'        => 'chip-danger',
                        'in_progress' => 'chip-pending',
                        'resolved'    => 'chip-ok',
                        default       => 'chip-dark',
                    };
                @endphp
                <span class="chip {{ $chipClass }}" style="font-size:11px;padding:4px 11px;">{{ $ticket->status }}</span>
            </div>

            {{-- Messages --}}
            <div style="padding:24px 22px 16px;display:flex;flex-direction:column;gap:0;max-height:460px;overflow-y:auto;background:radial-gradient(ellipse 70% 40% at 20% 90%,rgba(239,197,157,0.035) 0%,transparent 100%),var(--bg);">
                @if($messages->isEmpty())
                    <div style="display:flex;flex-direction:column;align-items:center;gap:16px;padding:48px 20px;text-align:center;">
                        <div style="width:60px;height:60px;border-radius:50%;background:linear-gradient(145deg,var(--bg-raised),var(--bg-hover));border:1px solid rgba(255,255,255,0.08);display:grid;place-items:center;box-shadow:0 12px 32px rgba(0,0,0,0.4);">
                            <svg width="24" height="24" fill="none" stroke="var(--tan)" stroke-width="1.5" viewBox="0 0 24 24"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                        </div>
                        <p style="font-size:14px;color:var(--fg-dim);margin:0;">No messages yet in this conversation.</p>
                    </div>
                @else
                    @foreach($messages as $msg)
                        @php
                            $isBuyer   = $msg->sender === 'buyer';
                            $prevMsg   = !$loop->first ? $messages[$loop->index - 1] : null;
                            $isGrouped = $prevMsg && $prevMsg->sender === $msg->sender;
                        @endphp
                        <div style="display:flex;align-items:flex-end;gap:9px;{{ $isBuyer ? 'justify-content:flex-end;' : 'justify-content:flex-start;' }}margin-top:{{ $isGrouped ? '3' : '16' }}px;">

                            @unless($isBuyer)
                                @if(!$isGrouped)
                                    <div style="width:34px;height:34px;border-radius:50%;background:linear-gradient(145deg,#f5d5b5 0%,var(--tan) 50%,var(--tan-dark) 100%);display:grid;place-items:center;font-weight:900;color:#0a1428;font-size:9px;flex-shrink:0;box-shadow:0 0 0 2px var(--bg),0 4px 14px rgba(239,197,157,0.28);letter-spacing:0.04em;">SR</div>
                                @else
                                    <div style="width:34px;flex-shrink:0;"></div>
                                @endif
                            @endunless

                            <div style="max-width:66%;display:flex;flex-direction:column;{{ $isBuyer ? 'align-items:flex-end;' : 'align-items:flex-start;' }}">
                                @if(!$isGrouped)
                                    <span style="font-size:10px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:var(--tan);opacity:0.85;margin-bottom:5px;{{ $isBuyer ? 'padding-right:14px;' : 'padding-left:14px;' }}">
                                        {{ $isBuyer ? $ticket->user->name : 'Support Team' }}
                                    </span>
                                @endif

                                <div style="font-size:14px;line-height:1.6;word-break:break-word;white-space:pre-wrap;
                                    {{ $isBuyer
                                        ? 'background:linear-gradient(145deg,#f5d5b5 0%,var(--tan) 45%,var(--tan-dark) 100%);color:#0d1e3a;border-radius:18px 18px 4px 18px;padding:12px 16px;box-shadow:0 6px 28px rgba(239,197,157,0.2),0 2px 6px rgba(0,0,0,0.32);'
                                        : 'background:linear-gradient(160deg,rgba(26,54,100,0.9) 0%,rgba(15,27,51,0.98) 100%);border:1px solid rgba(255,255,255,0.09);color:var(--fg);border-radius:18px 18px 18px 4px;padding:12px 16px;box-shadow:0 6px 22px rgba(0,0,0,0.28),inset 0 1px 0 rgba(255,255,255,0.06);' }}">
                                    {{ $msg->body }}
                                    <div style="display:flex;align-items:center;gap:5px;margin-top:5px;{{ $isBuyer ? 'justify-content:flex-end;' : 'justify-content:flex-start;' }}">
                                        <span style="font-size:10px;line-height:1;{{ $isBuyer ? 'color:#0d1e3a;opacity:0.42;' : 'opacity:0.36;' }}">{{ $msg->created_at->format('d M · H:i') }}</span>
                                        @if($isBuyer)
                                            <svg width="18" height="9" viewBox="0 0 18 9" fill="none" stroke="rgba(13,30,58,0.38)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M1 4.5l2.2 2.2L7.5 2M6 4.5l2.2 2.2L12.5 2M11 4.5l2 2"/>
                                            </svg>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            @if($isBuyer)
                                @if(!$isGrouped)
                                    <div style="width:34px;height:34px;border-radius:50%;background:var(--bg-raised);border:1px solid rgba(255,255,255,0.1);display:grid;place-items:center;font-size:12px;font-weight:700;color:var(--fg-mute);flex-shrink:0;box-shadow:0 0 0 2px var(--bg);">{{ $ticket->user->initials }}</div>
                                @else
                                    <div style="width:34px;flex-shrink:0;"></div>
                                @endif
                            @endif
                        </div>
                    @endforeach
                @endif
            </div>

            {{-- Reply box --}}
            @if(!in_array($ticket->status, ['resolved','closed']))
                <div style="border-top:1px solid rgba(255,255,255,0.06);padding:16px 20px 18px;background:linear-gradient(180deg,var(--bg-card) 0%,rgba(10,20,40,0.55) 100%);">
                    <form method="POST" action="{{ route('admin.tickets.reply', $ticket) }}">
                        @csrf
                        <div style="display:flex;align-items:flex-end;gap:10px;background:var(--bg-raised);border:1px solid rgba(255,255,255,0.1);border-radius:17px;padding:12px 12px 12px 18px;transition:border-color 0.2s,box-shadow 0.2s;"
                             onfocusin="this.style.borderColor='rgba(239,197,157,0.3)';this.style.boxShadow='0 0 0 4px rgba(239,197,157,0.07)'"
                             onfocusout="this.style.borderColor='rgba(255,255,255,0.1)';this.style.boxShadow='none'">
                            <textarea name="body" rows="2" required
                                      placeholder="Reply to {{ $ticket->user->name }}…"
                                      style="flex:1;background:none;border:none;color:var(--fg);font-size:14px;line-height:1.6;resize:none;outline:none;max-height:120px;padding:0;font-family:inherit;overflow-y:auto;"
                                      oninput="this.style.height='auto';this.style.height=Math.min(this.scrollHeight,120)+'px'">{{ old('body') }}</textarea>
                            <button type="submit"
                                    style="width:42px;height:42px;border-radius:13px;background:linear-gradient(145deg,#f5d5b5 0%,var(--tan) 45%,var(--tan-dark) 100%);border:none;cursor:pointer;display:grid;place-items:center;flex-shrink:0;box-shadow:0 4px 18px rgba(239,197,157,0.28);transition:all 0.18s;"
                                    onmouseover="this.style.transform='translateY(-1px) scale(1.05)'"
                                    onmouseout="this.style.transform='translateY(0) scale(1)'">
                                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#0d1e3a" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
                                </svg>
                            </button>
                        </div>
                        <p style="font-size:11px;color:var(--fg-faint);margin-top:8px;text-align:center;letter-spacing:0.02em;">Buyer sees this immediately in their portal</p>
                    </form>
                </div>
            @else
                <div style="border-top:1px solid rgba(255,255,255,0.06);padding:14px 22px;text-align:center;font-size:12px;color:var(--fg-dim);background:var(--bg-raised);">
                    Ticket is <strong>{{ $ticket->status }}</strong> — update status above to reopen
                </div>
            @endif
        </div>
    </div>

    {{-- Right: Ticket management --}}
    <div class="flex flex-col gap-4">

        {{-- Buyer info --}}
        <div class="card" style="padding:18px;">
            <h3 style="font-size:13px;font-weight:600;color:var(--fg);margin-bottom:14px;">Buyer</h3>
            <div class="flex items-center gap-3" style="margin-bottom:12px;">
                <div class="avatar" style="width:38px;height:38px;font-size:12px;flex-shrink:0;">{{ $ticket->user->initials }}</div>
                <div>
                    <div style="font-weight:500;color:var(--fg);font-size:14px;">{{ $ticket->user->name }}</div>
                    <div style="font-size:12px;color:var(--fg-dim);">{{ $ticket->user->email }}</div>
                </div>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                @if($ticket->user->is_kyc_verified)
                    <span class="chip chip-ok" style="font-size:10px;padding:2px 7px;">✓ KYC</span>
                @else
                    <span class="chip chip-pending" style="font-size:10px;padding:2px 7px;">KYC pending</span>
                @endif
                <span style="font-size:12px;color:var(--fg-dim);">Since {{ $ticket->user->member_since }}</span>
            </div>
            <div style="margin-top:12px;padding-top:12px;border-top:1px solid var(--line);">
                <a href="{{ route('admin.buyers.show', $ticket->user) }}" style="font-size:13px;color:var(--tan);text-decoration:underline;text-underline-offset:3px;">View buyer profile →</a>
            </div>
        </div>

        {{-- Status management --}}
        <div class="card" style="padding:18px;">
            <h3 style="font-size:13px;font-weight:600;color:var(--fg);margin-bottom:14px;">Update status</h3>
            <form method="POST" action="{{ route('admin.tickets.update', $ticket) }}" style="display:flex;flex-direction:column;gap:10px;">
                @csrf @method('PATCH')
                <select name="status" class="input-base" style="font-size:13px;">
                    <option value="open"        {{ $ticket->status === 'open'        ? 'selected' : '' }}>Open</option>
                    <option value="in_progress" {{ $ticket->status === 'in_progress' ? 'selected' : '' }}>In progress</option>
                    <option value="resolved"    {{ $ticket->status === 'resolved'    ? 'selected' : '' }}>Resolved</option>
                    <option value="closed"      {{ $ticket->status === 'closed'      ? 'selected' : '' }}>Closed</option>
                </select>
                <button type="submit" class="btn-primary" style="justify-content:center;">Update status</button>
            </form>
        </div>

        {{-- Ticket details --}}
        <div class="card" style="padding:18px;">
            <h3 style="font-size:13px;font-weight:600;color:var(--fg);margin-bottom:14px;">Ticket details</h3>
            <div style="display:flex;flex-direction:column;gap:10px;font-size:13px;">
                @foreach([
                    ['Case number', '<span style="font-family:\'Geist Mono\',monospace;font-size:12px;">'.$ticket->case_no.'</span>'],
                    ['Category', e(Str::limit($ticket->category, 40))],
                    ['Submitted', $ticket->created_at->format('d M Y, H:i')],
                    ['Last updated', $ticket->updated_at->diffForHumans()],
                ] as [$label, $val])
                    <div class="flex justify-between gap-3">
                        <span style="color:var(--fg-dim);flex-shrink:0;">{{ $label }}</span>
                        <span style="font-weight:500;color:var(--fg);text-align:right;">{!! $val !!}</span>
                    </div>
                @endforeach
                @if($ticket->property)
                    <div class="flex justify-between gap-3">
                        <span style="color:var(--fg-dim);flex-shrink:0;">Property</span>
                        <span style="font-weight:500;color:var(--fg);text-align:right;font-size:12px;">{{ $ticket->property->full_name }}</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Other tickets from same buyer --}}
        @php $otherTickets = $ticket->user->supportTickets->where('id', '!=', $ticket->id)->take(3); @endphp
        @if($otherTickets->isNotEmpty())
            <div class="card" style="padding:0;overflow:hidden;">
                <div style="padding:14px 16px;border-bottom:1px solid var(--line);">
                    <h3 style="font-size:13px;font-weight:600;color:var(--fg);">Other tickets from this buyer</h3>
                </div>
                @foreach($otherTickets as $other)
                    <a href="{{ route('admin.tickets.show', $other) }}"
                       style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-top:1px solid var(--line);text-decoration:none;"
                       onmouseover="this.style.background='var(--bg-raised)'" onmouseout="this.style.background='transparent'">
                        <div>
                            <div style="font-family:'Geist Mono',monospace;font-size:11px;color:var(--fg-dim);">{{ $other->case_no }}</div>
                            <div style="font-size:13px;color:var(--fg);">{{ Str::limit($other->category, 30) }}</div>
                        </div>
                        <span class="chip {{ $other->status === 'resolved' ? 'chip-ok' : 'chip-dark' }}" style="font-size:10px;padding:2px 7px;">{{ $other->status }}</span>
                    </a>
                @endforeach
            </div>
        @endif

    </div>
</div>

</x-layouts.admin>
