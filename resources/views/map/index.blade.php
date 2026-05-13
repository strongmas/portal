<x-portal-layout title="Property Map">
    <div>
        <div class="mb-4">
            <h1 style="font-size:22px;font-weight:700;color:var(--fg);margin:0 0 4px;">Property Map</h1>
            <p style="color:var(--fg-dim);font-size:14px;margin:0;">Your properties on the map</p>
        </div>

        <div style="background:var(--bg-card);border:1px solid var(--line);border-radius:16px;overflow:hidden;">
            <div id="map" style="height:520px;width:100%;"></div>
        </div>

        @if($ownerships->isEmpty())
        <div style="text-align:center;padding:40px;color:var(--fg-dim);">
            <p>No properties with location data available yet. Ask your admin to add coordinates to your properties.</p>
        </div>
        @endif
    </div>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    @php
        $mapData = $ownerships->map(function ($o) {
            return [
                'lat'       => (float) $o->property->lat,
                'lng'       => (float) $o->property->lng,
                'name'      => $o->property->development_name,
                'unit'      => $o->unit?->label ?? '',
                'addr'      => $o->property->address,
                'url'       => route('properties.show', $o),
                'cover_url' => $o->property->cover_url,
            ];
        })->values();
    @endphp

    <style>
    .map-pin { position:relative; }
    .map-pin-img {
        width:52px; height:52px; border-radius:50%; overflow:hidden;
        border:3px solid #fff; box-shadow:0 3px 10px rgba(0,0,0,0.35);
        background:#1e2a3a;
    }
    .map-pin-img img { width:100%; height:100%; object-fit:cover; display:block; }
    .map-pin-dot {
        width:52px; height:52px; border-radius:50%;
        background:linear-gradient(135deg,#4f46e5,#7c6ff7);
        border:3px solid #fff; box-shadow:0 3px 10px rgba(0,0,0,0.35);
        display:flex; align-items:center; justify-content:center;
        color:#fff; font-weight:800; font-size:18px;
    }
    .map-pin-tail {
        width:0; height:0;
        border-left:8px solid transparent; border-right:8px solid transparent;
        border-top:10px solid #fff;
        margin:0 auto; margin-top:-2px;
        filter: drop-shadow(0 2px 3px rgba(0,0,0,0.2));
    }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const properties = @json($mapData);

        if (!properties.length) return;

        const map = L.map('map').setView([properties[0].lat, properties[0].lng], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            maxZoom: 19,
        }).addTo(map);

        properties.forEach(function (p) {
            const inner = p.cover_url
                ? `<div class="map-pin-img"><img src="${p.cover_url}" alt="${p.name}"></div>`
                : `<div class="map-pin-dot">🏠</div>`;

            const icon = L.divIcon({
                html: `<div class="map-pin">${inner}<div class="map-pin-tail"></div></div>`,
                iconSize: [52, 64],
                iconAnchor: [26, 64],
                popupAnchor: [0, -68],
                className: '',
            });

            L.marker([p.lat, p.lng], { icon }).addTo(map).bindPopup(
                `<div style="min-width:180px;font-family:sans-serif;">
                    ${p.cover_url ? `<img src="${p.cover_url}" style="width:100%;height:100px;object-fit:cover;border-radius:6px;margin-bottom:10px;display:block;">` : ''}
                    <p style="font-weight:700;margin:0 0 3px;font-size:14px;">${p.name}</p>
                    ${p.unit ? `<p style="font-size:12px;color:#888;margin:0 0 3px;">${p.unit}</p>` : ''}
                    <p style="font-size:12px;color:#aaa;margin:0 0 10px;">${p.addr}</p>
                    <a href="${p.url}" style="display:inline-block;padding:7px 14px;background:#4f46e5;color:#fff;border-radius:6px;font-size:12px;font-weight:600;text-decoration:none;">View Details →</a>
                </div>`
            );
        });

        if (properties.length > 1) {
            map.fitBounds(L.latLngBounds(properties.map(p => [p.lat, p.lng])), { padding: [40, 40] });
        }
    });
    </script>
</x-portal-layout>
