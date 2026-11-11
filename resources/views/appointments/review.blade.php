@extends('layouts.dashboard')
@section('title', 'Review appointment')
@section('heading', 'Review appointment')
@section('dashboard-content')
<main class="booking-page">
    <section class="booking-panel booking-review-panel">
        <div class="booking-progress" aria-label="Booking steps"><span><b>1</b> Choose a time</span><i></i><span class="is-current"><b>2</b> Review</span><i></i><span><b>3</b> Confirm</span></div>
        <div class="booking-review-heading"><span class="doctor-verified-badge">Time reserved</span><h2>Review your appointment</h2><p>Check the details before continuing.</p></div>

        <div class="booking-review-grid">
            <article class="booking-summary-card">
                <span class="eyebrow">Doctor</span><h3>{{ $profile->professional_title }} {{ $profile->user->name }}</h3>
                <p>{{ $profile->specialties->pluck('name')->join(', ') ?: $profile->specialty }} · {{ $profile->city }}</p>
                <dl class="booking-summary-list">
                    <div><dt>Consultation</dt><dd>{{ $option->type === 'online' ? 'Online' : 'In person' }} · {{ $option->duration_minutes }} minutes</dd></div>
                    <div><dt>Date and time</dt><dd>{{ $localStart->format('l, F j · g:i A') }} ({{ $profile->timezone }})</dd></div>
                    <div><dt>Consultation fee</dt><dd>{{ $option->fee > 0 ? config('app.currency', 'PKR').' '.number_format((float) $option->fee, 2) : 'No payment required' }}</dd></div>
                    @if($option->type === 'online')<div><dt>Meeting details</dt><dd>Meeting link will be available after the appointment is confirmed.</dd></div>@elseif($hold->doctorSlot?->clinic_location)<div><dt>Clinic location</dt><dd>{{ $hold->doctorSlot->clinic_location }}</dd></div>@endif
                </dl>
            </article>
            <aside class="booking-hold-card" data-expires-at="{{ $hold->expires_at->toIso8601String() }}">
                <span class="booking-hold-icon" aria-hidden="true">◷</span><h3>Your time is held for you</h3>
                <p>Other patients can’t book this time while you review. Complete the next step before the hold expires.</p>
                <p class="booking-countdown-label">Time remaining</p><strong class="booking-countdown" id="hold-countdown">10:00</strong>
                <small>Hold ends {{ $hold->expires_at->timezone($profile->timezone)->format('g:i A') }} {{ $profile->timezone }}</small>
            </aside>
        </div>

        <div class="booking-review-actions">
            <form method="POST" action="{{ route('appointments.store', $profile) }}" id="confirm-booking-form">@csrf
                <input type="hidden" name="consultation_option_id" value="{{ $option->id }}">
                <input type="hidden" name="starts_at" value="{{ $localStart->format('Y-m-d\\TH:i') }}">
                <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">
                <input type="hidden" name="doctor_appointment_slot_id" value="{{ $hold->doctor_appointment_slot_id }}">
                @if($option->fee > 0)<p>Safepay hosted checkout opens after you confirm. The appointment is confirmed only after its signed payment notification is verified.</p>@else<p>Confirming books this reserved time. No payment is required.</p>@endif
                <button class="btn btn-blue" id="confirm-booking" type="submit">{{ $option->fee > 0 ? 'Confirm and continue to payment' : 'Confirm appointment' }}</button>
            </form>
            <form method="POST" action="{{ route('appointments.slot-holds.release', $hold) }}">@csrf<button class="btn btn-outline" type="submit">Choose another time</button></form>
        </div>
    </section>
</main>
<script>
(() => {
    const card = document.querySelector('.booking-hold-card');
    const countdown = document.getElementById('hold-countdown');
    const confirm = document.getElementById('confirm-booking');
    const expires = new Date(card.dataset.expiresAt).getTime();
    const update = () => {
        const seconds = Math.max(0, Math.floor((expires - Date.now()) / 1000));
        countdown.textContent = `${String(Math.floor(seconds / 60)).padStart(2, '0')}:${String(seconds % 60).padStart(2, '0')}`;
        if (seconds === 0) {
            card.classList.add('is-expired');
            card.querySelector('h3').textContent = 'Your time hold expired';
            card.querySelector('p').textContent = 'Choose another available time to continue.';
            confirm.disabled = true;
        }
    };
    update();
    window.setInterval(update, 1000);
})();
</script>
@endsection
