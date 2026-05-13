<!DOCTYPE html>
<html><head><meta charset="utf-8"><style>
body{font-family:Arial,sans-serif;background:#f5f5f5;margin:0;padding:20px;}
.card{background:#fff;border-radius:8px;padding:32px;max-width:600px;margin:0 auto;}
h2{color:#1a1a1a;margin-top:0;}
.meta{background:#f9f9f9;border-radius:6px;padding:16px 20px;margin:20px 0;}
.meta p{margin:4px 0;font-size:13px;color:#555;}
.meta strong{color:#1a1a1a;}
.reason{background:#fff3cd;border:1px solid #ffc107;border-radius:6px;padding:14px 18px;margin:16px 0;font-size:13px;color:#856404;}
.btn{display:inline-block;background:#1a1a1a;color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none;font-weight:600;margin-top:16px;}
</style></head>
<body>
<div class="card">
    <h2>⚠️ Document Requires Re-upload</h2>

    <p style="color:#444;line-height:1.6;">
        Our team has reviewed your uploaded document for
        <strong>{{ $document->property->development_name }}</strong> and it does not match
        the document sent to you. Please upload the correct document at your earliest convenience.
    </p>

    <div class="meta">
        <p><strong>Document:</strong> {{ $document->title }}</p>
        <p><strong>Type:</strong> {{ $document->type_label }}</p>
        <p><strong>Uploaded:</strong> {{ $document->created_at->format('d M Y') }}</p>
    </div>

    @if($document->flag_reason)
    <div class="reason">
        <strong>Note from our team:</strong><br>
        {{ $document->flag_reason }}
    </div>
    @endif

    <p style="color:#444;line-height:1.6;">
        Please log in to your portal, navigate to <strong>My Documents</strong>, and upload the correct document.
    </p>

    <a href="{{ route('documents.index') }}" class="btn">Go to My Documents</a>

    <p style="margin-top:24px;font-size:12px;color:#999;">Strongmas Portal · {{ config('app.name') }}</p>
</div>
</body></html>
