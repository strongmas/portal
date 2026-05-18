<x-layouts.admin title="Edit · {{ $property->development_name }}">

<div class="flex items-center gap-2 mb-6" style="font-size:13px;color:var(--fg-dim);">
    <a href="{{ route('admin.properties.index') }}" style="color:var(--fg-dim);text-decoration:none;" onmouseover="this.style.color='var(--tan)'" onmouseout="this.style.color='var(--fg-dim)'">Properties</a>
    <span>/</span>
    <a href="{{ route('admin.properties.show', $property) }}" style="color:var(--fg-dim);text-decoration:none;" onmouseover="this.style.color='var(--tan)'" onmouseout="this.style.color='var(--fg-dim)'">{{ $property->development_name }}</a>
    <span>/</span>
    <span style="color:var(--fg);">Edit</span>
</div>

<div style="max-width:640px;">
    <h1 style="font-size:24px;font-weight:700;color:var(--fg);letter-spacing:-0.02em;margin-bottom:24px;">Edit development</h1>

    @if($errors->any())
        <div style="background:var(--danger-bg);border:1px solid rgba(244,114,114,0.3);border-radius:8px;padding:12px 16px;margin-bottom:20px;font-size:13px;color:var(--danger);">
            <ul style="list-style:disc;padding-left:18px;display:flex;flex-direction:column;gap:2px;">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.properties.update', $property) }}" class="card" style="padding:0;overflow:hidden;">
        @csrf @method('PATCH')

        <div style="padding:24px;display:flex;flex-direction:column;gap:16px;">
            <div>
                <label style="display:block;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);margin-bottom:6px;">Development name</label>
                <input type="text" name="development_name" value="{{ old('development_name', $property->development_name) }}" required
                       class="input-base" style="width:100%;"/>
            </div>
            <div>
                <label style="display:block;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);margin-bottom:6px;">Property code</label>
                <input type="text" name="code" value="{{ old('code', $property->code) }}" required
                       class="input-base" style="width:100%;font-family:'Geist Mono',monospace;font-size:12px;"/>
            </div>
            <div>
                <label style="display:block;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);margin-bottom:6px;">Full address</label>
                <textarea name="address" required rows="2"
                          class="input-base" style="width:100%;resize:none;">{{ old('address', $property->address) }}</textarea>
            </div>
            <div>
                <label style="display:block;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);margin-bottom:6px;">Description (shown to buyers)</label>
                <textarea name="description" rows="4"
                          class="input-base" style="width:100%;resize:vertical;">{{ old('description', $property->description) }}</textarea>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div>
                    <label style="display:block;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);margin-bottom:6px;">Latitude</label>
                    <input type="number" name="lat" step="0.0000001" value="{{ old('lat', $property->lat) }}"
                           class="input-base" style="width:100%;" placeholder="e.g. 6.5244"/>
                </div>
                <div>
                    <label style="display:block;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);margin-bottom:6px;">Longitude</label>
                    <input type="number" name="lng" step="0.0000001" value="{{ old('lng', $property->lng) }}"
                           class="input-base" style="width:100%;" placeholder="e.g. 3.3792"/>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;align-items:center;">
                <div>
                    <label style="display:block;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);margin-bottom:6px;">Project status</label>
                    <select name="project_status" class="input-base" style="width:100%;">
                        @foreach(['planning' => 'Planning', 'foundation' => 'Foundation', 'construction' => 'Construction', 'finishing' => 'Finishing', 'completed' => 'Completed'] as $val => $label)
                            <option value="{{ $val }}" {{ old('project_status', $property->project_status) === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="display:flex;align-items:center;gap:10px;padding-top:22px;">
                    <input type="hidden" name="is_off_plan" value="0">
                    <input type="checkbox" name="is_off_plan" id="is_off_plan" value="1"
                           {{ old('is_off_plan', $property->is_off_plan) ? 'checked' : '' }}
                           style="width:16px;height:16px;accent-color:var(--hi);">
                    <label for="is_off_plan" style="font-size:14px;color:var(--fg);cursor:pointer;">Off-plan property</label>
                </div>
            </div>
        </div>

        <div class="form-footer" style="padding:16px 24px;border-top:1px solid var(--line);background:var(--bg-raised);">
            <a href="{{ route('admin.properties.show', $property) }}" style="font-size:13px;color:var(--fg-dim);text-decoration:none;" onmouseover="this.style.color='var(--fg)'" onmouseout="this.style.color='var(--fg-dim)'">Cancel</a>
            <button type="submit" class="btn-primary" style="font-size:13px;">Save changes</button>
        </div>
    </form>

    {{-- Property Documents (admin-uploaded) --}}
    @if(session('success'))
    <div style="background:#d1fae5;border:1px solid #6ee7b7;border-radius:10px;padding:12px 16px;margin-top:20px;font-size:13px;color:#065f46;">
        {{ session('success') }}
    </div>
    @endif

    <div class="card" style="padding:0;overflow:hidden;margin-top:24px;">
        <div style="padding:20px 24px;border-bottom:1px solid var(--line);">
            <h2 style="font-size:15px;font-weight:600;color:var(--fg);margin:0 0 4px;">Property Documents</h2>
            <p style="font-size:12px;color:var(--fg-dim);margin:0;">Upload documents visible to all buyers and interested buyers of this property.</p>
        </div>

        {{-- Upload form --}}
        <div style="padding:20px 24px;border-bottom:1px solid var(--line);" x-data="{ open: false }">
            <button type="button" @click="open = !open"
                    style="display:flex;align-items:center;gap:6px;font-size:13px;font-weight:600;color:var(--hi);background:none;border:none;cursor:pointer;padding:0;">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                <span x-text="open ? 'Cancel' : 'Upload Document'"></span>
            </button>

            <div x-show="open" x-cloak style="margin-top:16px;">
                <form method="POST" action="{{ route('admin.property-documents.store', $property) }}"
                      enctype="multipart/form-data" style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                    @csrf
                    <div>
                        <label style="display:block;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);margin-bottom:6px;">Document title <span style="color:var(--danger);">*</span></label>
                        <input type="text" name="title" required class="input-base" style="width:100%;"
                               placeholder="e.g. Deed of Assignment"/>
                    </div>
                    <div>
                        <label style="display:block;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);margin-bottom:6px;">Document type <span style="color:var(--danger);">*</span></label>
                        <select name="document_type" required class="input-base" style="width:100%;">
                            <option value="">— select type —</option>
                            <option value="offer_letter">Offer Letter</option>
                            <option value="deed">Title Deed / Deed of Assignment</option>
                            <option value="contract">Contract / Agreement</option>
                            <option value="survey">Survey Plan</option>
                            <option value="approval">Approval / Permit</option>
                            <option value="report">Report</option>
                            <option value="receipt">Receipt</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div style="grid-column:span 2;display:flex;align-items:center;gap:10px;">
                        <input type="hidden" name="is_buyer_visible" value="0">
                        <input type="checkbox" name="is_buyer_visible" id="doc_buyer_visible" value="1" checked
                               style="width:15px;height:15px;accent-color:var(--hi);cursor:pointer;">
                        <label for="doc_buyer_visible" style="font-size:13px;color:var(--fg);cursor:pointer;">Visible to buyers &amp; interested buyers</label>
                    </div>
                    <div style="grid-column:span 2;">
                        <label style="display:block;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);margin-bottom:6px;">File <span style="color:var(--danger);">*</span></label>
                        <input type="file" name="file" required accept=".pdf,.jpg,.jpeg,.png"
                               class="input-base" style="width:100%;padding:8px 12px;"/>
                        <p style="font-size:11px;color:var(--fg-dim);margin-top:4px;">PDF · JPG · PNG · max 10 MB</p>
                    </div>
                    <div style="grid-column:span 2;display:flex;justify-content:flex-end;">
                        <button type="submit" class="btn-primary" style="font-size:13px;">Upload document →</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Existing documents --}}
        @forelse($property->documents as $doc)
        <div style="padding:14px 24px;border-top:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;gap:12px;"
             onmouseover="this.style.background='var(--bg-raised)'" onmouseout="this.style.background='transparent'">
            <div style="flex:1;min-width:0;">
                <p style="font-size:13px;font-weight:600;color:var(--fg);margin:0 0 2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $doc->title }}</p>
                <p style="font-size:11px;color:var(--fg-dim);margin:0;">{{ $doc->type_label }} · v{{ $doc->version }} · {{ $doc->created_at->format('d M Y') }}</p>
            </div>
            <div style="display:flex;align-items:center;gap:14px;flex-shrink:0;">
                @if($doc->is_buyer_visible)
                    <span class="chip chip-ok" style="font-size:10px;padding:2px 8px;">Buyer visible</span>
                @else
                    <span class="chip chip-dark" style="font-size:10px;padding:2px 8px;">Hidden</span>
                @endif
                <a href="{{ route('admin.documents.download', $doc) }}"
                   style="font-size:12px;font-weight:600;color:var(--hi);text-decoration:none;">Download</a>
                <form method="POST" action="{{ route('admin.documents.destroy', $doc) }}"
                      onsubmit="return confirm('Delete this document?')" style="display:inline;">
                    @csrf @method('DELETE')
                    <button type="submit" style="font-size:12px;color:var(--danger);background:none;border:none;cursor:pointer;text-decoration:underline;text-underline-offset:3px;">Delete</button>
                </form>
            </div>
        </div>
        @empty
        <div style="padding:32px 24px;text-align:center;color:var(--fg-dim);">
            <p style="font-size:13px;margin:0;">No documents uploaded for this property yet.</p>
        </div>
        @endforelse
    </div>
</div>

</x-layouts.admin>
