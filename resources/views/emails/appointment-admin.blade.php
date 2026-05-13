<!DOCTYPE html>
<html><head><meta charset="utf-8"><style>
body{font-family:Arial,sans-serif;background:#f5f5f5;margin:0;padding:20px;}
.card{background:#fff;border-radius:8px;padding:32px;max-width:600px;margin:0 auto;}
h2{color:#1a1a1a;margin-top:0;}
.badge{display:inline-block;background:#fff3cd;color:#856404;padding:4px 12px;border-radius:20px;font-size:13px;font-weight:600;}
.detail{background:#f8f9fa;border-radius:6px;padding:16px;margin:20px 0;}
.detail p{margin:6px 0;color:#444;font-size:14px;}
.detail strong{color:#1a1a1a;}
.btn{display:inline-block;background:#1a1a1a;color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none;font-weight:600;margin-top:16px;}
</style></head>
<body>
<div class="card">
    <h2>🏠 New Inspection Booking</h2>
    <p>A buyer has requested a site inspection. Details below:</p>

    <div class="detail">
        <p><strong>Buyer:</strong> {{ $appointment->user->name }}</p>
        <p><strong>Email:</strong> {{ $appointment->user->email }}</p>
        <p><strong>Phone:</strong> {{ $appointment->user->phone ?? 'Not provided' }}</p>
        <p><strong>Property:</strong> {{ $appointment->property->development_name }}</p>
        <p><strong>Address:</strong> {{ $appointment->property->address }}</p>
        <p><strong>Preferred Date:</strong> {{ $appointment->preferred_date->format('l, d F Y') }}</p>
        <p><strong>Preferred Time:</strong> {{ date('g:i A', strtotime($appointment->preferred_time)) }}</p>
        @if($appointment->notes)
        <p><strong>Notes:</strong> {{ $appointment->notes }}</p>
        @endif
        @if($appointment->salesRep)
        <p><strong>Assigned Sales Rep:</strong> {{ $appointment->salesRep->name }} ({{ $appointment->salesRep->phone }})</p>
        @endif
    </div>

    <a href="{{ route('admin.appointments.show', $appointment) }}" class="btn">Manage Appointment</a>

    <p style="margin-top:24px;font-size:12px;color:#999;">Strongmas Portal · {{ config('app.name') }}</p>
</div>
</body></html>
