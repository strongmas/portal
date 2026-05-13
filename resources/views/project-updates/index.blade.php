<x-portal-layout title="Project Updates">
@php
    $totalUpdates   = $properties->sum(fn($p) => $p->projectUpdates->count());
    $totalMilestones = $properties->sum(fn($p) => $p->milestones->count());
    $doneMs          = $properties->sum(fn($p) => $p->milestones->where('status', 'completed')->count());
    $avgProgress     = $properties->count()
        ? round($properties->avg(fn($p) => optional($p->projectUpdates->first())->progress_percentage ?? 0), 0)
        : 0;
@endphp

{{-- Page header --}}
<div style="margin-bottom:32px;">
    <div class="eyebrow eyebrow-tan" style="margin-bottom:8px;">Construction Feed</div>
    <h1 style="font-size:28px;font-weight:700;color:var(--fg);letter-spacing:-0.02em;margin:0 0 6px;">Project Updates</h1>
    <p style="font-size:14px;color:var(--fg-mute);margin:0;">Live progress reports for your properties</p>
</div>

{{-- Summary strip --}}
@if($properties->count())
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-bottom:36px;">
    <div style="background:var(--bg-card);border:1px solid var(--line);border-radius:12px;padding:16px 18px;">
        <div style="font-size:11px;font-weight:500;color:var(--fg-dim);letter-spacing:.1em;text-transform:uppercase;font-family:'Geist Mono',monospace;margin-bottom:6px;">Properties</div>
        <div style="font-size:26px;font-weight:700;font-family:'Geist Mono',monospace;color:var(--fg);">{{ $properties->count() }}</div>
    </div>
    <div style="background:var(--bg-card);border:1px solid var(--line);border-radius:12px;padding:16px 18px;">
        <div style="font-size:11px;font-weight:500;color:var(--fg-dim);letter-spacing:.1em;text-transform:uppercase;font-family:'Geist Mono',monospace;margin-bottom:6px;">Total Updates</div>
        <div style="font-size:26px;font-weight:700;font-family:'Geist Mono',monospace;color:var(--fg);">{{ $totalUpdates }}</div>
    </div>
    <div style="background:var(--bg-card);border:1px solid var(--line);border-radius:12px;padding:16px 18px;">
        <div style="font-size:11px;font-weight:500;color:var(--fg-dim);letter-spacing:.1em;text-transform:uppercase;font-family:'Geist Mono',monospace;margin-bottom:6px;">Avg. Progress</div>
        <div style="font-size:26px;font-weight:700;font-family:'Geist Mono',monospace;color:var(--tan);">{{ $avgProgress }}%</div>
    </div>
    <div style="background:var(--bg-card);border:1px solid var(--line);border-radius:12px;padding:16px 18px;">
        <div style="font-size:11px;font-weight:500;color:var(--fg-dim);letter-spacing:.1em;text-transform:uppercase;font-family:'Geist Mono',monospace;margin-bottom:6px;">Milestones Done</div>
        <div style="font-size:26px;font-weight:700;font-family:'Geist Mono',monospace;color:var(--ok);">{{ $doneMs }}<span style="font-size:15px;color:var(--fg-faint);">/{{ $totalMilestones }}</span></div>
    </div>
</div>
@endif

@forelse($properties as $property)
@php
    $latest     = $property->projectUpdates->first();
    $progress   = $latest?->progress_percentage ?? 0;
    $stageLabel = $latest?->stage ?? ($property->project_status ?? null);

    $statusColors = [
        'planning'     => ['bg' => 'rgba(100,130,200,0.15)', 'text' => '#7b9fd4', 'border' => 'rgba(100,130,200,0.3)'],
        'foundation'   => ['bg' => 'rgba(245,194,107,0.15)', 'text' => '#f5c26b', 'border' => 'rgba(245,194,107,0.3)'],
        'construction' => ['bg' => 'rgba(239,197,157,0.15)', 'text' => '#EFC59D', 'border' => 'rgba(239,197,157,0.3)'],
        'finishing'    => ['bg' => 'rgba(93,217,167,0.15)',  'text' => '#5DD9A7', 'border' => 'rgba(93,217,167,0.3)'],
        'completed'    => ['bg' => 'rgba(93,217,167,0.15)',  'text' => '#5DD9A7', 'border' => 'rgba(93,217,167,0.3)'],
    ];
    $sc = $statusColors[$property->project_status ?? 'construction'] ?? $statusColors['construction'];

    $milestonesDone  = $property->milestones->where('status', 'completed')->count();
    $milestonesTotal = $property->milestones->count();

    // Determine ownership for analytics link
    $ownership = $ownershipsMap[$property->id] ?? null;
@endphp

<div style="background:var(--bg-card);border:1px solid var(--line);border-radius:20px;overflow:hidden;margin-bottom:28px;transition:border-color var(--dur-fast) var(--ease);"
     onmouseover="this.style.borderColor='rgba(239,197,157,0.28)'" onmouseout="this.style.borderColor='rgba(255,255,255,0.08)'">

    {{-- Cover image band --}}
    @if($property->cover_url)
    <div style="height:180px;background:url('{{ $property->cover_url }}') center/cover no-repeat;position:relative;">
        <div style="position:absolute;inset:0;background:linear-gradient(to bottom, rgba(10,20,40,0.2) 0%, rgba(10,20,40,0.85) 100%);"></div>
        <div style="position:absolute;bottom:0;left:0;right:0;padding:20px 24px;display:flex;align-items:flex-end;justify-content:space-between;">
            <div>
                <h2 style="font-size:20px;font-weight:700;color:#fff;margin:0 0 3px;text-shadow:0 1px 4px rgba(0,0,0,0.5);">{{ $property->development_name }}</h2>
                <p style="font-size:13px;color:rgba(255,255,255,0.7);margin:0;">{{ $property->address }}</p>
            </div>
            <div style="text-align:right;">
                <div style="font-size:32px;font-weight:800;color:var(--tan);font-family:'Geist Mono',monospace;line-height:1;text-shadow:0 2px 8px rgba(0,0,0,0.4);">{{ $progress }}%</div>
                <div style="font-size:11px;color:rgba(255,255,255,0.6);letter-spacing:.06em;text-transform:uppercase;">Progress</div>
            </div>
        </div>
    </div>
    @else
    {{-- Fallback header without image --}}
    <div style="padding:24px 24px 0;display:flex;justify-content:space-between;align-items:flex-start;">
        <div>
            <h2 style="font-size:20px;font-weight:700;color:var(--fg);margin:0 0 4px;">{{ $property->development_name }}</h2>
            <p style="font-size:13px;color:var(--fg-dim);margin:0;">{{ $property->address }}</p>
        </div>
        <div style="text-align:right;flex-shrink:0;">
            <div style="font-size:32px;font-weight:800;color:var(--tan);font-family:'Geist Mono',monospace;line-height:1;">{{ $progress }}%</div>
            <div style="font-size:11px;color:var(--fg-mute);letter-spacing:.06em;text-transform:uppercase;">Progress</div>
        </div>
    </div>
    @endif

    <div style="padding:20px 24px 24px;">

        {{-- Progress bar + Stage --}}
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
            <div style="flex:1;background:var(--bg-hover);border-radius:99px;height:8px;overflow:hidden;">
                <div style="height:8px;border-radius:99px;width:{{ $progress }}%;background:linear-gradient(90deg,var(--tan-dark),var(--tan));transition:width 1s var(--ease);"></div>
            </div>
            @if($stageLabel)
            <span style="font-size:12px;font-weight:600;background:{{ $sc['bg'] }};color:{{ $sc['text'] }};border:1px solid {{ $sc['border'] }};padding:3px 12px;border-radius:99px;white-space:nowrap;">
                {{ $stageLabel }}
            </span>
            @endif
        </div>

        {{-- Milestones --}}
        @if($property->milestones->count())
        <div style="margin-bottom:20px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                <div style="font-size:11px;font-weight:600;color:var(--fg-mute);text-transform:uppercase;letter-spacing:.08em;">Milestones</div>
                <span style="font-size:12px;color:var(--fg-dim);">{{ $milestonesDone }}/{{ $milestonesTotal }} complete</span>
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:6px;">
                @foreach($property->milestones as $ms)
                @php
                    $mc = match($ms->status) {
                        'completed'   => ['bg'=>'rgba(93,217,167,0.12)','text'=>'#5DD9A7','border'=>'rgba(93,217,167,0.28)','dot'=>'#5DD9A7'],
                        'in_progress' => ['bg'=>'rgba(99,148,244,0.12)','text'=>'#7ba8f7','border'=>'rgba(99,148,244,0.28)','dot'=>'#7ba8f7'],
                        'delayed'     => ['bg'=>'rgba(244,114,114,0.12)','text'=>'#F47272','border'=>'rgba(244,114,114,0.28)','dot'=>'#F47272'],
                        default       => ['bg'=>'rgba(255,255,255,0.04)','text'=>'#7A8AA5','border'=>'rgba(255,255,255,0.1)','dot'=>'#4A5876'],
                    };
                @endphp
                <div style="background:{{ $mc['bg'] }};border:1px solid {{ $mc['border'] }};border-radius:8px;padding:5px 11px;display:flex;align-items:center;gap:6px;">
                    <span style="width:6px;height:6px;border-radius:50%;background:{{ $mc['dot'] }};flex-shrink:0;"></span>
                    <span style="font-size:12px;font-weight:500;color:{{ $mc['text'] }};">{{ $ms->title }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Recent updates feed --}}
        @if($property->projectUpdates->count())
        <div style="margin-bottom:20px;">
            <div style="font-size:11px;font-weight:600;color:var(--fg-mute);text-transform:uppercase;letter-spacing:.08em;margin-bottom:10px;">Recent Updates</div>
            <div style="display:flex;flex-direction:column;gap:6px;">
                @foreach($property->projectUpdates->take(3) as $i => $update)
                <a href="{{ route('project-updates.show', $update) }}"
                   style="display:flex;gap:12px;align-items:center;padding:11px 14px;background:var(--bg-raised);border-radius:10px;text-decoration:none;border:1px solid transparent;transition:all var(--dur-fast) var(--ease);"
                   onmouseover="this.style.borderColor='var(--line-tan)';this.style.background='var(--bg-hover)'"
                   onmouseout="this.style.borderColor='transparent';this.style.background='var(--bg-raised)'">
                    <div style="width:7px;height:7px;border-radius:50%;background:{{ $i === 0 ? 'var(--tan)' : 'var(--fg-faint)' }};flex-shrink:0;"></div>
                    <div style="flex:1;min-width:0;">
                        <p style="font-size:14px;font-weight:{{ $i === 0 ? '600' : '500' }};color:{{ $i === 0 ? 'var(--fg)' : 'var(--fg-mute)' }};margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $update->title }}</p>
                    </div>
                    <div style="flex-shrink:0;display:flex;align-items:center;gap:8px;">
                        @if($update->progress_percentage !== null)
                        <span style="font-size:12px;font-weight:600;color:var(--tan);font-family:'Geist Mono',monospace;">{{ $update->progress_percentage }}%</span>
                        @endif
                        <span style="font-size:12px;color:var(--fg-faint);">{{ $update->created_at->format('d M') }}</span>
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color:var(--fg-faint);"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
        @else
        <div style="padding:16px;background:var(--bg-raised);border-radius:10px;margin-bottom:20px;">
            <p style="font-size:14px;color:var(--fg-dim);margin:0;text-align:center;">No updates posted yet — check back soon.</p>
        </div>
        @endif

        {{-- Footer actions --}}
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
            @if($latest)
            <a href="{{ route('project-updates.show', $latest) }}" class="btn-primary" style="font-size:13px;padding:9px 18px;">
                View Latest Update →
            </a>
            @endif
            @if($ownership)
            <a href="{{ route('property-analytics.show', $ownership) }}" class="btn-ghost" style="font-size:13px;padding:9px 16px;">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:5px;"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                Analytics
            </a>
            @endif
            @if($property->projectUpdates->count() > 3)
            <span style="font-size:12px;color:var(--fg-dim);">+{{ $property->projectUpdates->count() - 3 }} more updates</span>
            @endif
        </div>
    </div>
</div>
@empty
<div style="text-align:center;padding:80px 24px;background:var(--bg-card);border:1px solid var(--line);border-radius:20px;">
    <div style="width:64px;height:64px;border-radius:50%;background:var(--bg-raised);display:grid;place-items:center;margin:0 auto 20px;">
        <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="color:var(--fg-dim);"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18M3 9h18"/></svg>
    </div>
    <p style="font-size:17px;font-weight:600;color:var(--fg);margin:0 0 8px;">No project updates yet</p>
    <p style="font-size:14px;color:var(--fg-dim);margin:0;">Updates will appear here once your property's construction begins.</p>
</div>
@endforelse
</x-portal-layout>
