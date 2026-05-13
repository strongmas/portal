<x-layouts.admin title="Edit · {{ $buyer->name }}">

<div class="flex items-center gap-2 mb-6" style="font-size:13px;color:var(--fg-dim);">
    <a href="{{ route('admin.buyers.index') }}" style="color:var(--fg-dim);text-decoration:none;" onmouseover="this.style.color='var(--tan)'" onmouseout="this.style.color='var(--fg-dim)'">Buyers</a>
    <span>/</span>
    <a href="{{ route('admin.buyers.show', $buyer) }}" style="color:var(--fg-dim);text-decoration:none;" onmouseover="this.style.color='var(--tan)'" onmouseout="this.style.color='var(--fg-dim)'">{{ $buyer->name }}</a>
    <span>/</span>
    <span style="color:var(--fg);">Edit</span>
</div>

<div style="max-width:520px;">
    <h1 style="font-size:28px;font-weight:700;color:var(--fg);letter-spacing:-0.02em;margin-bottom:24px;">Edit buyer</h1>

    @if($errors->any())
        <div style="background:var(--danger-bg);border:1px solid rgba(244,114,114,0.3);border-radius:var(--r-lg);padding:14px 18px;margin-bottom:20px;">
            <ul style="list-style:disc;padding-left:16px;display:flex;flex-direction:column;gap:4px;">
                @foreach($errors->all() as $error)
                    <li style="font-size:13px;color:var(--danger);">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.buyers.update', $buyer) }}">
        @csrf @method('PATCH')

        <div class="card" style="padding:24px;border-radius:var(--r-lg) var(--r-lg) 0 0;border-bottom:none;display:flex;flex-direction:column;gap:16px;">
            <div>
                <label class="eyebrow" style="display:block;margin-bottom:6px;">Full name</label>
                <input type="text" name="name" value="{{ old('name', $buyer->name) }}" required class="input-base"/>
            </div>
            <div>
                <label class="eyebrow" style="display:block;margin-bottom:6px;">Email</label>
                <input type="email" value="{{ $buyer->email }}" disabled class="input-base" style="opacity:0.5;cursor:not-allowed;"/>
                <p style="font-size:12px;color:var(--fg-dim);margin-top:4px;">Email cannot be changed here.</p>
            </div>
            <div>
                <label class="eyebrow" style="display:block;margin-bottom:6px;">Phone</label>
                <input type="text" name="phone" value="{{ old('phone', $buyer->phone) }}" class="input-base"/>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 0;border-top:1px solid var(--line);">
                <div>
                    <div style="font-size:14px;font-weight:500;color:var(--fg);">KYC Verified</div>
                    <p style="font-size:12px;color:var(--fg-dim);margin-top:2px;">Toggle to mark buyer as identity-verified</p>
                </div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <input type="hidden" name="kyc_verified" value="0"/>
                    <input type="checkbox" id="kyc_verified" name="kyc_verified" value="1"
                           {{ $buyer->is_kyc_verified ? 'checked' : '' }}
                           style="width:18px;height:18px;border-radius:4px;accent-color:var(--tan);border:1px solid var(--line-strong);"/>
                </div>
            </div>
        </div>

        <div class="card form-footer" style="padding:16px 24px;border-radius:0 0 var(--r-lg) var(--r-lg);border-top:none;background:var(--bg-raised);">
            <a href="{{ route('admin.buyers.show', $buyer) }}" style="font-size:13px;color:var(--fg-dim);text-decoration:underline;text-underline-offset:3px;">Cancel</a>
            <button type="submit" class="btn-primary">Save changes</button>
        </div>
    </form>
</div>

</x-layouts.admin>
