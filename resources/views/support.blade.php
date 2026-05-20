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
            <a href="tel:09010777777"
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
                    ['tel:09010777777',               '📞', 'Call us',   '0901 077 7777',         false],
                    ['mailto:hello@strongmas.ng',    '✉',  'Email us',  'hello@strongmas.ng',    false],
                    ['https://wa.me/2349010777777',  '💬', 'WhatsApp',  '0901 077 7777',         true],
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
                    Case <span style="font-family:'Geist Mono',monospace;font-weight:600;color:var(--fg);" x-text="caseNo"></span> - response within 48h.
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
                    <option value="">- any property -</option>
                    @foreach($ownerships as $o)
                        <option value="{{ $o->property_id }}">{{ $o->display_name ?? '-' }}</option>
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
        <div class="card" style="padding:18px;"
             x-data="{
                 open: false,
                 idx: 0,
                 faqs: [
                     {
                         q: 'How do I log a payment?',
                         a: 'Go to Log Payment and fill in the amount, date paid, payment method, bank name, and transaction reference - then attach your proof of payment (bank debit alert, teller, or transfer receipt) as a PDF, JPG, or PNG (max 5 MB). You cannot submit without attaching a proof file. Our team reviews every submission and verifies it within 24–48 business hours.',
                         links: [{ label: 'Log a Payment', url: '{{ route('payments.create') }}' }]
                     },
                     {
                         q: 'How long does my balance take to update?',
                         a: 'After you submit a payment, our team checks the details and your proof of payment for accuracy. If everything is correct they verify it and your outstanding balance updates automatically - this typically takes 24–48 business hours. If the details are wrong, the payment will be flagged so you can correct and resubmit it.',
                         links: [{ label: 'View Payment History', url: '{{ route('payments.history') }}' }]
                     },
                     {
                         q: 'My payment was flagged - what do I do?',
                         a: 'If our team finds incorrect details (wrong amount, wrong date, unclear proof), they will flag the payment and add a reason. You will see a red Flagged banner on that entry in your payment history. Delete the flagged payment and re-submit it with the correct amount, date, transaction reference, and a clear proof of payment.',
                         links: [{ label: 'Payment History', url: '{{ route('payments.history') }}' }]
                     },
                     {
                         q: 'What happens if I miss a payment?',
                         a: 'If an instalment date passes without a logged payment, it will be marked as Overdue on your balance page. This does not automatically cancel your agreement, but we strongly recommend contacting our team as soon as possible. Persistent overdue instalments may affect your agreement status.',
                         links: [{ label: 'My Balance', url: '{{ route('properties.index') }}' }, { label: 'Contact Support', url: '{{ route('support') }}' }]
                     },
                     {
                         q: 'Where can I see construction progress?',
                         a: 'The Project Updates page shows live construction reports for all your properties. You can see the overall progress percentage, current stage (planning, foundation, construction, finishing, completed), a milestone tracker, and every update posted by the Strongmas team - each with photos and notes.',
                         links: [{ label: 'Project Updates', url: '{{ route('project-updates.index') }}' }]
                     },
                     {
                         q: 'Can I share property access with a family member or investor?',
                         a: 'Yes. As the primary owner, open your property\'s balance page and tap Share Access. Invite anyone by email and choose their level: Viewer (project updates and analytics only, cannot make payments), Financial (sees all payment data and can comment), or Co-Owner (full access). The invitation email expires after 7 days - the recipient must create or log in to a Strongmas account to accept.',
                         links: [{ label: 'My Properties', url: '{{ route('properties.index') }}' }]
                     },
                     {
                         q: 'How do I book a site inspection?',
                         a: 'Use the Book Inspection page to request a visit to your property. Select the property, pick your preferred date and time, and add any notes or special requests. Our team will review your request, confirm availability, and assign a sales representative to meet you on site.',
                         links: [{ label: 'Book Inspection', url: '{{ route('appointments.create') }}' }]
                     },
                     {
                         q: 'Where are my property documents?',
                         a: 'The Documents page holds everything in one place - documents you upload (offer letters, deeds of assignment, contracts, survey plans, etc.) and documents shared by Strongmas for your property. Strongmas-uploaded documents also appear directly on your property balance page under the Property Documents section.',
                         links: [{ label: 'My Documents', url: '{{ route('documents.index') }}' }, { label: 'My Properties', url: '{{ route('properties.index') }}' }]
                     },
                     {
                         q: 'Can I view documents for a property I haven\'t bought yet?',
                         a: 'Yes. On any property\'s detail page in Browse, scroll down to the Property Documents section to view and download official documents shared by Strongmas for that development - such as brochures, approvals, and survey plans. You do not need to own or register interest in the property first.',
                         links: [{ label: 'Browse Properties', url: '{{ route('browse.index') }}' }]
                     },
                     {
                         q: 'What does the Analytics page show?',
                         a: 'The Analytics page tracks the investment value of each property you own. It shows your original purchase price, the current estimated market value (updated by Strongmas), your total gain or loss in naira and percentage, and your payment progress. Market insights posted by the team - such as price movements and area demand trends - also appear here.',
                         links: [{ label: 'My Analytics', url: '{{ route('analytics.index') }}' }]
                     },
                     {
                         q: 'What is the Property Map?',
                         a: 'The Map page shows all your properties plotted on an interactive map. Each pin displays the property name, unit, and address. Clicking a pin takes you directly to that property\'s balance page. If a property does not appear on the map it means location coordinates have not been added yet - contact our team.',
                         links: [{ label: 'Property Map', url: '{{ route('map.index') }}' }]
                     },
                     {
                         q: 'How do I update my profile or change my password?',
                         a: 'On your Profile page you can update your full name, phone number, and address, upload or change your profile photo, and change your password. You can also view active login sessions and sign out of devices you no longer use. Your KYC verification status is also shown here.',
                         links: [{ label: 'My Profile', url: '{{ route('profile') }}' }]
                     },
                 ]
             }">

            <p style="font-size:10px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;
                      color:rgba(176,196,221,.45);margin:0 0 10px;">FAQ</p>

            <template x-for="(faq, i) in faqs" :key="i">
                <div @click="idx = i; open = true"
                     style="display:flex;align-items:center;justify-content:space-between;gap:8px;
                            padding:10px 0;border-top:1px solid var(--line);
                            font-size:13px;color:var(--fg-dim);cursor:pointer;transition:color .15s;"
                     onmouseover="this.style.color='var(--fg)'" onmouseout="this.style.color='var(--fg-dim)'">
                    <span x-text="faq.q" style="flex:1;"></span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;opacity:.35;">
                        <polyline points="9 18 15 12 9 6"/>
                    </svg>
                </div>
            </template>

            {{-- Modal backdrop --}}
            <div x-show="open" x-cloak
                 x-transition:enter="transition-opacity ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click.self="open = false"
                 @keydown.escape.window="open = false"
                 style="position:fixed;inset:0;z-index:999;display:flex;align-items:center;justify-content:center;
                        padding:16px;background:rgba(5,14,30,0.72);backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px);">

                {{-- Modal card --}}
                <div x-show="open"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     @click.stop
                     style="width:100%;max-width:500px;background:var(--bg-card);border:1px solid var(--line);
                            border-radius:20px;padding:28px 28px 24px;position:relative;
                            box-shadow:0 32px 80px rgba(0,0,0,0.5);max-height:90vh;overflow-y:auto;">

                    {{-- Close --}}
                    <button @click="open = false"
                            style="position:absolute;top:16px;right:16px;width:32px;height:32px;
                                   border:1px solid var(--line);border-radius:9px;background:var(--bg-raised);
                                   display:grid;place-items:center;cursor:pointer;color:var(--fg-mute);
                                   transition:all .15s;flex-shrink:0;"
                            onmouseover="this.style.background='var(--bg-hover)';this.style.color='var(--fg)'"
                            onmouseout="this.style.background='var(--bg-raised)';this.style.color='var(--fg-mute)'">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>

                    {{-- Tag --}}
                    <div style="display:inline-flex;align-items:center;gap:6px;padding:3px 10px;
                                border-radius:99px;background:rgba(239,197,157,.1);border:1px solid rgba(239,197,157,.2);
                                margin-bottom:16px;">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="var(--tan)" stroke-width="2.5"
                             stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/>
                            <line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                        <span style="font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--tan);">FAQ</span>
                    </div>

                    {{-- Question --}}
                    <h3 style="font-size:17px;font-weight:700;color:var(--fg);margin:0 0 14px;
                               letter-spacing:-.01em;line-height:1.4;padding-right:28px;"
                        x-text="faqs[idx].q"></h3>

                    <div style="width:28px;height:2px;background:var(--tan);border-radius:1px;margin-bottom:16px;opacity:.6;"></div>

                    {{-- Answer --}}
                    <p style="font-size:14px;color:var(--fg-dim);line-height:1.75;margin:0;"
                       x-text="faqs[idx].a"></p>

                    {{-- Action links --}}
                    <template x-if="faqs[idx].links && faqs[idx].links.length">
                        <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:18px;">
                            <template x-for="(lnk, li) in faqs[idx].links" :key="li">
                                <a :href="lnk.url"
                                   style="display:inline-flex;align-items:center;gap:6px;padding:7px 16px;
                                          background:rgba(239,197,157,.08);border:1px solid rgba(239,197,157,.22);
                                          border-radius:99px;font-size:12px;font-weight:600;color:var(--tan);
                                          text-decoration:none;transition:all .15s;"
                                   onmouseover="this.style.background='rgba(239,197,157,.18)'"
                                   onmouseout="this.style.background='rgba(239,197,157,.08)'">
                                    <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5"
                                         viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M5 12h14M12 5l7 7-7 7"/>
                                    </svg>
                                    <span x-text="lnk.label"></span>
                                </a>
                            </template>
                        </div>
                    </template>

                    {{-- Navigation --}}
                    <div style="display:flex;align-items:center;justify-content:space-between;
                                margin-top:24px;padding-top:16px;border-top:1px solid var(--line);gap:12px;">
                        <button @click="idx = (idx - 1 + faqs.length) % faqs.length"
                                style="display:flex;align-items:center;gap:5px;font-size:12px;font-weight:500;
                                       color:var(--fg-dim);background:var(--bg-raised);border:1px solid var(--line);
                                       border-radius:8px;padding:7px 14px;cursor:pointer;transition:all .15s;white-space:nowrap;"
                                onmouseover="this.style.borderColor='var(--line-strong)';this.style.color='var(--fg)'"
                                onmouseout="this.style.borderColor='var(--line)';this.style.color='var(--fg-dim)'">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="15 18 9 12 15 6"/></svg>
                            Prev
                        </button>
                        <span style="font-size:11px;color:var(--fg-faint);font-family:'Geist Mono',monospace;flex-shrink:0;"
                              x-text="(idx + 1) + ' / ' + faqs.length"></span>
                        <button @click="idx = (idx + 1) % faqs.length"
                                style="display:flex;align-items:center;gap:5px;font-size:12px;font-weight:500;
                                       color:var(--fg-dim);background:var(--bg-raised);border:1px solid var(--line);
                                       border-radius:8px;padding:7px 14px;cursor:pointer;transition:all .15s;white-space:nowrap;"
                                onmouseover="this.style.borderColor='var(--line-strong)';this.style.color='var(--fg)'"
                                onmouseout="this.style.borderColor='var(--line)';this.style.color='var(--fg-dim)'">
                            Next
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="9 18 15 12 9 6"/></svg>
                        </button>
                    </div>

                    {{-- Dots --}}
                    <div style="display:flex;justify-content:center;gap:6px;margin-top:16px;">
                        <template x-for="(_, i) in faqs" :key="i">
                            <button @click="idx = i"
                                    :style="'width:6px;height:6px;border-radius:50%;border:none;cursor:pointer;transition:all .2s;' +
                                            (i === idx ? 'background:var(--tan);transform:scale(1.3);' : 'background:var(--line-strong);')" ></button>
                        </template>
                    </div>
                </div>
            </div>
        </div>

    </div>{{-- end sidebar --}}
    </div>
</x-portal-layout>
