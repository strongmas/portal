<x-portal-layout title="Book Inspection">
    <div style="max-width:560px;">
        <div class="mb-6">
            <h1 style="font-size:22px;font-weight:700;color:var(--fg);margin:0 0 4px;">Book a Site Inspection</h1>
            <p style="color:var(--fg-dim);font-size:14px;margin:0;">Choose a property and your preferred date and time</p>
        </div>

        <form method="POST" action="{{ route('appointments.store') }}"
              style="background:var(--bg-card);border:1px solid var(--line);border-radius:16px;padding:28px;">
            @csrf

            <div class="mb-5">
                <x-input-label for="property_id" value="Property" />
                <select id="property_id" name="property_id"
                        style="width:100%;padding:10px 12px;border:1px solid var(--line);border-radius:10px;background:var(--bg);color:var(--fg);font-size:14px;margin-top:6px;"
                        required>
                    <option value="">Select a property…</option>
                    @foreach($properties as $property)
                    <option value="{{ $property->id }}" {{ old('property_id', $selected?->id) == $property->id ? 'selected' : '' }}>
                        {{ $property->development_name }} — {{ $property->address }}
                    </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('property_id')" />
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;" class="mb-5">
                <div>
                    <x-input-label for="preferred_date" value="Preferred Date" />
                    <x-text-input id="preferred_date" name="preferred_date" type="date"
                                  :value="old('preferred_date')"
                                  min="{{ now()->addDay()->format('Y-m-d') }}"
                                  style="margin-top:6px;" required />
                    <x-input-error :messages="$errors->get('preferred_date')" />
                </div>
                <div>
                    <x-input-label for="preferred_time" value="Preferred Time" />
                    <x-text-input id="preferred_time" name="preferred_time" type="time"
                                  :value="old('preferred_time', '09:00')"
                                  style="margin-top:6px;" required />
                    <x-input-error :messages="$errors->get('preferred_time')" />
                </div>
            </div>

            <div class="mb-6">
                <x-input-label for="notes" value="Additional Notes (optional)" />
                <textarea id="notes" name="notes" rows="3"
                          style="width:100%;padding:10px 12px;border:1px solid var(--line);border-radius:10px;background:var(--bg);color:var(--fg);font-size:14px;margin-top:6px;resize:vertical;"
                          placeholder="Any specific questions or requirements?">{{ old('notes') }}</textarea>
            </div>

            <x-primary-button style="width:100%;justify-content:center;">
                Submit Inspection Request
            </x-primary-button>
        </form>
    </div>
</x-portal-layout>
