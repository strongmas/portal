<x-portal-layout>
    <x-slot:title>Home</x-slot>

    {{-- $hasActive and $memberRoles are passed from DashboardController --}}

    {{-- Greeting --}}
    <div class="page-header mb-8">
        <div>
            <div class="eyebrow" style="margin-bottom:6px;">Dashboard</div>
            <h1 style="font-size:32px;font-weight:700;color:var(--fg);letter-spacing:-0.02em;">
                Hello, {{ explode(' ', auth()->user()->name)[0] }} 👋
            </h1>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('payments.history') }}" class="btn-ghost" style="padding:10px 16px;font-size:13px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Statement
            </a>
            @if($hasActive)
                <a href="{{ route('payments.create') }}" class="btn-primary" style="padding:10px 16px;font-size:13px;">
                    + Log payment
                </a>
            @endif
        </div>
    </div>

    <div class="dash-grid">

        {{-- ── Left column ──────────────────────────────────────────────── --}}
        <div class="flex flex-col gap-5">

            {{-- Hero: Next instalment --}}
            @if($nextDue)
                <div class="card card-hi" style="padding:28px;">
                    <div class="eyebrow eyebrow-tan" style="margin-bottom:12px;">Next instalment due</div>
                    <div class="hero-amount-row flex items-end justify-between gap-4" style="flex-wrap:wrap;gap:12px;">
                        <div>
                            <div style="font-family:'Geist Mono',monospace;font-size:clamp(22px,5vw,40px);font-weight:600;color:var(--fg);letter-spacing:-0.04em;">
                                {{ naira($nextDue->amount_kobo) }}
                            </div>
                            <div style="font-size:14px;color:var(--fg-mute);margin-top:6px;">
                                Due in <strong style="color:var(--tan);">{{ (int) now()->diffInDays($nextDue->due_date) }} days</strong>
                                · {{ $nextDue->due_date->format('j M Y') }}
                            </div>
                        </div>
                        <a href="{{ route('payments.create') }}" class="btn-primary" style="flex-shrink:0;">
                            Submit Payment →
                        </a>
                    </div>
                </div>
            @else
                <div class="card card-hi" style="padding:28px;">
                    @if($hasActive)
                        <div class="eyebrow" style="margin-bottom:8px;">Payments</div>
                        <p style="color:var(--fg-mute);font-size:14px;margin-top:4px;">No upcoming instalments scheduled.</p>
                        <a href="{{ route('payments.create') }}" class="btn-primary" style="margin-top:20px;display:inline-flex;">+ Log a payment</a>
                    @elseif($memberRoles->isNotEmpty() && $ownerships->whereNotIn('id', $memberRoles->keys())->isEmpty())
                        {{-- User only has shared (member) access, no owned properties --}}
                        <div class="eyebrow eyebrow-tan" style="margin-bottom:8px;">Shared Property Access</div>
                        <p style="color:var(--fg-mute);font-size:14px;margin-top:4px;">You have been granted access to {{ $ownerships->count() }} {{ Str::plural('property', $ownerships->count()) }} below.</p>
                        <a href="{{ route('project-updates.index') }}" class="btn-secondary" style="margin-top:20px;display:inline-flex;">View project updates →</a>
                    @else
                        <div class="eyebrow eyebrow-tan" style="margin-bottom:8px;">All properties paid</div>
                        <p style="color:var(--fg-mute);font-size:14px;margin-top:4px;">Congratulations! All your properties are fully paid off.</p>
                        <a href="{{ route('properties.index') }}" class="btn-secondary" style="margin-top:20px;display:inline-flex;">View portfolio →</a>
                    @endif
                </div>
            @endif

            {{-- Quick actions --}}
            <div class="flex items-center flex-wrap gap-2">
                <span class="chip chip-dark">Quick actions</span>
                @if($hasActive)
                    <a href="{{ route('payments.create') }}" class="chip" style="cursor:pointer;">+ Upload receipt</a>
                @endif
                <a href="{{ route('properties.index') }}" class="chip" style="cursor:pointer;">View properties</a>
                @if($hasActive)
                    <a href="{{ route('payments.history') }}" class="chip" style="cursor:pointer;">Payment history</a>
                @endif
                <a href="{{ route('project-updates.index') }}" class="chip" style="cursor:pointer;">Project updates</a>
                <a href="{{ route('support') }}" class="chip" style="cursor:pointer;">Get support</a>
            </div>

            {{-- Recent payments --}}
            <div class="card" style="padding:24px;">
                <div class="flex items-center justify-between" style="margin-bottom:20px;">
                    <h2 style="font-size:18px;font-weight:600;color:var(--fg);">Recent payment activity</h2>
                    <a href="{{ route('payments.history') }}" style="font-size:13px;color:var(--fg-dim);text-decoration:underline;text-underline-offset:3px;">See all →</a>
                </div>
                @forelse($recentPayments as $payment)
                    <x-payment-row :payment="$payment"/>
                @empty
                    <div style="padding:48px 0;text-align:center;color:var(--fg-dim);">
                        <div style="font-size:32px;margin-bottom:10px;">📋</div>
                        <p style="font-size:14px;color:var(--fg-mute);">No payments logged yet.</p>
                        @if($hasActive)
                            <a href="{{ route('payments.create') }}" class="btn-primary" style="margin-top:16px;display:inline-flex;font-size:13px;">Log your first payment →</a>
                        @endif
                    </div>
                @endforelse
            </div>

        </div>

        {{-- ── Right column ─────────────────────────────────────────────── --}}
        <div class="flex flex-col gap-5">

            {{-- Property cards --}}
            @forelse($ownerships as $ownership)
                @php
                    $memberRole      = $memberRoles->get($ownership->id);
                    $canSeeFinancials = $memberRole === null || in_array($memberRole, ['owner', 'financial']);
                @endphp
                <div class="card" style="padding:0;overflow:hidden;">
                    {{-- Cover photo header --}}
                    @if($ownership->property->cover_url)
                    <div style="position:relative;height:130px;overflow:hidden;">
                        <img src="{{ $ownership->property->cover_url }}"
                             style="width:100%;height:100%;object-fit:cover;display:block;"
                             alt="{{ $ownership->property->development_name }}">
                        <div style="position:absolute;inset:0;background:linear-gradient(to bottom,transparent 40%,rgba(0,0,0,0.55) 100%);"></div>
                        <div style="position:absolute;bottom:10px;left:14px;right:14px;display:flex;align-items:flex-end;justify-content:space-between;">
                            <div style="font-size:14px;font-weight:700;color:#fff;text-shadow:0 1px 4px rgba(0,0,0,0.5);">{{ $ownership->display_name }}</div>
                            @if($canSeeFinancials)
                                <span class="chip chip-tan" style="font-size:11px;">{{ number_format($ownership->percent_paid, 0) }}%</span>
                            @else
                                <span style="font-size:11px;font-weight:600;background:rgba(255,255,255,0.18);color:#fff;border:1px solid rgba(255,255,255,0.35);padding:3px 10px;border-radius:99px;">{{ ucfirst($memberRole) }} Access</span>
                            @endif
                        </div>
                    </div>
                    @else
                    <div style="padding:16px 20px 0;display:flex;align-items:center;justify-content:space-between;">
                        <h3 style="font-size:15px;font-weight:600;color:var(--fg);">{{ $ownership->display_name }}</h3>
                        @if($canSeeFinancials)
                            <span class="chip chip-tan">{{ number_format($ownership->percent_paid, 0) }}%</span>
                        @else
                            <span class="chip" style="font-size:11px;">{{ ucfirst($memberRole) }} Access</span>
                        @endif
                    </div>
                    @endif

                    <div style="padding:16px 20px 20px;">
                        @if($canSeeFinancials)
                            <div class="balance-card-body flex items-end gap-4">
                                <x-building-viz :percent="$ownership->percent_paid" variant="house" size="sm" :showLabel="false"/>
                                <div style="flex:1;width:100%;">
                                    <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:8px;">
                                        <span style="color:var(--fg-dim);">Total</span>
                                        <span style="font-weight:600;color:var(--fg);font-family:'Geist Mono',monospace;">{{ naira($ownership->agreed_price_kobo, true) }}</span>
                                    </div>
                                    <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:8px;">
                                        <span style="color:var(--fg-dim);">Paid</span>
                                        <span style="font-weight:600;color:var(--tan);font-family:'Geist Mono',monospace;">{{ naira($ownership->paid_kobo, true) }}</span>
                                    </div>
                                    <div style="display:flex;justify-content:space-between;font-size:13px;">
                                        <span style="color:var(--fg-dim);">Outstanding</span>
                                        <span style="font-weight:600;color:var(--fg);font-family:'Geist Mono',monospace;">{{ naira($ownership->outstanding_kobo, true) }}</span>
                                    </div>
                                </div>
                            </div>
                            <x-progress-bar :percent="$ownership->percent_paid" class="mt-3"/>

                            {{-- Market Insight snippet --}}
                            @if(isset($latestInsights[$ownership->property_id]))
                                @php $mi = $latestInsights[$ownership->property_id]; @endphp
                                <div style="margin-top:14px;padding:10px 12px;border-radius:8px;background:var(--bg-raised);border:1px solid var(--line);display:flex;align-items:flex-start;gap:10px;">
                                    @if($mi->change_type)
                                        @php
                                            $miColor = match($mi->change_type) {
                                                'increase' => 'var(--ok)',
                                                'decrease' => 'var(--danger)',
                                                default    => 'var(--fg-dim)',
                                            };
                                            $miBg = match($mi->change_type) {
                                                'increase' => 'var(--ok-bg)',
                                                'decrease' => 'var(--danger-bg)',
                                                default    => 'var(--bg-hover)',
                                            };
                                        @endphp
                                        <span style="flex-shrink:0;display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:6px;background:{{ $miBg }};font-size:14px;font-weight:700;color:{{ $miColor }};">
                                            {{ $mi->change_arrow }}
                                        </span>
                                    @endif
                                    <div style="flex:1;min-width:0;">
                                        <div style="font-size:12px;font-weight:600;color:var(--fg);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                            @if($mi->change_type && $mi->change_percent)
                                                <span style="color:{{ $miColor ?? 'var(--fg)' }};">{{ $mi->change_percent }}%</span>
                                                ·
                                            @endif
                                            {{ Str::limit($mi->title, 45) }}
                                        </div>
                                        <div style="font-size:11px;color:var(--fg-mute);margin-top:2px;">
                                            Market Update{{ $mi->period ? ' · ' . $mi->period : '' }}
                                            · <a href="{{ route('property-analytics.show', $ownership) }}" style="color:var(--tan);text-decoration:underline;text-underline-offset:2px;">See analytics →</a>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <a href="{{ route('properties.show', $ownership) }}"
                               style="font-size:12px;color:var(--fg-dim);text-decoration:underline;text-underline-offset:3px;margin-top:10px;display:inline-block;">
                                View balance →
                            </a>
                        @else
                            {{-- Viewer: show property links, not financial data --}}
                            <p style="font-size:13px;color:var(--fg-mute);margin:0 0 12px;">Shared property access</p>
                            <div style="display:flex;flex-direction:column;gap:8px;">
                                <a href="{{ route('project-updates.index') }}" style="font-size:13px;color:var(--fg-dim);text-decoration:underline;text-underline-offset:3px;">Project Updates →</a>
                                <a href="{{ route('documents.index') }}" style="font-size:13px;color:var(--fg-dim);text-decoration:underline;text-underline-offset:3px;">Documents →</a>
                                <a href="{{ route('property-analytics.show', $ownership) }}" style="font-size:13px;color:var(--fg-dim);text-decoration:underline;text-underline-offset:3px;">Value Analytics →</a>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="card" style="padding:32px;text-align:center;color:var(--fg-dim);">
                    <p style="font-size:14px;">No properties linked yet.</p>
                </div>
            @endforelse

            {{-- Ledger note --}}
            @if($hasActive)
            <div class="card card-brand" style="padding:20px;">
                <div class="eyebrow" style="margin-bottom:6px;">Transparency Ledger</div>
                <p style="font-size:13px;color:var(--fg-mute);">
                    Every payment is logged permanently.
                    <a href="{{ route('payments.history') }}" style="color:var(--tan);text-decoration:underline;text-underline-offset:3px;">View ledger →</a>
                </p>
            </div>
            @endif

        </div>
    </div>

</x-portal-layout>
