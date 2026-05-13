<x-layouts.admin title="Support">

<div class="mb-6">
    <div class="eyebrow" style="margin-bottom:6px;">Support</div>
    <h1 style="font-size:28px;font-weight:700;color:var(--fg);letter-spacing:-0.02em;">Support</h1>
    <p style="font-size:13px;color:var(--fg-mute);margin-top:2px;">Buyer chat threads &amp; open tickets</p>
</div>

<div class="grid gap-6" style="grid-template-columns:1fr 360px;">

    {{-- Left: Chat threads --}}
    <div class="flex flex-col gap-4">
        <h2 style="font-size:15px;font-weight:600;color:var(--fg);">Buyer conversations</h2>

        @if($buyers->isEmpty())
            <div class="card" style="padding:60px;text-align:center;color:var(--fg-dim);font-size:14px;">No buyer messages yet.</div>
        @else
            @foreach($buyers as $buyer)
                @php $lastMsg = $buyer->supportMessages->first(); @endphp
                <div class="card" style="padding:0;overflow:hidden;"
                     x-data="{
                        open: false,
                        replyText: '',
                        sending: false,
                        thread: {{ \Illuminate\Support\Js::from(\App\Models\SupportMessage::where('user_id', $buyer->id)->orderBy('created_at')->take(20)->get()->map(fn($m) => ['sender'=>$m->sender,'body'=>$m->body,'time'=>$m->created_at->format('H:i')])) }},
                        async sendReply() {
                            if (!this.replyText.trim() || this.sending) return;
                            const body = this.replyText;
                            this.replyText = '';
                            this.sending = true;
                            try {
                                await fetch('{{ route('admin.support.reply', $buyer) }}', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                                    body: JSON.stringify({ body })
                                });
                                this.thread.push({ sender: 'agent', body, time: new Date().toLocaleTimeString('en-GB', {hour:'2-digit',minute:'2-digit'}) });
                                this.$nextTick(() => this.$refs.msgEnd?.scrollIntoView({ behavior: 'smooth' }));
                            } catch(e) {}
                            this.sending = false;
                        }
                     }">

                    {{-- Thread header --}}
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;cursor:pointer;transition:background var(--dur-fast);"
                         @click="open = !open"
                         onmouseover="this.style.background='var(--bg-raised)'" onmouseout="this.style.background='transparent'">
                        <div class="flex items-center gap-3">
                            <div class="avatar" style="width:36px;height:36px;font-size:11px;flex-shrink:0;">{{ $buyer->initials }}</div>
                            <div>
                                <div style="font-weight:500;color:var(--fg);font-size:14px;">{{ $buyer->name }}</div>
                                @if($lastMsg)
                                    <div style="font-size:12px;color:var(--fg-dim);margin-top:2px;">
                                        {{ Str::limit($lastMsg->body, 60) }} · {{ $lastMsg->created_at->diffForHumans() }}
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <a href="{{ route('admin.buyers.show', $buyer) }}" @click.stop
                               style="font-size:12px;color:var(--tan);text-decoration:underline;text-underline-offset:3px;">profile</a>
                            <span style="color:var(--fg-dim);font-size:12px;" x-text="open ? '▲' : '▼'">▼</span>
                        </div>
                    </div>

                    {{-- Thread messages + reply --}}
                    <div x-show="open" x-cloak style="border-top:1px solid var(--line);">
                        <div style="padding:16px 20px;display:flex;flex-direction:column;gap:8px;max-height:260px;overflow-y:auto;background:var(--bg-raised);">
                            <template x-for="(msg, i) in thread" :key="i">
                                <div :style="msg.sender === 'buyer' ? 'align-self:flex-end;' : 'align-self:flex-start;'"
                                     style="display:flex;flex-direction:column;max-width:72%;">
                                    <div :style="msg.sender === 'buyer'
                                            ? 'background:var(--warn-bg);border:1px solid rgba(245,194,107,0.3);border-bottom-right-radius:4px;'
                                            : 'background:var(--bg-card);border:1px solid var(--line);border-bottom-left-radius:4px;'"
                                         style="padding:8px 12px;border-radius:10px;font-size:13px;color:var(--fg);line-height:1.5;">
                                        <span x-text="msg.body"></span>
                                    </div>
                                    <div :style="msg.sender === 'buyer' ? 'text-align:right;' : 'text-align:left;'"
                                         style="font-size:11px;color:var(--fg-dim);margin-top:3px;padding:0 3px;">
                                        <span x-text="msg.time"></span> · <span x-text="msg.sender"></span>
                                    </div>
                                </div>
                            </template>
                            <div x-ref="msgEnd"></div>
                        </div>

                        <div style="padding:12px 20px;border-top:1px solid var(--line);display:flex;align-items:center;gap:10px;">
                            <input type="text" x-model="replyText" placeholder="Type reply…" class="input-base" style="flex:1;font-size:13px;"
                                   @keyup.enter="sendReply()"/>
                            <button type="button" class="btn-primary" style="font-size:13px;flex-shrink:0;"
                                    :disabled="sending" :style="sending ? 'opacity:0.6;' : ''"
                                    @click="sendReply()">
                                <span x-show="!sending">Send</span>
                                <span x-show="sending">...</span>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    {{-- Right: Tickets summary --}}
    <div class="flex flex-col gap-4">
        <h2 style="font-size:15px;font-weight:600;color:var(--fg);">Complaint tickets</h2>

        <a href="{{ route('admin.tickets.index') }}"
           class="card" style="display:flex;align-items:center;gap:16px;padding:20px;text-decoration:none;transition:border-color var(--dur-fast);"
           onmouseover="this.style.borderColor='var(--line-tan)'" onmouseout="this.style.borderColor='var(--line)'">
            <div style="width:48px;height:48px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:18px;flex-shrink:0;
                {{ $openCount > 0 ? 'background:var(--danger-bg);border:1px solid rgba(244,114,114,0.3);color:var(--danger);' : 'background:var(--ok-bg);border:1px solid rgba(93,217,167,0.3);color:var(--ok);' }}">
                {{ $openCount > 0 ? $openCount : '✓' }}
            </div>
            <div>
                <div style="font-weight:600;color:var(--fg);font-size:14px;">{{ $openCount > 0 ? $openCount . ' open ' . Str::plural('ticket', $openCount) : 'All tickets resolved' }}</div>
                <div style="font-size:12px;color:var(--fg-dim);margin-top:2px;">Click to manage all tickets →</div>
            </div>
        </a>

        <div class="card" style="padding:18px;">
            <h3 style="font-size:13px;font-weight:600;color:var(--fg);margin-bottom:12px;">About this section</h3>
            <div style="font-size:13px;color:var(--fg-mute);display:flex;flex-direction:column;gap:8px;line-height:1.6;">
                <p>The <strong style="color:var(--fg);">Chat</strong> tab shows buyer message threads — use it for informal back-and-forth.</p>
                <p>Formal complaints submitted via "Raise a complaint" appear in the <strong style="color:var(--fg);">Tickets</strong> section with a case number.</p>
                <p>From the ticket detail page you can reply, update the status, and view the full conversation history.</p>
            </div>
            <div style="margin-top:16px;">
                <a href="{{ route('admin.tickets.index') }}" style="font-size:13px;color:var(--tan);text-decoration:underline;text-underline-offset:3px;">Go to tickets →</a>
            </div>
        </div>
    </div>

</div>

</x-layouts.admin>
