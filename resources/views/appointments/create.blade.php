@extends('layouts.public')

@section('title', 'Book an appointment')
@section('body_class', 'care-page booking-page')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/care.css') }}">
@endpush

@section('content')
<section class="care-hero care-hero--booking">
    <div class="care-hero__grain" aria-hidden="true"></div>
    <div class="container">
        <a class="bk-back" href="{{ route('doctors.show', $profile) }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
            Back to {{ $profile->user->name }}&rsquo;s profile
        </a>

        <div class="care-hero__inner bk-hero__inner">
            <div class="care-hero__copy">
                <span class="care-eyebrow">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/><path d="m9 16 2 2 4-4"/></svg>
                    Book appointment
                </span>
                <h1>Choose a time. <span>We&rsquo;ll take care of the rest.</span></h1>
                <p>Select a consultation type and an available time with {{ $profile->professional_title }} {{ $profile->user->name }}. Your chosen slot is held for 10 minutes while you review the details.</p>

                <ol class="bk-steps" aria-label="Booking steps">
                    <li class="is-current"><span>1</span><strong>Choose a time</strong></li>
                    <li><span>2</span><strong>Review</strong></li>
                    <li><span>3</span><strong>Confirm</strong></li>
                </ol>
            </div>

            <article class="bk-doctor-card" aria-label="Doctor details">
                <div class="bk-doctor-card__top">
                    @if($profile->profile_photo_path)
                        <img class="bk-doctor-photo" src="{{ route('doctors.photo', $profile) }}" alt="Profile photo of {{ $profile->user->name }}">
                    @else
                        <span class="bk-doctor-photo bk-doctor-photo--placeholder" aria-hidden="true">{{ strtoupper(substr($profile->user->first_name ?: $profile->user->name, 0, 1)) }}</span>
                    @endif
                    <span class="bk-verified">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3 4.5 6v5.4c0 4.7 3.2 8.1 7.5 9.6 4.3-1.5 7.5-4.9 7.5-9.6V6L12 3Z"/><path d="m8.7 12.2 2.1 2.1 4.5-4.5"/></svg>
                        Verified professional
                    </span>
                </div>
                <h2>{{ $profile->professional_title }} {{ $profile->user->name }}</h2>
                <p class="bk-doctor-card__specialty">{{ $profile->specialties->pluck('name')->join(', ') ?: $profile->specialty }}</p>
                <dl class="bk-doctor-card__facts">
                    <div>
                        <dt>Location</dt>
                        <dd>{{ $profile->city ?: 'Online' }}</dd>
                    </div>
                    <div>
                        <dt>Times shown in</dt>
                        <dd>{{ $profile->timezone }}</dd>
                    </div>
                </dl>
                <a class="bk-doctor-card__link" href="{{ route('doctors.show', $profile) }}">View full profile</a>
            </article>
        </div>
    </div>
</section>

<main class="bk-main">
    <div class="container bk-layout">
        <div class="bk-content">
            @if($profile->consultationOptions->where('is_active', true)->isEmpty() || !$profile->hasPublishedAvailability())
                <section class="bk-empty" role="status">
                    <span class="bk-empty__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                    </span>
                    <span class="care-kicker">Booking not open</span>
                    <h2>Online booking is not open yet</h2>
                    @if($profile->consultationOptions->where('is_active', true)->isEmpty() && !$profile->hasPublishedAvailability())
                        <p>This doctor has not configured a consultation option or published appointment times yet. Please check again later.</p>
                    @elseif($profile->consultationOptions->where('is_active', true)->isEmpty())
                        <p>This doctor has not configured an active consultation option yet. Please check again later.</p>
                    @else
                        <p>This doctor has not published appointment times yet. Please check again later.</p>
                    @endif
                    <div class="bk-empty__actions">
                        <a class="care-btn care-btn--primary" href="{{ route('doctors.show', $profile) }}">Return to profile</a>
                        <a class="care-btn care-btn--outline" href="{{ route('doctors.index') }}">Browse professionals</a>
                    </div>
                </section>
            @else
                <section class="bk-panel">
                    <div class="bk-panel__head">
                        <div>
                            <span class="care-kicker">Appointment scheduling</span>
                            <h2>Choose a consultation and time</h2>
                            <p>Online and in-person times are loaded from this doctor&rsquo;s published schedule. Click a time to reserve it for 10 minutes while you review your appointment.</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('appointments.review', $profile) }}" id="booking-form" class="bk-form">
                        @csrf
                        <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">
                        <input type="hidden" name="starts_at" id="selected-start-at" value="">
                        <input type="hidden" name="doctor_appointment_slot_id" id="doctor-appointment-slot-id" value="">

                        <div class="booking-controls bk-controls">
                            <div class="booking-field bk-field">
                                <label for="consultation_option_id">Consultation type</label>
                                <select required id="consultation_option_id" name="consultation_option_id">
                                    <option value="">Choose a consultation</option>
                                    @foreach($profile->consultationOptions as $option)
                                        <option value="{{ $option->id }}" data-duration="{{ $option->duration_minutes }}">{{ $option->type === 'online' ? 'Online consultation' : 'In-person consultation' }} · {{ $option->duration_minutes }} min · {{ $option->fee > 0 ? config('app.currency', 'PKR').' '.number_format((float)$option->fee, 2) : 'Free' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="booking-field bk-field bk-field--date">
                                <label for="booking-date">Choose another date</label>
                                <div class="bk-date-row">
                                    <input id="booking-date" type="date" min="{{ now($profile->timezone)->toDateString() }}" value="{{ now($profile->timezone)->toDateString() }}">
                                    <button type="button" class="care-btn care-btn--outline care-btn--small" id="check-availability">Check availability</button>
                                </div>
                            </div>
                        </div>

                        <div class="bk-slot-section">
                            <div class="bk-slot-head">
                                <div>
                                    <h3>Next few days</h3>
                                    <p id="preview-status" aria-live="polite">Choose a consultation to preview available dates and times.</p>
                                </div>
                            </div>
                            <div id="availability-preview" class="availability-preview"></div>
                        </div>

                        <div class="bk-slot-section">
                            <div class="bk-slot-head">
                                <div>
                                    <h3>Selected date availability</h3>
                                    <p id="slot-status" aria-live="polite">Use Check Availability to load times for your selected date.</p>
                                </div>
                                <span class="slot-duration" id="slot-duration"></span>
                            </div>
                            <div id="slot-grid" class="booking-slot-grid" aria-label="Available appointment times">
                                <p class="booking-slot-placeholder">Times will appear here after you choose a date.</p>
                            </div>
                            <p id="slot-detail" class="bk-slot-detail" aria-live="polite"></p>
                        </div>
                    </form>
                </section>
            @endif
        </div>

        <aside class="bk-aside">
            <section class="bk-aside-card">
                <span class="bk-aside-card__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 4.5 6v5.4c0 4.7 3.2 8.1 7.5 9.6 4.3-1.5 7.5-4.9 7.5-9.6V6L12 3Z"/><path d="m8.7 12.2 2.1 2.1 4.5-4.5"/></svg>
                </span>
                <h3>What happens next</h3>
                <ol class="bk-next-list">
                    <li><span>1</span><div><strong>Choose a time</strong><p>Pick a consultation and an available slot.</p></div></li>
                    <li><span>2</span><div><strong>Review the details</strong><p>Check the date, fee, and consultation type.</p></div></li>
                    <li><span>3</span><div><strong>Confirm</strong><p>Your appointment is booked once confirmed.</p></div></li>
                </ol>
            </section>

            @if($profile->consultationOptions->where('is_active', true)->contains('type', 'in_person'))
                <section class="bk-aside-card bk-aside-card--clinic">
                    <span class="care-kicker">In-person consultation</span>
                    <h3>Clinic information</h3>
                    <p>{{ $profile->workplace ?: 'Clinic' }}{{ $profile->workplace_address ? ', '.$profile->workplace_address : '' }}{{ $profile->city ? ', '.$profile->city : '' }}</p>
                    @if($profile->phone)
                        <p class="bk-clinic-links">
                            <a href="tel:{{ $profile->phone }}">Call {{ $profile->phone }}</a>
                            <a href="https://wa.me/{{ preg_replace('/\\D+/', '', $profile->phone) }}" target="_blank" rel="noopener noreferrer">WhatsApp</a>
                        </p>
                    @endif
                    <p class="bk-clinic-links"><a href="mailto:{{ $profile->user->email }}">{{ $profile->user->email }}</a></p>
                </section>
            @endif

            <section class="bk-aside-card bk-aside-card--help">
                <span class="bk-aside-card__icon is-amber" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2Z"/></svg>
                </span>
                <h3>Questions before booking?</h3>
                <p>Our team can help with appointment types, payments, or finding the right professional.</p>
                <a class="care-btn care-btn--outline care-btn--small" href="{{ route('contact.create') }}">Contact support</a>
            </section>
        </aside>
    </div>
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
