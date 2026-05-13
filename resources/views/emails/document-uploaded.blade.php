<!DOCTYPE html>
<html><head><meta charset="utf-8"><style>
body{font-family:Arial,sans-serif;background:#f5f5f5;margin:0;padding:20px;}
.card{background:#fff;border-radius:8px;padding:32px;max-width:600px;margin:0 auto;}
h2{color:#1a1a1a;margin-top:0;}
.meta{background:#f9f9f9;border-radius:6px;padding:16px 20px;margin:20px 0;}
.meta p{margin:4px 0;font-size:13px;color:#555;}
.meta strong{color:#1a1a1a;}
.btn{display:inline-block;background:#1a1a1a;color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none;font-weight:600;margin-top:16px;}
</style></head>
<body>
<div class="card">
    <h2>📄 New Document Available</h2>

    <p style="color:#444;line-height:1.6;">
        A new document has been uploaded for your property at
        <strong>{{ $document->property->development_name }}</strong>.
    </p>

    <div class="meta">
        <p><strong>Document:</strong> {{ $document->title }}</p>
        <p><strong>Type:</strong> {{ $document->type_label }}</p>
        <p><strong>Uploaded:</strong> {{ $document->created_at->format('d M Y') }}</p>
    </div>

    <a href="{{ route('documents.index') }}" class="btn">View Document</a>

    <p style="margin-top:24px;font-size:12px;color:#999;">Strongmas Portal · {{ config('app.name') }}</p>
</div>
</body></html>
