<x-layouts.admin title="Update Detail">
    <div style="max-width:680px;">
        <a href="{{ route('admin.project-updates.index') }}" style="font-size:13px;color:var(--fg-dim);text-decoration:none;display:inline-flex;align-items:center;gap:4px;margin-bottom:20px;">← Back</a>

        <h1 style="font-size:24px;font-weight:700;color:var(--fg);letter-spacing:-0.02em;margin-bottom:4px;">{{ $projectUpdate->title }}</h1>
        <p style="font-size:13px;color:var(--fg-mute);margin-bottom:24px;">{{ $property->development_name }} · {{ $projectUpdate->created_at->format('d M Y') }}</p>

        <div class="card" style="padding:20px;margin-bottom:16px;">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
                <div style="flex:1;background:var(--bg-raised);border-radius:999px;height:8px;overflow:hidden;">
                    <div style="background:var(--hi);height:8px;border-radius:999px;width:{{ $projectUpdate->progress_percentage }}%;"></div>
                </div>
                <span style="font-size:15px;font-weight:700;color:var(--hi);">{{ $projectUpdate->progress_percentage }}%</span>
            </div>
            @if($projectUpdate->stage)
            <p style="font-size:13px;color:var(--fg-dim);margin-bottom:12px;">Stage: <strong style="color:var(--fg);">{{ $projectUpdate->stage }}</strong></p>
            @endif
            <div style="font-size:13px;color:var(--fg-dim);line-height:1.6;white-space:pre-wrap;">{{ $projectUpdate->body }}</div>
        </div>

        @if($projectUpdate->youtube_embed_url)
        <div class="card" style="padding:0;overflow:hidden;margin-bottom:16px;">
            <div style="position:relative;padding-bottom:56.25%;height:0;">
                <iframe src="{{ $projectUpdate->youtube_embed_url }}" style="position:absolute;top:0;left:0;width:100%;height:100%;border:none;" allowfullscreen></iframe>
            </div>
        </div>
        @endif

        @if($projectUpdate->images->count())
        <div class="card" style="padding:0;overflow:hidden;margin-bottom:16px;">
            <div style="padding:16px 20px;border-bottom:1px solid var(--line);">
                <h2 style="font-size:15px;font-weight:600;color:var(--fg);">Images ({{ $projectUpdate->images->count() }})</h2>
            </div>
            <div style="padding:16px 20px;display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px;">
                @foreach($projectUpdate->images as $img)
                <div style="position:relative;border-radius:8px;overflow:hidden;background:var(--bg-raised);border:1px solid var(--line);">
                    <img src="{{ $img->url }}" alt="{{ $img->caption }}"
                         style="width:100%;height:140px;object-fit:cover;display:block;">
                    @if($img->caption)
                    <p style="font-size:11px;color:var(--fg-dim);padding:6px 8px;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $img->caption }}</p>
                    @endif
                    <form method="POST" action="{{ route('admin.project-update-images.destroy', $img) }}"
                          style="position:absolute;top:6px;right:6px;">
                        @csrf @method('DELETE')
                        <button type="submit"
                                style="width:26px;height:26px;display:grid;place-items:center;background:rgba(0,0,0,0.6);border:none;border-radius:6px;cursor:pointer;color:#fff;font-size:14px;line-height:1;"
                                title="Remove" onclick="return confirm('Remove this image?')">×</button>
                    </form>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        @if($milestones->count())
        <div class="card" style="padding:0;overflow:hidden;margin-bottom:16px;">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--line);">
                <h2 style="font-size:15px;font-weight:600;color:var(--fg);">Milestones</h2>
            </div>
            <div style="padding:0 20px;">
                @foreach($milestones as $m)
                @php $status = $m->status; @endphp
                <div style="display:flex;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid var(--line);">
                    <div style="width:8px;height:8px;border-radius:50%;flex-shrink:0;background:{{ $status==='completed' ? 'var(--ok)' : ($status==='in_progress' ? 'var(--hi)' : ($status==='delayed' ? 'var(--danger)' : 'var(--fg-mute)')) }};"></div>
                    <p style="flex:1;font-size:13px;color:var(--fg);">{{ $m->title }}</p>
                    @if($status === 'completed')
                        <span class="chip chip-ok">{{ $m->status_label }}</span>
                    @elseif($status === 'in_progress')
                        <span class="chip chip-tan">{{ $m->status_label }}</span>
                    @elseif($status === 'delayed')
                        <span class="chip chip-danger">{{ $m->status_label }}</span>
                    @else
                        <span class="chip chip-dark">{{ $m->status_label }}</span>
                    @endif
                    <p style="font-size:11px;color:var(--fg-mute);">{{ $m->planned_date->format('M Y') }}</p>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <div class="card" style="padding:0;overflow:hidden;">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--line);">
                <h2 style="font-size:15px;font-weight:600;color:var(--fg);">Comments ({{ $projectUpdate->comments->count() }})</h2>
            </div>
            <div style="padding:16px 20px;">
                @forelse($projectUpdate->comments as $comment)
                <div style="display:flex;gap:12px;margin-bottom:12px;">
                    <div style="width:32px;height:32px;border-radius:50%;background:{{ $comment->user->is_admin ? 'var(--hi)' : 'var(--bg-raised)' }};border:1px solid var(--line);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:{{ $comment->user->is_admin ? '#000' : 'var(--hi)' }};flex-shrink:0;">{{ $comment->user->initials }}</div>
                    <div style="flex:1;background:{{ $comment->user->is_admin ? 'rgba(var(--hi-rgb,240,196,80),0.08)' : 'var(--bg-raised)' }};border-radius:8px;padding:10px 12px;border:1px solid {{ $comment->user->is_admin ? 'var(--hi)' : 'transparent' }};">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
                            <p style="font-size:12px;font-weight:600;color:var(--fg);">
                                {{ $comment->user->name }}
                                @if($comment->user->is_admin)
                                    <span style="font-size:10px;background:var(--hi);color:#000;border-radius:4px;padding:1px 6px;margin-left:4px;font-weight:700;">TEAM</span>
                                @endif
                            </p>
                            <p style="font-size:11px;color:var(--fg-mute);">{{ $comment->created_at->diffForHumans() }}</p>
                        </div>
                        <p style="font-size:13px;color:var(--fg-dim);">{{ $comment->body }}</p>
                    </div>
                </div>
                @empty
                <p style="font-size:13px;color:var(--fg-mute);margin-bottom:16px;">No comments yet.</p>
                @endforelse

                {{-- Admin reply form --}}
                <div style="border-top:1px solid var(--line);padding-top:16px;margin-top:{{ $projectUpdate->comments->count() ? '8px' : '0' }};">
                    <p style="font-size:12px;font-weight:600;color:var(--fg-dim);margin-bottom:8px;">Reply as {{ auth()->user()->name }}</p>
                    <form method="POST" action="{{ route('admin.project-updates.comments.store', [$property, $projectUpdate]) }}">
                        @csrf
                        <textarea name="body" rows="3" placeholder="Type your reply to buyers…"
                                  class="input-base" style="resize:vertical;margin-bottom:10px;" required></textarea>
                        <button type="submit" class="btn-primary" style="font-size:13px;">Post Reply</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
