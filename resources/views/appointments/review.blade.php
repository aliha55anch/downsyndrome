@extends('layouts.public')

@section('title', 'Review appointment')
@section('body_class', 'care-page booking-page')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/care.css') }}">
@endpush

@section('content')
<section class="care-hero care-hero--compact">
    <div class="care-hero__grain" aria-hidden="true"></div>
    <div class="container">
        <a class="bk-back" href="{{ route('appointments.create', $profile) }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
            Back to choose a time
        </a>

        <div class="care-hero__inner bk-hero__inner">
            <div class="care-hero__copy">
                <span class="care-eyebrow">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3 4.5 6v5.4c0 4.7 3.2 8.1 7.5 9.6 4.3-1.5 7.5-4.9 7.5-9.6V6L12 3Z"/><path d="m8.7 12.2 2.1 2.1 4.5-4.5"/></svg>
                    Review appointment
                </span>
                <h1>One last look before <span>we confirm</span>.</h1>
                <p>Check the date, consultation type, and fee below. Your time is held while you review, so you can continue with confidence.</p>

                <ol class="bk-steps" aria-label="Booking steps">
                    <li class="is-done"><span>1</span><strong>Choose a time</strong></li>
                    <li class="is-current"><span>2</span><strong>Review</strong></li>
                    <li><span>3</span><strong>Confirm</strong></li>
                </ol>
            </div>

            <aside class="care-hero__art" aria-label="Reserved time">
                <div class="booking-hold-card" data-expires-at="{{ $hold->expires_at->toIso8601String() }}">
                    <span class="booking-hold-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                    </span>
                    <span class="care-panel__badge">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                        Time reserved
                    </span>
                    <h3>Your time is held for you</h3>
                    <p>Other patients can&rsquo;t book this time while you review. Complete the next step before the hold expires.</p>
                    <p class="booking-countdown-label">Time remaining</p>
                    <strong class="booking-countdown" id="hold-countdown">10:00</strong>
                    <small>Hold ends {{ $hold->expires_at->timezone($profile->timezone)->format('g:i A') }} {{ $profile->timezone }}</small>
                </div>
            </aside>
        </div>
    </div>
</section>

<main class="bk-main">
    <div class="container bk-layout">
        <div class="bk-content">
            <section class="bk-panel booking-review-panel">
                <div class="bk-panel__head">
                    <span class="care-kicker">Almost done</span>
                    <h2>Review your appointment</h2>
                    <p>Check the details before continuing. You can still choose another time if something needs to change.</p>
                </div>

                <div class="booking-review-grid">
                    <article class="booking-summary-card">
                        <div class="booking-summary-doctor">
                            @if($profile->profile_photo_path)
                                <img class="bk-doctor-photo" src="{{ route('doctors.photo', $profile) }}" alt="Profile photo of {{ $profile->user->name }}">
                            @else
                                <span class="bk-doctor-photo bk-doctor-photo--placeholder" aria-hidden="true">{{ strtoupper(substr($profile->user->first_name ?: $profile->user->name, 0, 1)) }}</span>
                            @endif
                            <div>
                                <span class="care-kicker">Doctor</span>
                                <h3>{{ $profile->professional_title }} {{ $profile->user->name }}</h3>
                                <p>{{ $profile->specialties->pluck('name')->join(', ') ?: $profile->specialty }} &middot; {{ $profile->city }}</p>
                            </div>
                        </div>

                        <dl class="booking-summary-list">
                            <div>
                                <dt>Consultation</dt>
                                <dd>{{ $option->type === 'online' ? 'Online' : 'In person' }} &middot; {{ $option->duration_minutes }} minutes</dd>
                            </div>
                            <div>
                                <dt>Date and time</dt>
                                <dd>{{ $localStart->format('l, F j · g:i A') }} ({{ $profile->timezone }})</dd>
                            </div>
                            <div>
                                <dt>Consultation fee</dt>
                                <dd>{{ $option->fee > 0 ? config('app.currency', 'PKR').' '.number_format((float) $option->fee, 2) : 'No payment required' }}</dd>
                            </div>
                            @if($option->type === 'online')
                                <div>
                                    <dt>Meeting details</dt>
                                    <dd>Meeting link will be available after the appointment is confirmed.</dd>
                                </div>
                            @elseif($hold->doctorSlot?->clinic_location)
                                <div>
                                    <dt>Clinic location</dt>
                                    <dd>{{ $hold->doctorSlot->clinic_location }}</dd>
                                </div>
                            @endif
                        </dl>
                    </article>

                    <aside class="booking-review-note">
                        <span class="booking-review-note__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 4.5 6v5.4c0 4.7 3.2 8.1 7.5 9.6 4.3-1.5 7.5-4.9 7.5-9.6V6L12 3Z"/><path d="m8.7 12.2 2.1 2.1 4.5-4.5"/></svg>
                        </span>
                        <h3>Booked with confidence</h3>
                        <p>Your details are only shared with the professional you chose. You can review or reschedule from your appointments page later.</p>
                    </aside>
                </div>

                <div class="booking-review-actions">
                    <form method="POST" action="{{ route('appointments.store', $profile) }}" id="confirm-booking-form">
                        @csrf
                        <input type="hidden" name="consultation_option_id" value="{{ $option->id }}">
                        <input type="hidden" name="starts_at" value="{{ $localStart->format('Y-m-d\\TH:i') }}">
                        <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">
                        <input type="hidden" name="doctor_appointment_slot_id" value="{{ $hold->doctor_appointment_slot_id }}">
                        @if($option->fee > 0)
                            <p>Safepay hosted checkout opens after you confirm. The appointment is confirmed only after its signed payment notification is verified.</p>
                        @else
                            <p>Confirming books this reserved time. No payment is required.</p>
                        @endif
                        <div class="booking-review-actions__buttons">
                            <button class="care-btn care-btn--primary" id="confirm-booking" type="submit">{{ $option->fee > 0 ? 'Confirm and continue to payment' : 'Confirm appointment' }}</button>
                        </div>
                    </form>
                    <form method="POST" action="{{ route('appointments.slot-holds.release', $hold) }}">
                        @csrf
                        <button class="care-btn care-btn--outline" type="submit">Choose another time</button>
                    </form>
                </div>
            </section>
        </div>

        <aside class="bk-aside">
            <section class="bk-aside-card">
                <span class="bk-aside-card__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 4.5 6v5.4c0 4.7 3.2 8.1 7.5 9.6 4.3-1.5 7.5-4.9 7.5-9.6V6L12 3Z"/><path d="m8.7 12.2 2.1 2.1 4.5-4.5"/></svg>
                </span>
                <h3>What happens next</h3>
                <ol class="bk-next-list">
                    <li><span>1</span><div><strong>Confirm your booking</strong><p>Review the details and continue.</p></div></li>
                    <li><span>2</span><div><strong>Payment, if required</strong><p>Paid consultations use secure hosted checkout.</p></div></li>
                    <li><span>3</span><div><strong>Appointment confirmed</strong><p>Your appointment and meeting details appear in your account.</p></div></li>
                </ol>
            </section>

            <section class="bk-aside-card bk-aside-card--help">
                <span class="bk-aside-card__icon is-amber" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2Z"/></svg>
                </span>
                <h3>Need to change something?</h3>
                <p>You can choose another time before confirming, or contact our team for help.</p>
                <a class="care-btn care-btn--outline care-btn--small" href="{{ route('contact.create') }}">Contact support</a>
            </section>
        </aside>
    </div>
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
