@extends('layouts.auth')
@section('title', 'Book an appointment')
@section('content')
<main class="booking-page">
    <a class="booking-back-link" href="{{ route('doctors.show', $profile) }}">← Back to {{ $profile->user->name }}’s profile</a>
    <section class="booking-doctor-card">
        @if($profile->profile_photo_path)
            <img class="booking-doctor-photo" src="{{ route('doctors.photo', $profile) }}" alt="">
        @else
            <div class="booking-doctor-photo doctor-photo-placeholder" aria-hidden="true">{{ strtoupper(substr($profile->user->first_name ?: $profile->user->name, 0, 1)) }}</div>
        @endif
        <div><span class="doctor-verified-badge">Verified doctor</span><h2>{{ $profile->professional_title }} {{ $profile->user->name }}</h2><p>{{ $profile->specialties->pluck('name')->join(', ') ?: $profile->specialty }} · {{ $profile->city }}</p></div>
        <div class="booking-timezone"><span>Times shown in</span><strong>{{ $profile->timezone }}</strong></div>
    </section>

    @if($profile->consultationOptions->where('is_active', true)->contains('type', 'in_person'))
        <section class="booking-panel"><span class="eyebrow">In-person consultation</span><h2>Clinic information</h2><p>{{ $profile->workplace ?: 'Clinic' }}{{ $profile->workplace_address ? ', '.$profile->workplace_address : '' }}{{ $profile->city ? ', '.$profile->city : '' }}</p>@if($profile->phone)<p><a href="tel:{{ $profile->phone }}">Call {{ $profile->phone }}</a> · <a href="https://wa.me/{{ preg_replace('/\\D+/', '', $profile->phone) }}" target="_blank" rel="noopener noreferrer">WhatsApp</a></p>@endif<p><a href="mailto:{{ $profile->user->email }}">{{ $profile->user->email }}</a></p></section>
    @endif

    @if($profile->consultationOptions->where('is_active', true)->isEmpty() || !$profile->hasPublishedAvailability())
        <section class="booking-empty-state" role="status">
            <span class="booking-empty-icon" aria-hidden="true">◷</span>
            <h2>Online booking is not open yet</h2>
            @if($profile->consultationOptions->where('is_active', true)->isEmpty() && !$profile->hasPublishedAvailability())
                <p>This doctor has not configured a consultation option or published appointment times yet. Please check again later.</p>
            @elseif($profile->consultationOptions->where('is_active', true)->isEmpty())
                <p>This doctor has not configured an active consultation option yet. Please check again later.</p>
            @else
                <p>This doctor has not published appointment times yet. Please check again later.</p>
            @endif
            <a class="btn btn-outline" href="{{ route('doctors.show', $profile) }}">Return to profile</a>
        </section>
    @else
        <section class="booking-panel">
            <div class="booking-progress" aria-label="Booking steps"><span class="is-current"><b>1</b> Choose a time</span><i></i><span><b>2</b> Review</span><i></i><span><b>3</b> Confirm</span></div>
            <div class="booking-panel-heading"><div><span class="eyebrow">Appointment scheduling</span><h2>Choose a consultation and time</h2><p>Online and in-person times are loaded from this doctor's published schedule. Click a time to reserve it for 10 minutes while you review your appointment.</p></div></div>

            <form method="POST" action="{{ route('appointments.review', $profile) }}" id="booking-form">
                @csrf
                <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">
                <input type="hidden" name="starts_at" id="selected-start-at" value="">
                <input type="hidden" name="doctor_appointment_slot_id" id="doctor-appointment-slot-id" value="">
                <div class="booking-controls">
                    <div class="booking-field"><label for="consultation_option_id">Consultation type</label>
                        <select required id="consultation_option_id" name="consultation_option_id">
                            <option value="">Choose a consultation</option>
                            @foreach($profile->consultationOptions as $option)
                                <option value="{{ $option->id }}" data-duration="{{ $option->duration_minutes }}">{{ $option->type === 'online' ? 'Online consultation' : 'In-person consultation' }} · {{ $option->duration_minutes }} min · {{ $option->fee > 0 ? config('app.currency', 'PKR').' '.number_format((float)$option->fee, 2) : 'Free' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="booking-field"><label for="booking-date">Choose another date</label><input id="booking-date" type="date" min="{{ now($profile->timezone)->toDateString() }}" value="{{ now($profile->timezone)->toDateString() }}"><button type="button" class="btn btn-outline btn-small" id="check-availability">Check Availability</button></div>
                </div>

                <div class="booking-slot-heading"><div><h3>Next few days</h3><p id="preview-status" aria-live="polite">Choose a consultation to preview available dates and times.</p></div></div>
                <div id="availability-preview" class="availability-preview"></div>
                <div class="booking-slot-heading"><div><h3>Selected date availability</h3><p id="slot-status" aria-live="polite">Use Check Availability to load times for your selected date.</p></div><span class="slot-duration" id="slot-duration"></span></div>
                <div id="slot-grid" class="booking-slot-grid" aria-label="Available appointment times"><p class="booking-slot-placeholder">Times will appear here after you choose a date.</p></div>
                <p id="slot-detail" class="muted" aria-live="polite"></p>
            </form>
        </section>
    @endif
</main>
@if($profile->consultationOptions->where('is_active', true)->isNotEmpty() && $profile->hasPublishedAvailability())
<script>
(() => {
    const form = document.getElementById('booking-form');
    const type = document.getElementById('consultation_option_id');
    const date = document.getElementById('booking-date');
    const grid = document.getElementById('slot-grid');
    const status = document.getElementById('slot-status');
    const duration = document.getElementById('slot-duration');
    const selectedSlot = document.getElementById('doctor-appointment-slot-id');
    const selectedStart = document.getElementById('selected-start-at');
    const slotDetail = document.getElementById('slot-detail');
    const preview = document.getElementById('availability-preview');
    const previewStatus = document.getElementById('preview-status');
    const checkAvailability = document.getElementById('check-availability');
    let requestNumber = 0;

    async function fetchSlots(dateValue) {
        const url = new URL(@json(route('doctors.slots', $profile)), location.origin);
        url.searchParams.set('consultation_option_id', type.value);
        url.searchParams.set('date', dateValue);
        const response = await fetch(url, {headers: {'Accept': 'application/json'}});
        if (!response.ok) throw new Error('Availability request failed');
        return (await response.json()).slots;
    }

    function addSlotButton(target, slot) {
        const button = document.createElement('button');
        button.type = 'submit'; button.dataset.startsAt = slot.value;
        button.dataset.slotId = slot.slot_id ?? ''; button.dataset.clinicLocation = slot.clinic_location ?? '';
        button.className = 'booking-slot-button'; button.textContent = slot.label;
        button.setAttribute('aria-label', `Reserve ${slot.label}`);
        button.addEventListener('focus', () => {
            slotDetail.textContent = type.selectedOptions[0]?.textContent.startsWith('Online')
                ? 'Meeting link will be shared after the appointment is confirmed.'
                : (slot.clinic_location ? `Clinic: ${slot.clinic_location}` : 'See the clinic contact details above.');
        });
        target.appendChild(button);
    }

    async function loadSlots() {
        const request = ++requestNumber;
        grid.replaceChildren();
        duration.textContent = type.selectedOptions[0]?.dataset.duration ? `${type.selectedOptions[0].dataset.duration} minutes` : '';
        if (!type.value || !date.value) {
            grid.innerHTML = '<p class="booking-slot-placeholder">Choose a consultation and date to see available times.</p>';
            status.textContent = 'Choose a consultation and date to see available times.';
            return;
        }
        status.textContent = 'Checking this doctor’s live availability…';
        grid.innerHTML = '<span class="booking-slot-placeholder">Loading available times…</span>';
        try {
            const slots = await fetchSlots(date.value);
            if (request !== requestNumber) return;
            grid.replaceChildren();
            if (!slots.length) {
                grid.innerHTML = '<p class="booking-slot-placeholder">No times are available on this date. Choose another day.</p>';
                status.textContent = 'No available times for this date.';
                return;
            }
            slots.forEach(slot => addSlotButton(grid, slot));
            status.textContent = `${slots.length} available ${slots.length === 1 ? 'time' : 'times'}. Click one to hold it.`;
        } catch (error) {
            if (request !== requestNumber) return;
            grid.innerHTML = '<p class="booking-slot-placeholder is-error">We could not load times. Please try again.</p>';
            status.textContent = 'Availability could not be loaded.';
        }
    }

    async function loadPreview() {
        preview.replaceChildren();
        if (!type.value) { previewStatus.textContent = 'Choose a consultation to preview available dates and times.'; return; }
        previewStatus.textContent = 'Checking the next four days…';
        const base = new Date(`${date.min}T12:00:00`);
        try {
            let count = 0;
            for (let offset = 0; offset < 4; offset++) {
                const day = new Date(base); day.setDate(base.getDate() + offset);
                const dateValue = `${day.getFullYear()}-${String(day.getMonth()+1).padStart(2,'0')}-${String(day.getDate()).padStart(2,'0')}`;
                const slots = await fetchSlots(dateValue);
                if (!slots.length) continue;
                count += slots.length;
                const section = document.createElement('section'); section.className = 'availability-day';
                const heading = document.createElement('h4'); heading.textContent = day.toLocaleDateString(undefined,{weekday:'long',month:'short',day:'numeric'}); section.appendChild(heading);
                const times = document.createElement('div'); times.className = 'booking-slot-grid';
                slots.forEach(slot => addSlotButton(times, slot)); section.appendChild(times); preview.appendChild(section);
            }
            previewStatus.textContent = count ? `${count} real available time(s) across the next four days. Choose a time or check another date.` : 'No open times in the next four days. Choose another date to check.';
        } catch (error) { previewStatus.textContent = 'Availability preview could not load. Choose a date and check again.'; }
    }

    type.addEventListener('change', () => { loadPreview(); loadSlots(); });
    checkAvailability.addEventListener('click', loadSlots);
    date.addEventListener('change', () => { status.textContent = 'Select Check Availability to load the chosen date.'; });
    loadPreview();
    form.addEventListener('submit', (event) => {
        const submitter = event.submitter;
        if (!submitter?.dataset.startsAt) {
            event.preventDefault();
            status.textContent = 'Choose an available time before continuing.';
            return;
        }
        selectedStart.value = submitter.dataset.startsAt;
        selectedSlot.value = submitter?.dataset.slotId || '';
        grid.querySelectorAll('button').forEach(button => { button.disabled = true; });
        status.textContent = 'Reserving your selected time…';
    });
})();
</script>
@endif
@endsection
