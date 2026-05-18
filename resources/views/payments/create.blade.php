<x-portal-layout>
    <x-slot:title>Log Payment</x-slot>

    <div style="margin-bottom:24px;">
        <div class="eyebrow" style="margin-bottom:6px;">New Payment</div>
        <h1 style="font-size:32px;font-weight:700;color:var(--fg);letter-spacing:-0.02em;">Log a payment</h1>
        <p style="font-size:14px;color:var(--fg-mute);margin-top:4px;">Fill in the details — the receipt preview updates live on the right.</p>
    </div>

    @php
        $outstandingMap = $ownerships->mapWithKeys(fn($o) => [$o->id => $o->outstanding_kobo])->toJson();
        $displayNameMap = $ownerships->mapWithKeys(fn($o) => [$o->id => $o->display_name])->toJson();
    @endphp

    <div x-data="{
        selectedOwnershipId: {{ $selectedOwnership?->id ?? 'null' }},
        outstandingMap: {{ $outstandingMap }},
        displayNameMap: {{ $displayNameMap }},
        propertyName: '{{ addslashes($selectedOwnership?->display_name ?? '') }}',
        buyerName: '{{ addslashes(auth()->user()->name) }}',
        amount: '',
        amountDisplay: '',
        datePaid: '',
        method: 'Bank transfer',
        submitting: false,
        bank: '',
        txnRef: '',
        proofName: '',
        get outstanding() {
            return this.outstandingMap[this.selectedOwnershipId] ?? 0;
        },
        get outstandingDisplay() {
            if (!this.outstanding) return '₦ 0';
            return '₦ ' + parseInt(this.outstanding / 100).toLocaleString();
        },
        get amountKobo() {
            return this.amount ? parseInt(this.amount) * 100 : 0;
        },
        get isOverpay() {
            return this.amountKobo > 0 && this.amountKobo > this.outstanding;
        },
        formatAmount(val) {
            let num = val.replace(/[^0-9]/g, '');
            this.amount = num;
            this.amountDisplay = num ? '₦ ' + parseInt(num).toLocaleString() : '';
        },
        handleProof(event) {
            const file = event.target.files[0];
            if (file) this.proofName = file.name;
        },
        updateProperty(select) {
            this.selectedOwnershipId = parseInt(select.value);
            this.propertyName = this.displayNameMap[this.selectedOwnershipId] || '';
        }
    }" style="display:grid;gap:24px;grid-template-columns:1.1fr 0.9fr;">

        {{-- ── Left: Form ─────────────────────────────────────────────────── --}}
        <div style="display:flex;flex-direction:column;gap:16px;">
            <form method="POST" action="{{ route('payments.store') }}" enctype="multipart/form-data" id="payment-form"
                  @submit="if (submitting) { $event.preventDefault(); return; } submitting = true;">
                @csrf

                {{-- Property --}}
                <div class="card" style="padding:20px;">
                    <div class="eyebrow" style="margin-bottom:12px;">Property</div>
                    <select name="ownership_id" required class="input-base"
                            @change="updateProperty($el)" x-init="propertyName = $el.options[$el.selectedIndex].text">
                        @foreach($ownerships as $o)
                            <option value="{{ $o->id }}" {{ $selectedOwnership?->id === $o->id ? 'selected' : '' }}>
                                {{ $o->display_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('ownership_id')
                        <p style="color:var(--danger);font-size:12px;margin-top:6px;">{{ $message }}</p>
                    @enderror

                    {{-- Outstanding balance indicator --}}
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-top:12px;padding:10px 14px;background:var(--bg-raised);border-radius:var(--r-md);border:1px solid var(--line);">
                        <span style="font-size:12px;color:var(--fg-dim);">Outstanding balance</span>
                        <span style="font-family:'Geist Mono',monospace;font-weight:600;color:var(--tan);font-size:14px;" x-text="outstandingDisplay"></span>
                    </div>
                </div>

                {{-- Payment details --}}
                <div class="card" style="padding:20px;">
                    <div class="eyebrow" style="margin-bottom:16px;">Payment details</div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                        <div style="grid-column:span 2;">
                            <label style="display:block;font-family:'Geist Mono',monospace;font-size:11px;text-transform:uppercase;letter-spacing:0.1em;color:var(--fg-mute);margin-bottom:8px;">Amount (₦)</label>
                            <input type="text" name="amount" required class="input-base"
                                   placeholder="2,500,000"
                                   :style="isOverpay ? 'border-color:var(--danger);' : ''"
                                   @input="formatAmount($event.target.value)"
                                   :value="amount ? parseInt(amount).toLocaleString() : ''"
                                   value="{{ old('amount') }}"/>
                            {{-- Overpay warning --}}
                            <div x-show="isOverpay" x-cloak
                                 style="display:flex;align-items:center;gap:8px;margin-top:8px;padding:10px 12px;background:var(--danger-bg);border:1px solid rgba(244,114,114,0.3);border-radius:var(--r-md);">
                                <span style="color:var(--danger);font-size:14px;flex-shrink:0;">⚠</span>
                                <span style="font-size:12px;color:var(--danger);">
                                    Amount exceeds the outstanding balance of <strong x-text="outstandingDisplay"></strong>. You cannot overpay.
                                </span>
                            </div>
                            @error('amount')<p style="color:var(--danger);font-size:12px;margin-top:6px;">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label style="display:block;font-family:'Geist Mono',monospace;font-size:11px;text-transform:uppercase;letter-spacing:0.1em;color:var(--fg-mute);margin-bottom:8px;">Date paid</label>
                            <input type="date" name="paid_on" required class="input-base"
                                   x-model="datePaid" max="{{ date('Y-m-d') }}"
                                   value="{{ old('paid_on') }}"/>
                            @error('paid_on')<p style="color:var(--danger);font-size:12px;margin-top:6px;">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label style="display:block;font-family:'Geist Mono',monospace;font-size:11px;text-transform:uppercase;letter-spacing:0.1em;color:var(--fg-mute);margin-bottom:8px;">Payment method</label>
                            <select name="method" class="input-base" x-model="method">
                                <option>Bank transfer</option>
                                <option>Cash deposit</option>
                                <option>Online transfer</option>
                                <option>Cheque</option>
                            </select>
                        </div>
                        <div>
                            <label style="display:block;font-family:'Geist Mono',monospace;font-size:11px;text-transform:uppercase;letter-spacing:0.1em;color:var(--fg-mute);margin-bottom:8px;">Bank / channel</label>
                            <input type="text" name="bank" class="input-base" placeholder="GTBank, Zenith..."
                                   x-model="bank" value="{{ old('bank') }}"/>
                        </div>
                        <div style="grid-column:span 2;">
                            <label style="display:block;font-family:'Geist Mono',monospace;font-size:11px;text-transform:uppercase;letter-spacing:0.1em;color:var(--fg-mute);margin-bottom:8px;">Transaction reference</label>
                            <input type="text" name="reference" required class="input-base"
                                   placeholder="TRX/2026/04/8821-A" x-model="txnRef"
                                   value="{{ old('reference') }}"/>
                            @error('reference')<p style="color:var(--danger);font-size:12px;margin-top:6px;">{{ $message }}</p>@enderror
                        </div>
                        <div style="grid-column:span 2;">
                            <label style="display:block;font-family:'Geist Mono',monospace;font-size:11px;text-transform:uppercase;letter-spacing:0.1em;color:var(--fg-mute);margin-bottom:8px;">Notes (optional)</label>
                            <input type="text" name="notes" class="input-base" placeholder="anything we should know"
                                   value="{{ old('notes') }}"/>
                        </div>
                    </div>
                </div>

                {{-- Upload proof --}}
                <div class="card" style="padding:20px;">
                    <div class="eyebrow" style="margin-bottom:12px;">Upload proof of payment <span style="color:var(--danger);">(required)</span></div>
                    <label style="display:block;cursor:pointer;">
                        <div style="border:2px dashed var(--line-strong);border-radius:var(--r-lg);padding:40px;text-align:center;transition:all var(--dur-fast);"
                             :style="proofName ? 'border-color:var(--tan);background:var(--info-bg);' : ''">
                            <div style="width:48px;height:48px;border-radius:12px;background:var(--bg-raised);border:1px solid var(--line-strong);display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--tan)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                            </div>
                            <p style="font-size:14px;color:var(--fg-mute);" x-text="proofName || 'Drop your file here, or click to browse'"></p>
                            <p style="font-family:'Geist Mono',monospace;font-size:11px;color:var(--fg-dim);margin-top:6px;">PDF · JPG · PNG · max 5 MB</p>
                        </div>
                        <input type="file" name="proof" accept=".pdf,.jpg,.jpeg,.png" required
                               class="hidden" @change="handleProof($event)"/>
                    </label>
                    @error('proof')<p style="color:var(--danger);font-size:12px;margin-top:6px;">{{ $message }}</p>@enderror
                </div>

                {{-- Submit --}}
                <div class="page-header" style="gap:16px;flex-wrap:wrap;">
                    <label style="display:flex;align-items:center;gap:10px;font-size:14px;color:var(--fg-mute);cursor:pointer;">
                        <input type="checkbox" required style="width:16px;height:16px;border-radius:4px;accent-color:var(--tan);border:1px solid var(--line-strong);">
                        I confirm these details are accurate
                    </label>
                    <div class="flex gap-3">
                        <a href="{{ route('home') }}" class="btn-ghost">Cancel</a>
                        <button type="submit" class="btn-primary" style="padding:12px 24px;font-size:15px;"
                                :disabled="isOverpay || submitting"
                                :style="(isOverpay || submitting) ? 'opacity:0.5;cursor:not-allowed;' : ''"
                                x-text="submitting ? 'Submitting…' : 'Log payment →'">
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- ── Right: Live receipt preview ────────────────────────────────── --}}
        <div style="position:sticky;top:80px;align-self:start;">
            <div class="eyebrow" style="margin-bottom:12px;">Live preview</div>
            <div class="receipt">
                <div class="flex items-center justify-between" style="margin-bottom:16px;">
                    <h3 style="font-size:15px;font-weight:600;color:var(--fg);">Strongmas · Payment Record</h3>
                    <span class="chip" style="border-style:dashed;">draft</span>
                </div>
                <div class="divider"></div>
                <div style="display:flex;flex-direction:column;gap:10px;font-size:13px;margin-top:16px;">
                    <div class="flex justify-between"><span style="color:var(--fg-dim);">Property</span><strong style="color:var(--fg);" x-text="propertyName || '—'"></strong></div>
                    <div class="flex justify-between"><span style="color:var(--fg-dim);">Buyer</span><strong style="color:var(--fg);">{{ auth()->user()->name }}</strong></div>
                    <div class="flex justify-between"><span style="color:var(--fg-dim);">Date paid</span><strong style="color:var(--fg);" x-text="datePaid ? new Date(datePaid).toLocaleDateString('en-GB',{day:'numeric',month:'short',year:'numeric'}) : '—'"></strong></div>
                    <div class="flex justify-between"><span style="color:var(--fg-dim);">Method</span><strong style="color:var(--fg);" x-text="method + (bank ? ' · ' + bank : '')"></strong></div>
                    <div class="flex justify-between"><span style="color:var(--fg-dim);">Reference</span><span style="font-family:'Geist Mono',monospace;font-size:11px;color:var(--fg-mute);" x-text="txnRef || '—'"></span></div>
                    <div class="flex justify-between">
                        <span style="color:var(--fg-dim);">Outstanding</span>
                        <strong style="color:var(--tan);font-family:'Geist Mono',monospace;" x-text="outstandingDisplay"></strong>
                    </div>
                </div>
                <div class="divider"></div>
                <div class="flex items-center justify-between">
                    <span class="eyebrow">Amount</span>
                    <span style="font-family:'Geist Mono',monospace;font-size:22px;font-weight:600;"
                          :style="isOverpay ? 'color:var(--danger);' : 'color:var(--tan);'"
                          x-text="amountDisplay || '₦ —'"></span>
                </div>
                <div x-show="isOverpay" x-cloak style="margin-top:10px;padding:8px 12px;background:var(--danger-bg);border-radius:var(--r-md);font-size:12px;color:var(--danger);text-align:center;">
                    Overpayment — reduce the amount
                </div>
                <div class="divider"></div>
                <div class="flex items-center gap-3">
                    <div style="width:48px;height:48px;border-radius:6px;flex-shrink:0;background:repeating-linear-gradient(45deg,var(--bg-raised) 0 2px,var(--bg-hover) 2px 5px);border:1px solid var(--line);"></div>
                    <p style="font-size:11px;color:var(--fg-dim);line-height:1.5;">Unique receipt ID issued on submit.<br/>Logged to immutable transparency ledger.</p>
                </div>
            </div>
        </div>

    </div>
</x-portal-layout>
