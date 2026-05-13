<x-layouts.admin title="Post Update">
    <div style="max-width:680px;">
        <a href="{{ route('admin.project-updates.index') }}" style="font-size:13px;color:var(--fg-dim);text-decoration:none;display:inline-flex;align-items:center;gap:4px;margin-bottom:20px;">← Back</a>

        <div class="flex items-center justify-between mb-6">
            <div>
                <div class="eyebrow" style="margin-bottom:4px;">{{ $property->development_name }}</div>
                <h1 style="font-size:24px;font-weight:700;color:var(--fg);letter-spacing:-0.02em;">Post Update</h1>
            </div>
        </div>

        <div class="card" style="padding:24px;">
            <form method="POST" action="{{ route('admin.project-updates.store', $property) }}" enctype="multipart/form-data">
                @csrf
                <div style="margin-bottom:16px;">
                    <label class="eyebrow" style="display:block;margin-bottom:6px;">Title <span style="color:var(--danger);">*</span></label>
                    <input type="text" name="title" value="{{ old('title') }}" required class="input-base" style="width:100%;">
                    @error('title')<p style="color:var(--danger);font-size:11px;margin-top:4px;">{{ $message }}</p>@enderror
                </div>

                <div style="margin-bottom:16px;">
                    <label class="eyebrow" style="display:block;margin-bottom:6px;">Update Body <span style="color:var(--danger);">*</span></label>
                    <textarea name="body" rows="6" required class="input-base" style="width:100%;resize:vertical;">{{ old('body') }}</textarea>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                    <div>
                        <label class="eyebrow" style="display:block;margin-bottom:6px;">Progress (%) <span style="color:var(--danger);">*</span></label>
                        <input type="number" name="progress_percentage" value="{{ old('progress_percentage', 0) }}" min="0" max="100" required class="input-base" style="width:100%;">
                    </div>
                    <div>
                        <label class="eyebrow" style="display:block;margin-bottom:6px;">Current Stage</label>
                        <input type="text" name="stage" value="{{ old('stage') }}" placeholder="e.g. Foundation" class="input-base" style="width:100%;">
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
                    <div>
                        <label class="eyebrow" style="display:block;margin-bottom:6px;">YouTube URL</label>
                        <input type="url" name="youtube_url" value="{{ old('youtube_url') }}" placeholder="https://youtube.com/watch?v=…" class="input-base" style="width:100%;">
                    </div>
                    <div>
                        <label class="eyebrow" style="display:block;margin-bottom:6px;">Est. Completion</label>
                        <input type="date" name="estimated_completion" value="{{ old('estimated_completion') }}" class="input-base" style="width:100%;">
                    </div>
                </div>

                {{-- Attach Documents (removable rows) --}}
                <div style="margin-bottom:20px;"
                     x-data="{ docs: [{ id: 0 }], next: 1 }">
                    <label class="eyebrow" style="display:block;margin-bottom:8px;">Attach Documents</label>

                    <template x-for="(doc, idx) in docs" :key="doc.id">
                        <div style="display:flex;gap:8px;margin-bottom:8px;align-items:center;">
                            <input type="text" :name="`doc_titles[${idx}]`" placeholder="Document title"
                                   class="input-base" style="flex:1;">
                            <input type="file" :name="`documents[${idx}]`" accept=".pdf,.jpg,.jpeg,.png"
                                   class="input-base" style="flex:1;font-size:12px;">
                            <button type="button" @click="docs.splice(idx, 1)"
                                    x-show="docs.length > 1"
                                    style="width:32px;height:32px;display:grid;place-items:center;border:1px solid var(--danger);border-radius:8px;background:transparent;color:var(--danger);cursor:pointer;font-size:16px;flex-shrink:0;"
                                    title="Remove">×</button>
                        </div>
                    </template>

                    <button type="button" @click="docs.push({ id: next++ })"
                            style="font-size:13px;color:var(--hi);font-weight:600;background:none;border:1px dashed var(--hi);border-radius:8px;cursor:pointer;padding:6px 14px;margin-top:2px;">
                        + Add document
                    </button>
                </div>

                <div style="padding-top:20px;border-top:1px solid var(--line);">
                    <button type="submit" class="btn-primary">Post Update &amp; Notify Buyers</button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.admin>
