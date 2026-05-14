<x-layouts.admin title="Milestones">

<div class="flex items-center gap-2 mb-6" style="font-size:13px;color:var(--fg-dim);">
    <a href="{{ route('admin.project-updates.index') }}" style="color:var(--fg-dim);text-decoration:none;" onmouseover="this.style.color='var(--tan)'" onmouseout="this.style.color='var(--fg-dim)'">Project Updates</a>
    <span>/</span>
    <span style="color:var(--fg);">Milestones</span>
</div>

<div style="max-width:720px;">
    <div class="mb-6">
        <div class="eyebrow" style="margin-bottom:4px;">{{ $property->development_name }}</div>
        <h1 style="font-size:24px;font-weight:700;color:var(--fg);letter-spacing:-0.02em;">Project Milestones</h1>
    </div>

    {{-- Add milestone --}}
    <div class="card" style="padding:24px;margin-bottom:20px;">
        <h2 style="font-size:14px;font-weight:600;color:var(--fg);margin-bottom:16px;">Add Milestone</h2>
        <form method="POST" action="{{ route('admin.milestones.store', $property) }}" enctype="multipart/form-data">
            @csrf
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
                <div style="grid-column:span 2;">
                    <label class="eyebrow" style="display:block;margin-bottom:6px;">Title <span style="color:var(--danger);">*</span></label>
                    <input type="text" name="title" value="{{ old('title') }}" required class="input-base" style="width:100%;" placeholder="e.g. Foundation Complete">
                </div>
                <div>
                    <label class="eyebrow" style="display:block;margin-bottom:6px;">Planned Date <span style="color:var(--danger);">*</span></label>
                    <input type="date" name="planned_date" value="{{ old('planned_date') }}" required class="input-base" style="width:100%;">
                </div>
                <div>
                    <label class="eyebrow" style="display:block;margin-bottom:6px;">Description</label>
                    <input type="text" name="description" value="{{ old('description') }}" class="input-base" style="width:100%;" placeholder="Optional note">
                </div>
                <div style="grid-column:span 2;">
                    <label class="eyebrow" style="display:block;margin-bottom:6px;">Attach Photo (optional)</label>
                    <input type="file" name="image" accept="image/png,image/jpeg,image/jpg" class="input-base" style="width:100%;font-size:13px;">
                    <p style="font-size:11px;color:var(--fg-mute);margin-top:4px;">JPG or PNG · max 4MB</p>
                </div>
            </div>
            <button type="submit" class="btn-primary">Add Milestone</button>
        </form>
    </div>

    {{-- Existing milestones --}}
    <div class="card" style="padding:0;overflow:hidden;">
        @forelse($milestones as $milestone)
        <form method="POST" action="{{ route('admin.milestones.update', [$property, $milestone]) }}"
              enctype="multipart/form-data"
              style="padding:20px;border-bottom:1px solid var(--line);">
            @csrf @method('PATCH')

            {{-- Milestone image preview --}}
            @if($milestone->image_path)
            <div style="margin-bottom:14px;">
                <img src="{{ \Storage::disk('public')->url($milestone->image_path) }}"
                     style="width:100%;max-height:220px;object-fit:cover;border-radius:10px;border:1px solid var(--line);"
                     alt="{{ $milestone->title }}">
            </div>
            @endif

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
                <div style="grid-column:span 2;">
                    <label class="eyebrow" style="display:block;margin-bottom:6px;">Title</label>
                    <input type="text" name="title" value="{{ $milestone->title }}" required class="input-base" style="width:100%;">
                </div>
                <div>
                    <label class="eyebrow" style="display:block;margin-bottom:6px;">Planned Date</label>
                    <input type="date" name="planned_date" value="{{ $milestone->planned_date->format('Y-m-d') }}" required class="input-base" style="width:100%;">
                </div>
                <div>
                    <label class="eyebrow" style="display:block;margin-bottom:6px;">Actual Date</label>
                    <input type="date" name="actual_date" value="{{ $milestone->actual_date?->format('Y-m-d') }}" class="input-base" style="width:100%;">
                </div>
                <div>
                    <label class="eyebrow" style="display:block;margin-bottom:6px;">Status</label>
                    <select name="status" class="input-base" style="width:100%;">
                        @foreach(['pending' => 'Pending', 'in_progress' => 'In Progress', 'completed' => 'Completed', 'delayed' => 'Delayed'] as $val => $label)
                        <option value="{{ $val }}" {{ $milestone->status === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="eyebrow" style="display:block;margin-bottom:6px;">Replace Photo</label>
                    <input type="file" name="image" accept="image/png,image/jpeg,image/jpg" class="input-base" style="width:100%;font-size:12px;">
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="btn-primary" style="font-size:13px;">Update</button>
                <form method="POST" action="{{ route('admin.milestones.destroy', [$property, $milestone]) }}"
                      onsubmit="return confirm('Delete this milestone?')" style="display:inline;">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn-ghost" style="color:var(--danger);font-size:13px;">Delete</button>
                </form>
            </div>
        </form>
        @empty
        <div style="padding:60px;text-align:center;color:var(--fg-dim);font-size:13px;">No milestones yet. Add one above.</div>
        @endforelse
    </div>
</div>

</x-layouts.admin>
