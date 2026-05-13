<x-portal-layout>
    <x-slot:title>Support</x-slot>

    <div style="display:flex;align-items:baseline;justify-content:space-between;margin-bottom:16px;">
        <div>
            <div class="eyebrow" style="margin-bottom:3px;">Customer Care</div>
            <h1 style="font-size:22px;font-weight:700;color:var(--fg);letter-spacing:-.02em;margin:0;">How can we help?</h1>
        </div>
    </div>

    <div style="display:grid;gap:20px;grid-template-columns:1fr 284px;align-items:start;">

    {{-- Chat window --}}
    @php
        $initMessages = $messages->map(fn($m) => [
            'id'              => $m->id,
            'sender'          => $m->sender,
            'body'            => $m->body,
            'time'            => $m->created_at->format('H:i'),
            'has_attachment'  => $m->has_attachment,
            'attachment_name' => $m->attachment_name,
            'is_image'        => $m->is_image,
        ]);
    @endphp

    <div class="card"
         style="display:flex;flex-direction:column;height:calc(100vh - 158px);min-height:540px;padding:0;overflow:hidden;"
         x-data="{
            messages: {{ json_encode($initMessages) }},
            newMsg: '',
            loading: false,
            async send() {
                const body = this.newMsg.trim();
                if (!body || this.loading) return;
                this.newMsg = '';
                this.loading = true;
                try {
                    const r = await fetch('{{ route('support.messages.store') }}', {
                        method:'POST',
                        headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'},
                        body:JSON.stringify({body})
                    });
                    this.messages.push(await r.json());
                    this.$nextTick(()=> this.$refs.end?.scrollIntoView({behavior:'smooth'}));
                } catch(e) {}
                this.loading = false;
            },
            async poll() {
                try {
                    const r = await fetch('{{ route('support.messages') }}');
                    const all = await r.json();
                    const last = this.messages.length ? Math.max(...this.messages.map(m=>m.id)) : 0;
                    const fresh = all.filter(m=>m.id>last);
                    if (fresh.length) {
                        this.messages.push(...fresh);
                        this.$nextTick(()=> this.$refs.end?.scrollIntoView({behavior:'smooth'}));
                    }
                } catch(e) {}
            }
         }"
         x-init="$nextTick(()=> $refs.end?.scrollIntoView()); setInterval(()=> poll(), 5000);">

        {{-- Header --}}
        <div style="flex-shrink:0;display:flex;align-items:center;justify-content:space-between;
                    padding:14px 18px;border-bottom:1px solid var(--line);">
            <div style="display:flex;align-items:center;gap:11px;">
                <div style="width:38px;height:38px;border-radius:50%;background:var(--tan);
                            display:grid;place-items:center;font-weight:700;color:#0a1428;font-size:12px;flex-shrink:0;">SR</div>
                <div>
                    <div style="font-size:14px;font-weight:600;color:var(--fg);">Strongmas Care</div>
                    <div style="font-size:12px;color:var(--ok);margin-top:1px;">● Online</div>
                </div>
            </div>
            <a href="tel:07007867666627"
               style="font-size:13px;color:var(--fg-dim);text-decoration:none;display:flex;align-items:center;gap:6px;"
               onmouseover="this.style.color='var(--tan)'" onmouseout="this.style.color='var(--fg-dim)'">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81 19.79 19.79 0 012 1.19 2 2 0 014 1h3a2 2 0 012 1.72c.127.96.36 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 8.75a16 16 0 006.34 6.34l1.12-1.12a2 2 0 012.11-.45c.907.34 1.85.573 2.81.7A2 2 0 0122 16.92z"/>
                </svg>
                Call us
            </a>
        </div>

        {{-- Messages --}}
        <div style="flex:1;overflow-y:auto;padding:20px 18px;display:flex;flex-direction:column;gap:12px;background:var(--bg);">

            <template x-if="messages.length === 0">
                <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;
                            gap:12px;padding:40px 20px;text-align:center;margin:auto;">
                    <svg width="32" height="32" fill="none" stroke="var(--tan)" stroke-width="1.5"
                         stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>
                    </svg>
                    <div>
                        <p style="font-size:15px;font-weight:600;color:var(--fg);margin:0 0 6px;">Start the conversation</p>
                        <p style="font-size:13px;color:var(--fg-dim);margin:0;line-height:1.6;">Our team typically replies in under 5 minutes.</p>
                    </div>
                </div>
            </template>

            <template x-for="msg in messages" :key="msg.id">
                <div :style="'display:flex;' + (msg.sender==='buyer' ? 'justify-content:flex-end;' : 'justify-content:flex-start;')">
                    <div style="max-width:68%;">
                        <div :style="msg.sender==='buyer'
                            ? 'background:var(--tan);color:#0a1428;padding:10px 14px;border-radius:16px 16px 4px 16px;font-size:14px;line-height:1.6;word-break:break-word;white-space:pre-wrap;'
                            : 'background:var(--bg-card);color:var(--fg);border:1px solid var(--line);padding:10px 14px;border-radius:16px 16px 16px 4px;font-size:14px;line-height:1.6;word-break:break-word;white-space:pre-wrap;'">
                            <template x-if="msg.has_attachment">
                                <div style="display:flex;align-items:center;gap:6px;margin-bottom:6px;font-size:12px;font-weight:600;opacity:.75;">
                                    <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                                        <polyline points="14 2 14 8 20 8"/>
                                    </svg>
                                    <span x-text="msg.attachment_name"></span>
                                </div>
                            </template>
                            <span x-text="msg.body" x-show="msg.body"></span>
                        </div>
                        <div :style="'font-size:11px;color:var(--fg-faint);margin-top:3px;' + (msg.sender==='buyer' ? 'text-align:right;' : '')"
                             x-text="msg.time"></div>
                    </div>
                </div>
            </template>

            <div x-show="loading" x-cloak style="display:flex;justify-content:flex-start;">
                <div style="background:var(--bg-card);border:1px solid var(--line);padding:10px 16px;border-radius:16px;font-size:13px;color:var(--fg-dim);">
                    Typing…
                </div>
            </div>

            <div x-ref="end"></div>
        </div>

        {{-- Input --}}
        @if($canChat)
        <div style="flex-shrink:0;padding:12px 16px;border-top:1px solid var(--line);background:var(--bg-card);">
            <div style="display:flex;align-items:flex-end;gap:10px;">
                <textarea x-ref="ta"
                          class="input-base"
                          placeholder="Message Strongmas Care…"
                          rows="1"
                          style="flex:1;resize:none;max-height:120px;overflow-y:auto;font-size:14px;line-height:1.5;"
                          x-model="newMsg"
                          @keydown.enter.prevent="!$event.shiftKey && send()"
                          @input="$el.style.height='auto'; $el.style.height=Math.min($el.scrollHeight,120)+'px'">
                </textarea>
                <button type="button" @click="send()" :disabled="loading || !newMsg.trim()" class="btn-primary"
                        style="flex-shrink:0;padding:0 18px;height:42px;">
                    Send
                </button>
            </div>
        </div>
        @else
        <div style="flex-shrink:0;padding:14px 16px;border-top:1px solid var(--line);background:var(--bg-card);text-align:center;">
            <p style="font-size:13px;color:var(--fg-mute);margin:0;">Support chat is available to property owners and co-owners only.</p>
        </div>
        @endif
    </div>{{-- end chat window --}}

    {{-- ── Right sidebar ──────────────────────────────────────────────────── --}}
    <div style="display:flex;flex-direction:column;gap:12px;">

        {{-- Contact --}}
        <div class="card" style="padding:18px;">
            <p style="font-size:10px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;
                      color:rgba(176,196,221,.45);margin:0 0 12px;">Other ways to reach us</p>
            <div style="display:flex;flex-direction:column;gap:7px;">
                @foreach([
                    ['tel:07007867666627',           '📞', 'Call us',   '0700-STRONGMAS',        false],
                    ['mailto:hello@strongmasng.com', '✉',  'Email us',  'hello@strongmasng.com', false],
                    ['https://wa.me/2348034441209',  '💬', 'WhatsApp',  '0803 444 1209',         true],
                ] as [$href, $icon, $label, $sub, $blank])
                <a href="{{ $href }}" {{ $blank ? 'target=_blank' : '' }}
                   style="display:flex;align-items:center;gap:11px;padding:10px 13px;
                          border:1px solid var(--line);border-radius:12px;
                          text-decoration:none;background:var(--bg-raised);transition:all .15s;"
                   onmouseover="this.style.borderColor='rgba(239,197,157,.28)';this.style.background='var(--bg-hover)'"
                   onmouseout="this.style.borderColor='var(--line)';this.style.background='var(--bg-raised)'">
                    <span style="width:34px;height:34px;border-radius:10px;
                                 background:var(--bg-card);border:1px solid var(--line);
                                 display:grid;place-items:center;font-size:16px;flex-shrink:0;">{{ $icon }}</span>
                    <div>
                        <p style="font-size:13px;font-weight:600;color:var(--fg);margin:0;line-height:1.2;">{{ $label }}</p>
                        <p style="font-size:11px;color:var(--fg-dim);margin:0;margin-top:1px;">{{ $sub }}</p>
                    </div>
                </a>
                @endforeach
            </div>
        </div>

        {{-- Raise complaint --}}
        @if($canChat)
        <div class="card" style="padding:18px;"
             x-data="{sub:false,submitting:false,caseNo:'',async go(e){e.preventDefault();this.submitting=true;try{const r=await fetch(e.target.action,{method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'},body:new FormData(e.target)});if(r.ok){const d=await r.json();this.caseNo=d.case_no;this.sub=true;e.target.reset()}}catch(e){}this.submitting=false}}">
            <h3 style="font-size:14px;font-weight:700;color:var(--fg);margin:0 0 2px;letter-spacing:-.01em;">Raise a complaint</h3>
            <p style="font-size:12px;color:var(--fg-dim);margin:0 0 14px;">Case ID · guaranteed 48h response</p>

            <div x-show="sub" x-cloak
                 style="background:rgba(93,217,167,.1);border:1px solid rgba(93,217,167,.22);
                        border-radius:10px;padding:14px;">
                <p style="font-size:13px;font-weight:700;color:var(--ok);margin:0 0 4px;">✓ Complaint received</p>
                <p style="font-size:12px;color:var(--fg-dim);margin:0;">
                    Case <span style="font-family:'Geist Mono',monospace;font-weight:600;color:var(--fg);" x-text="caseNo"></span> — response within 48h.
                </p>
                <button type="button" @click="sub=false"
                        style="margin-top:8px;font-size:11px;color:var(--fg-dim);text-decoration:underline;background:none;border:none;cursor:pointer;">
                    Submit another
                </button>
            </div>

            <form x-show="!sub" @submit="go($event)" action="{{ route('support.tickets.store') }}"
                  style="display:flex;flex-direction:column;gap:9px;">
                @csrf
                <select name="category" class="input-base" style="font-size:13px;">
                    <option>Balance not updated</option>
                    <option>Wrong amount on record</option>
                    <option>Project / handover delay</option>
                    <option>Receipt not received</option>
                    <option>Other</option>
                </select>
                <select name="property_id" class="input-base" style="font-size:13px;">
                    <option value="">— any property —</option>
                    @foreach($ownerships as $o)
                        <option value="{{ $o->property_id }}">{{ $o->display_name ?? '—' }}</option>
                    @endforeach
                </select>
                <textarea name="body" rows="3" required class="input-base"
                          style="font-size:13px;resize:none;" placeholder="Describe the issue…"></textarea>
                <button type="submit" class="btn-primary" style="justify-content:center;" :disabled="submitting">
                    <span x-show="!submitting">Submit complaint →</span>
                    <span x-show="submitting">Submitting…</span>
                </button>
            </form>
        </div>
        @else
        <div class="card" style="padding:18px;">
            <h3 style="font-size:14px;font-weight:700;color:var(--fg);margin:0 0 6px;">Raise a complaint</h3>
            <p style="font-size:13px;color:var(--fg-mute);margin:0;">Available to property owners and co-owners only.</p>
        </div>
        @endif

        {{-- Open cases --}}
        @if($tickets->isNotEmpty())
        <div class="card" style="padding:18px;">
            <h3 style="font-size:13px;font-weight:700;color:var(--fg);margin:0 0 12px;">Your open cases</h3>
            <div style="display:flex;flex-direction:column;">
                @foreach($tickets as $ticket)
                <div style="display:flex;align-items:center;justify-content:space-between;
                            padding:10px 0;border-top:1px solid var(--line);">
                    <div>
                        <div style="font-family:'Geist Mono',monospace;font-size:10px;
                                    color:var(--fg-faint);letter-spacing:.06em;">{{ $ticket->case_no }}</div>
                        <div style="font-size:13px;color:var(--fg);margin-top:1px;">{{ Str::limit($ticket->category, 26) }}</div>
                    </div>
                    <span class="chip {{ $ticket->status === 'resolved' ? 'chip-ok' : ($ticket->status === 'in_progress' ? 'chip-pending' : 'chip-dark') }}"
                          style="font-size:10px;">{{ $ticket->status }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- FAQ --}}
        <div class="card" style="padding:18px;">
            <p style="font-size:10px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;
                      color:rgba(176,196,221,.45);margin:0 0 10px;">FAQ</p>
            @foreach([
                'When does my balance update?',
                'Can I edit a logged payment?',
                'Can I change instalment dates?',
                'What if I miss a payment?',
            ] as $q)
            <div style="display:flex;align-items:center;justify-content:space-between;
                        padding:10px 0;border-top:1px solid var(--line);
                        font-size:13px;color:var(--fg-dim);cursor:pointer;transition:color .15s;"
                 onmouseover="this.style.color='var(--fg)'" onmouseout="this.style.color='var(--fg-dim)'">
                <span>{{ $q }}</span>
                <span style="opacity:.3;font-size:16px;line-height:1;">›</span>
            </div>
            @endforeach
        </div>

    </div>{{-- end sidebar --}}
    </div>
</x-portal-layout>
