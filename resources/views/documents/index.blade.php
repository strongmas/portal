<x-portal-layout title="My Documents">
    <div style="max-width:760px;">

        {{-- Header --}}
        <div class="mb-6">
            <h1 style="font-size:22px;font-weight:700;color:var(--fg);margin:0 0 4px;">My Documents</h1>
            <p style="color:var(--fg-dim);font-size:14px;margin:0;">Upload documents sent to you by the company — offer letters, deeds of assignment, contracts, and more.</p>
        </div>

        @if(session('success'))
        <div style="background:#d1fae5;border:1px solid #6ee7b7;border-radius:10px;padding:12px 16px;margin-bottom:20px;font-size:13px;color:#065f46;">
            {{ session('success') }}
        </div>
        @endif

        @if($errors->any())
        <div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:10px;padding:12px 16px;margin-bottom:20px;font-size:13px;color:#991b1b;">
            {{ $errors->first() }}
        </div>
        @endif

        {{-- Upload Form --}}
        @if($ownerships->count())
        <div style="background:var(--bg-card);border:1px solid var(--line);border-radius:14px;padding:20px;margin-bottom:28px;" x-data="{ open: false }">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <div>
                    <p style="font-size:14px;font-weight:600;color:var(--fg);margin:0 0 2px;">Upload a Document</p>
                    <p style="font-size:12px;color:var(--fg-dim);margin:0;">Max 5 MB · PDF, JPG, or PNG</p>
                </div>
                <button type="button" @click="open = !open"
                        style="display:flex;align-items:center;gap:6px;font-size:13px;font-weight:600;color:var(--hi);background:none;border:none;cursor:pointer;padding:0;">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    <span x-text="open ? 'Cancel' : 'Upload Document'"></span>
                </button>
            </div>

            <div x-show="open" x-cloak style="margin-top:18px;padding-top:18px;border-top:1px solid var(--line);">
                <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data"
                      style="display:grid;gap:14px;">
                    @csrf

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                        {{-- Property --}}
                        <div>
                            <label class="eyebrow" style="display:block;margin-bottom:4px;">Property <span style="color:var(--danger);">*</span></label>
                            <select name="ownership_id" class="input-base" required>
                                <option value="">Select property…</option>
                                @foreach($ownerships as $ownership)
                                <option value="{{ $ownership->id }}">{{ $ownership->display_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Document Type --}}
                        <div>
                            <label class="eyebrow" style="display:block;margin-bottom:4px;">Document Type <span style="color:var(--danger);">*</span></label>
                            <select name="document_type" class="input-base" required>
                                <option value="">Select type…</option>
                                <option value="offer_letter">Offer Letter</option>
                                <option value="deed">Title Deed / Deed of Assignment</option>
                                <option value="contract">Contract / Agreement</option>
                                <option value="receipt">Receipt</option>
                                <option value="survey">Survey Plan</option>
                                <option value="approval">Approval / Permit</option>
                                <option value="report">Report</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>

                    {{-- Title --}}
                    <div>
                        <label class="eyebrow" style="display:block;margin-bottom:4px;">Document Title <span style="color:var(--danger);">*</span></label>
                        <input type="text" name="title" class="input-base" required placeholder="e.g. Signed Offer Letter" value="{{ old('title') }}">
                    </div>

                    {{-- File --}}
                    <div>
                        <label class="eyebrow" style="display:block;margin-bottom:4px;">File <span style="color:var(--danger);">*</span></label>
                        <input type="file" name="file" class="input-base" required accept=".pdf,.jpg,.jpeg,.png">
                    </div>

                    <div>
                        <button type="submit" class="btn-primary" style="font-size:13px;">Upload Document</button>
                    </div>
                </form>
            </div>
        </div>
        @endif

        {{-- Documents List --}}
        @forelse($documents as $ownershipId => $docs)
        @php
            $ownership = $docs->first()->ownership ?? $ownerships->firstWhere('id', $ownershipId);
            $property  = $docs->first()->property;
        @endphp
        <div style="margin-bottom:24px;">
            <p style="font-size:12px;font-weight:700;color:var(--fg-mute);text-transform:uppercase;letter-spacing:.06em;margin:0 0 10px;">
                {{ $property?->development_name ?? 'Property' }}
            </p>
            <div style="background:var(--bg-card);border:1px solid var(--line);border-radius:14px;overflow:hidden;">
                @foreach($docs as $doc)
                <div style="padding:16px 20px;border-bottom:1px solid var(--line);{{ $loop->last ? 'border-bottom:none;' : '' }}">
                    {{-- Flagged banner --}}
                    @if($doc->isFlagged())
                    <div style="background:#fef9c3;border:1px solid #fde047;border-radius:8px;padding:10px 14px;margin-bottom:12px;font-size:12px;color:#713f12;display:flex;gap:10px;align-items:flex-start;">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:1px;"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        <div>
                            <strong>This document requires re-upload.</strong>
                            @if($doc->flag_reason)
                            <span style="display:block;margin-top:2px;">{{ $doc->flag_reason }}</span>
                            @endif
                        </div>
                    </div>
                    @endif

                    <div style="display:flex;gap:14px;align-items:center;">
                        <div style="width:38px;height:38px;border-radius:10px;background:{{ $doc->isFlagged() ? 'rgba(234,179,8,0.12)' : 'var(--hi)18' }};display:grid;place-items:center;flex-shrink:0;">
                            <svg width="18" height="18" fill="none" stroke="{{ $doc->isFlagged() ? '#ca8a04' : 'var(--hi)' }}" stroke-width="1.5" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <p style="font-size:14px;font-weight:600;color:var(--fg);margin:0 0 2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $doc->title }}</p>
                            <p style="font-size:12px;color:var(--fg-dim);margin:0;">
                                {{ $doc->type_label }} · v{{ $doc->version }} · {{ $doc->created_at->format('d M Y') }}
                            </p>
                        </div>

                        {{-- Status badge --}}
                        <div style="flex-shrink:0;">
                            @if($doc->isFlagged())
                                <span class="chip" style="background:#fef9c3;color:#713f12;border:1px solid #fde047;font-size:10px;padding:2px 8px;">Flagged</span>
                            @elseif($doc->status === 'verified')
                                <span class="chip chip-ok" style="font-size:10px;padding:2px 8px;">Verified</span>
                            @else
                                <span class="chip chip-dark" style="font-size:10px;padding:2px 8px;">Under Review</span>
                            @endif
                        </div>

                        {{-- Actions --}}
                        <div style="display:flex;align-items:center;gap:12px;flex-shrink:0;">
                            <a href="{{ route('documents.download', $doc) }}"
                               style="display:flex;align-items:center;gap:5px;font-size:13px;font-weight:600;color:var(--hi);text-decoration:none;white-space:nowrap;">
                                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                Download
                            </a>
                            <form method="POST" action="{{ route('documents.destroy', $doc) }}" onsubmit="return confirm('Delete this document?')">
                                @csrf @method('DELETE')
                                <button type="submit" style="font-size:12px;color:var(--danger);background:none;border:none;cursor:pointer;padding:0;text-decoration:underline;text-underline-offset:3px;">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @empty
        <div style="text-align:center;padding:60px;color:var(--fg-dim);">
            <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 12px;opacity:0.3;"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            <p style="margin:0 0 4px;font-size:15px;font-weight:600;">No documents uploaded yet.</p>
            <p style="margin:0;font-size:13px;">Use the form above to upload documents sent to you by the company.</p>
        </div>
        @endforelse
    </div>
</x-portal-layout>
