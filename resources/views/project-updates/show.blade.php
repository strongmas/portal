<x-portal-layout title="{{ $projectUpdate->title }}">
@php
    $progress = $projectUpdate->progress_percentage;
    // Check if the current user is a view-only member for this property's ownership
    $userOwnership = $projectUpdate->property->ownerships
        ->where('user_id', auth()->id())->first();
    $memberOwnership = \App\Models\Ownership::where('property_id', $projectUpdate->property_id)
        ->whereIn('id', function ($q) {
            $q->select('ownership_id')->from('ownership_members')
              ->where('user_id', auth()->id());
        })->first();
    $relevantOwnership = $userOwnership ?? $memberOwnership;
    $isViewer = $relevantOwnership ? auth()->user()->isViewerOnly($relevantOwnership) : false;

    $statusColors = [
        'planning'     => ['bg' => 'rgba(100,130,200,0.15)', 'text' => '#7b9fd4', 'border' => 'rgba(100,130,200,0.3)'],
        'foundation'   => ['bg' => 'rgba(245,194,107,0.15)', 'text' => '#f5c26b', 'border' => 'rgba(245,194,107,0.3)'],
        'construction' => ['bg' => 'rgba(239,197,157,0.15)', 'text' => '#EFC59D', 'border' => 'rgba(239,197,157,0.3)'],
        'finishing'    => ['bg' => 'rgba(93,217,167,0.15)',  'text' => '#5DD9A7', 'border' => 'rgba(93,217,167,0.3)'],
        'completed'    => ['bg' => 'rgba(93,217,167,0.15)',  'text' => '#5DD9A7', 'border' => 'rgba(93,217,167,0.3)'],
    ];
    $sc = $statusColors[$projectUpdate->stage ?? 'construction'] ?? $statusColors['construction'];
@endphp

<div style="max-width:820px;">

    {{-- Breadcrumb --}}
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:24px;font-size:13px;color:var(--fg-dim);">
        <a href="{{ route('project-updates.index') }}" style="color:var(--fg-dim);text-decoration:none;">Updates</a>
        <span>›</span>
        <span style="color:var(--fg);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:300px;">{{ $projectUpdate->title }}</span>
    </div>

    {{-- Hero card --}}
    <div style="background:var(--hero-gradient);border:1px solid var(--line-tan);border-radius:20px;overflow:hidden;margin-bottom:20px;">

        {{-- Cover image --}}
        @if($projectUpdate->property->cover_url)
        <div style="height:200px;background:url('{{ $projectUpdate->property->cover_url }}') center/cover no-repeat;position:relative;">
            <div style="position:absolute;inset:0;background:linear-gradient(to bottom, transparent 40%, var(--bg-card) 100%);"></div>
        </div>
        @endif

        <div style="padding:28px;">
            {{-- Property + date eyebrow --}}
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;flex-wrap:wrap;">
                <span style="font-size:12px;font-weight:600;color:var(--tan);text-transform:uppercase;letter-spacing:.08em;">{{ $projectUpdate->property->development_name }}</span>
                <span style="width:3px;height:3px;border-radius:50%;background:var(--fg-faint);"></span>
                <span style="font-size:12px;color:var(--fg-dim);">{{ $projectUpdate->created_at->format('d M Y') }}</span>
                @if($projectUpdate->stage)
                <span style="background:{{ $sc['bg'] }};color:{{ $sc['text'] }};border:1px solid {{ $sc['border'] }};font-size:12px;font-weight:600;padding:3px 12px;border-radius:99px;">{{ $projectUpdate->stage }}</span>
                @endif
            </div>

            <h1 style="font-size:24px;font-weight:700;color:var(--fg);letter-spacing:-0.02em;margin:0 0 20px;line-height:1.3;">{{ $projectUpdate->title }}</h1>
            @if($progress !== null)

            {{-- Progress --}}
            <div style="display:flex;align-items:center;gap:14px;margin-bottom:16px;">
                <div style="flex:1;background:rgba(255,255,255,0.08);border-radius:99px;height:10px;overflow:hidden;">
                    <div style="height:10px;border-radius:99px;width:{{ $progress }}%;background:linear-gradient(90deg,var(--tan-dark),var(--tan));transition:width 1s var(--ease);"></div>
                </div>
                <span style="font-size:20px;font-weight:800;font-family:'Geist Mono',monospace;color:var(--tan);flex-shrink:0;">{{ $progress }}%</span>
            </div>
            @endif

            {{-- Est. completion --}}
            @if($projectUpdate->estimated_completion)
            <p style="font-size:13px;color:var(--fg-dim);margin:0;">
                Estimated completion:
                <strong style="color:var(--fg);">{{ $projectUpdate->estimated_completion->format('F Y') }}</strong>
            </p>
            @endif
        </div>
    </div>

    {{-- Body --}}
    @if($projectUpdate->body)
    <div style="background:var(--bg-card);border:1px solid var(--line);border-radius:16px;padding:28px;margin-bottom:20px;">
        <div style="font-size:15px;line-height:1.85;color:var(--fg-mute);">{!! nl2br(e($projectUpdate->body)) !!}</div>
    </div>
    @endif

    {{-- Property Walkthrough Video --}}
    @if($projectUpdate->hasVideo())
    <div style="background:var(--bg-card);border:1px solid var(--line);border-radius:16px;overflow:hidden;margin-bottom:20px;">
        <div style="padding:16px 20px;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:10px;">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color:var(--hi);"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                <span style="font-size:13px;font-weight:600;color:var(--fg);">Walkthrough Video</span>
            </div>
            <div style="display:flex;align-items:center;gap:12px;">
                <span style="font-size:11px;color:var(--fg-dim);">Expires: {{ $projectUpdate->video_expires_at->format('d M Y') }}</span>
            </div>
        </div>
        <div style="padding:16px 20px;">
            {{-- Video with watermark overlay --}}
            <div style="position:relative;display:inline-block;width:100%;margin-bottom:12px;">
                <video controls playsinline preload="metadata"
                       style="width:100%;border-radius:8px;background:#000;display:block;"
                       controlsList="nodownload">
                    <source src="{{ $projectUpdate->video_url }}" type="video/mp4">
                </video>
                <div style="position:absolute;top:12px;right:14px;color:rgba(255,255,255,0.55);font-size:13px;font-weight:600;pointer-events:none;text-shadow:0 1px 3px rgba(0,0,0,0.6);">
                    Strongmas
                </div>
            </div>

            {{-- Download & Share buttons --}}
            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <a href="{{ route('project-updates.video.download', $projectUpdate) }}"
                   style="flex:1;min-width:120px;padding:10px 16px;background:var(--hi);color:#000;border:none;border-radius:8px;font-weight:600;font-size:13px;text-decoration:none;text-align:center;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:6px;">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Download
                </a>
                <a href="https://wa.me/?text={{ urlencode('Check out this property walkthrough from Strongmas: ' . route('project-updates.show', $projectUpdate)) }}"
                   target="_blank" rel="noopener"
                   style="flex:1;min-width:120px;padding:10px 16px;background:var(--bg-raised);color:var(--fg);border:1px solid var(--line);border-radius:8px;font-weight:600;font-size:13px;text-decoration:none;text-align:center;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:6px;">
                    <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.67-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.076 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421-7.403h-.006a9.87 9.87 0 00-5.031 1.378l-.361.214-3.741-.982.998 3.645-.235.364a9.864 9.864 0 001.512 5.514c.732 1.243 1.882 2.524 3.465 3.46h.006c1.07.629 1.98 1.005 2.755 1.245.484.119 1.02.108 1.452-.021l.41-.052 3.741.982-.998-3.645.235-.364c.552-.935 1.01-1.9 1.280-2.858.13-.526.167-1.056.082-1.572-.236-1.558-.958-2.945-2.063-3.861l-.329-.267c-.863-.704-1.916-1.177-3.053-1.378-.577-.072-1.165-.072-1.73.027z"/></svg>
                    Share
                </a>
            </div>
        </div>
    </div>
    @endif

    {{-- YouTube video --}}
    @if($projectUpdate->youtube_embed_url)
    <div style="background:var(--bg-card);border:1px solid var(--line);border-radius:16px;overflow:hidden;margin-bottom:20px;">
        <div style="padding:16px 20px;border-bottom:1px solid var(--line);display:flex;align-items:center;gap:10px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="#ff0000"><path d="M21.8 8s-.2-1.4-.8-2c-.8-.8-1.6-.8-2-.9C16.3 5 12 5 12 5s-4.3 0-7 .1c-.4.1-1.2.1-2 .9-.6.6-.8 2-.8 2S2 9.6 2 11.2v1.5c0 1.6.2 3.2.2 3.2s.2 1.4.8 2c.8.8 1.8.8 2.3.8C6.8 19 12 19 12 19s4.3 0 7-.2c.4-.1 1.2-.1 2-.9.6-.6.8-2 .8-2s.2-1.6.2-3.2v-1.5C22 9.6 21.8 8 21.8 8zM9.7 14.5V9l5.4 2.8-5.4 2.7z"/></svg>
            <span style="font-size:13px;font-weight:600;color:var(--fg);">Video Update</span>
        </div>
        <div style="position:relative;padding-bottom:56.25%;height:0;">
            <iframe src="{{ $projectUpdate->youtube_embed_url }}"
                    style="position:absolute;top:0;left:0;width:100%;height:100%;border:none;"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
        </div>
    </div>
    @endif

    {{-- Images --}}
    @if($projectUpdate->images->count())
    <div style="background:var(--bg-card);border:1px solid var(--line);border-radius:16px;padding:24px;margin-bottom:20px;">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:16px;">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color:var(--fg-dim);"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
            <h3 style="font-size:14px;font-weight:700;color:var(--fg);margin:0;">Site Photos</h3>
            <span style="font-size:12px;background:var(--bg-raised);color:var(--fg-dim);padding:2px 8px;border-radius:99px;">{{ $projectUpdate->images->count() }}</span>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:10px;">
            @foreach($projectUpdate->images as $img)
            <div style="border-radius:10px;overflow:hidden;background:var(--bg-raised);border:1px solid var(--line);">
                <img src="{{ $img->url }}" alt="{{ $img->caption ?? '' }}"
                     style="width:100%;height:160px;object-fit:cover;display:block;">
                @if($img->caption)
                <p style="font-size:12px;color:var(--fg-dim);padding:8px 10px;margin:0;border-top:1px solid var(--line);line-height:1.4;">{{ $img->caption }}</p>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Milestones Timeline --}}
    @if($projectUpdate->property->milestones->count())
    <div style="background:var(--bg-card);border:1px solid var(--line);border-radius:16px;padding:24px;margin-bottom:20px;">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color:var(--fg-dim);"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
            <h3 style="font-size:14px;font-weight:700;color:var(--fg);margin:0;">Project Milestones</h3>
        </div>
        <div style="position:relative;padding-left:24px;">
            {{-- Vertical line --}}
            <div style="position:absolute;left:7px;top:8px;bottom:8px;width:2px;background:linear-gradient(to bottom, var(--tan) 0%, var(--bg-hover) 100%);border-radius:1px;"></div>

            @foreach($projectUpdate->property->milestones as $idx => $milestone)
            @php
                $mc = match($milestone->status) {
                    'completed'   => ['dot'=>'#5DD9A7', 'text'=>'#5DD9A7', 'bg'=>'rgba(93,217,167,0.12)', 'border'=>'rgba(93,217,167,0.3)'],
                    'in_progress' => ['dot'=>'#7ba8f7', 'text'=>'#7ba8f7', 'bg'=>'rgba(99,148,244,0.12)', 'border'=>'rgba(99,148,244,0.3)'],
                    'delayed'     => ['dot'=>'#F47272', 'text'=>'#F47272', 'bg'=>'rgba(244,114,114,0.12)', 'border'=>'rgba(244,114,114,0.3)'],
                    default       => ['dot'=>'#4A5876', 'text'=>'#7A8AA5', 'bg'=>'rgba(255,255,255,0.04)', 'border'=>'rgba(255,255,255,0.1)'],
                };
            @endphp
            <div style="position:relative;padding:4px 0 20px;">
                {{-- Dot --}}
                <div style="position:absolute;left:-20px;top:6px;width:14px;height:14px;border-radius:50%;background:{{ $mc['dot'] }};box-shadow:0 0 0 3px {{ $mc['bg'] }};"></div>

                <div style="background:var(--bg-raised);border:1px solid var(--line);border-radius:12px;padding:14px 16px;">
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                        <div style="flex:1;min-width:0;">
                            <p style="font-size:14px;font-weight:600;color:var(--fg);margin:0 0 4px;">{{ $milestone->title }}</p>
                            @if($milestone->description)
                            <p style="font-size:13px;color:var(--fg-dim);margin:0 0 6px;line-height:1.5;">{{ $milestone->description }}</p>
                            @endif
                            <p style="font-size:12px;color:var(--fg-faint);margin:0;">
                                Planned: {{ $milestone->planned_date->format('d M Y') }}
                                @if($milestone->actual_date)
                                    · Actual: {{ $milestone->actual_date->format('d M Y') }}
                                @endif
                            </p>
                        </div>
                        <span style="font-size:11px;font-weight:600;color:{{ $mc['text'] }};background:{{ $mc['bg'] }};border:1px solid {{ $mc['border'] }};padding:3px 10px;border-radius:99px;white-space:nowrap;flex-shrink:0;">
                            {{ $milestone->status_label }}
                        </span>
                    </div>

                    @if($milestone->image_path)
                    <div style="margin-top:12px;border-radius:8px;overflow:hidden;height:120px;background:url('{{ Storage::url($milestone->image_path) }}') center/cover no-repeat;"></div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Comments --}}
    <div style="background:var(--bg-card);border:1px solid var(--line);border-radius:16px;padding:24px;">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color:var(--fg-dim);"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
            <h3 style="font-size:14px;font-weight:700;color:var(--fg);margin:0;">Comments & Questions</h3>
            @if($projectUpdate->comments->count())
            <span style="font-size:12px;background:var(--bg-raised);color:var(--fg-dim);padding:2px 8px;border-radius:99px;">{{ $projectUpdate->comments->count() }}</span>
            @endif
        </div>

        @forelse($projectUpdate->comments as $comment)
        <div style="display:flex;gap:12px;margin-bottom:16px;">
            <div class="avatar" style="flex-shrink:0;">{{ $comment->user->initials }}</div>
            <div style="flex:1;background:var(--bg-raised);border-radius:12px;padding:12px 14px;border:1px solid var(--line);">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;gap:8px;">
                    <p style="font-size:13px;font-weight:600;color:var(--fg);margin:0;">{{ $comment->user->name }}</p>
                    <p style="font-size:11px;color:var(--fg-faint);margin:0;white-space:nowrap;">{{ $comment->created_at->diffForHumans() }}</p>
                </div>
                <p style="font-size:14px;color:var(--fg-mute);margin:0;line-height:1.6;">{{ $comment->body }}</p>
            </div>
        </div>
        @empty
        <p style="font-size:14px;color:var(--fg-dim);margin:0 0 20px;">No comments yet — be the first to ask a question.</p>
        @endforelse

        @if(!$isViewer)
        <form method="POST" action="{{ route('project-updates.comments.store', $projectUpdate) }}" style="margin-top:{{ $projectUpdate->comments->count() ? '12px' : '0' }};">
            @csrf
            <div style="display:flex;gap:10px;align-items:flex-start;">
                <div class="avatar" style="flex-shrink:0;margin-top:2px;">{{ auth()->user()->initials }}</div>
                <div style="flex:1;">
                    <textarea name="body" rows="3" placeholder="Ask a question or leave a comment…"
                              class="input-base"
                              style="resize:vertical;margin-bottom:10px;"
                              required>{{ old('body') }}</textarea>
                    <x-primary-button>Post Comment</x-primary-button>
                </div>
            </div>
        </form>
        @else
        <div style="padding:14px;background:var(--bg-raised);border-radius:10px;border:1px solid var(--line);text-align:center;margin-top:12px;">
            <p style="font-size:13px;color:var(--fg-dim);margin:0;">You have view-only access to this property.</p>
        </div>
        @endif
    </div>

</div>
</x-portal-layout>
