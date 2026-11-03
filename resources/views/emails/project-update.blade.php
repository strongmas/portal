<!DOCTYPE html>
<html><head><meta charset="utf-8"><style>
body{font-family:Arial,sans-serif;background:#f5f5f5;margin:0;padding:20px;}
.card{background:#fff;border-radius:8px;padding:32px;max-width:600px;margin:0 auto;}
h2{color:#1a1a1a;margin-top:0;}
.progress-wrap{background:#eee;border-radius:99px;height:10px;margin:12px 0 20px;}
.progress-bar{background:#22c55e;height:10px;border-radius:99px;}
.btn{display:inline-block;background:#1a1a1a;color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none;font-weight:600;margin-top:16px;}
</style></head>
<body>
<div class="card">
    <h2>📢 Project Update — {{ $update->property->development_name }}</h2>

    <h3 style="margin:0 0 8px;color:#1a1a1a;">{{ $update->title }}</h3>
    @if($update->stage)
    <p style="margin:0 0 12px;color:#666;font-size:13px;">Stage: {{ $update->stage }}</p>
    @endif
    @if($update->progress_percentage !== null)

    <div class="progress-wrap">
        <div class="progress-bar" style="width:{{ $update->progress_percentage }}%"></div>
    </div>
    <p style="font-size:13px;color:#666;margin:0 0 20px;">Overall progress: <strong>{{ $update->progress_percentage }}%</strong></p>
    @endif

    @if($update->body)
    <p style="color:#444;line-height:1.6;">{{ Str::limit(strip_tags($update->body), 300) }}</p>
    @endif

    <a href="{{ route('project-updates.show', $update) }}" class="btn">View Full Update</a>

    @if($update->estimated_completion)
    <p style="margin-top:20px;font-size:13px;color:#666;">Estimated completion: {{ $update->estimated_completion->format('F Y') }}</p>
    @endif

    <p style="margin-top:24px;font-size:12px;color:#999;">Strongmas Portal · {{ config('app.name') }}</p>
</div>
</body></html>
