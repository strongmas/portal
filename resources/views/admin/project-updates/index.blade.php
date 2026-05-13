<x-layouts.admin title="Project Updates">
    <div class="flex items-center justify-between mb-6">
        <div>
            <div class="eyebrow" style="margin-bottom:4px;">Admin</div>
            <h1 style="font-size:24px;font-weight:700;color:var(--fg);letter-spacing:-0.02em;">Project Updates</h1>
        </div>
    </div>

    <div style="display:grid;gap:12px;">
        @forelse($properties as $property)
        @php $latest = $property->projectUpdates->first(); @endphp
        <div class="card" style="padding:20px;">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;">
                <div style="flex:1;">
                    <p style="font-weight:600;color:var(--fg);font-size:15px;margin-bottom:4px;">{{ $property->development_name }}</p>
                    <p style="font-size:13px;color:var(--fg-mute);margin-bottom:12px;">{{ $property->address }}</p>
                    @if($latest)
                    <div style="display:flex;align-items:center;gap:12px;margin-bottom:4px;">
                        <div style="flex:1;background:var(--bg-raised);border-radius:999px;height:6px;overflow:hidden;">
                            <div style="background:var(--hi);height:6px;border-radius:999px;width:{{ $latest->progress_percentage }}%;"></div>
                        </div>
                        <span style="font-size:13px;font-weight:700;color:var(--hi);">{{ $latest->progress_percentage }}%</span>
                    </div>
                    <p style="font-size:11px;color:var(--fg-mute);">Last updated: {{ $latest->created_at->format('d M Y') }}</p>
                    @else
                    <p style="font-size:13px;color:var(--fg-mute);">No updates posted yet</p>
                    @endif
                </div>
                <div style="display:flex;gap:8px;flex-shrink:0;">
                    <a href="{{ route('admin.milestones.index', $property) }}" class="btn-ghost">Milestones</a>
                    <a href="{{ route('admin.project-updates.create', $property) }}" class="btn-primary">+ Post Update</a>
                </div>
            </div>
        </div>
        @empty
        <div style="padding:60px;text-align:center;color:var(--fg-dim);font-size:13px;">No properties found.</div>
        @endforelse
    </div>
</x-layouts.admin>
