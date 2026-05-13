<!DOCTYPE html>
<html><head><meta charset="utf-8"><style>
body{font-family:Arial,sans-serif;background:#f5f5f5;margin:0;padding:20px;}
.card{background:#fff;border-radius:8px;padding:32px;max-width:600px;margin:0 auto;}
h2{color:#1a1a1a;margin-top:0;}
.detail{background:#f8f9fa;border-radius:6px;padding:16px;margin:20px 0;}
.detail p{margin:6px 0;color:#444;font-size:14px;}
.detail strong{color:#1a1a1a;}
</style></head>
<body>
<div class="card">
    <h2>Inspection Assignment — {{ $appointment->property->development_name }}</h2>
    <p>Hi {{ $appointment->salesRep->name }}, you have been assigned to handle a site inspection.</p>

    <div class="detail">
        <p><strong>Client Name:</strong> {{ $appointment->user->name }}</p>
        <p><strong>Client Phone:</strong> {{ $appointment->user->phone ?? 'Not provided' }}</p>
        <p><strong>Client Email:</strong> {{ $appointment->user->email }}</p>
        <p><strong>Property:</strong> {{ $appointment->property->development_name }}</p>
        <p><strong>Address:</strong> {{ $appointment->property->address }}</p>
        <p><strong>Preferred Date:</strong> {{ $appointment->preferred_date->format('l, d F Y') }}</p>
        <p><strong>Preferred Time:</strong> {{ date('g:i A', strtotime($appointment->preferred_time)) }}</p>
        @if($appointment->notes)
        <p><strong>Client Notes:</strong> {{ $appointment->notes }}</p>
        @endif
    </div>

    <p>Please contact the client to confirm this appointment. After the inspection, kindly update the admin on the outcome.</p>
    <p style="margin-top:24px;font-size:12px;color:#999;">Strongmas Portal</p>
</div>
</body></html>
