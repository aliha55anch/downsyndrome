@extends('layouts.public')

@section('title', 'My appointments')
@section('body_class', 'care-page appointments-page')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/care.css') }}">
@endpush

@section('content')
<section class="care-hero care-hero--compact">
    <div class="care-hero__grain" aria-hidden="true"></div>
    <div class="container care-hero__inner">
        <div class="care-hero__copy">
            <span class="care-eyebrow">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                Your care
            </span>
            <h1>Upcoming <span>appointments</span></h1>
            <p>Review bookings, payment progress, and appointment details &mdash; all in one private place.</p>
            <div class="care-hero__actions">
                <a class="care-btn care-btn--primary" href="{{ route('doctors.index') }}">
                    Book another appointment
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </a>
                <a class="care-btn care-btn--ghost" href="{{ route('appointments.history') }}">Appointment history</a>
            </div>
            <dl class="care-hero__stats">
                <div class="care-hero__stat">
                    <dt>Upcoming</dt>
                    <dd>{{ $appointments->total() }}</dd>
                </div>
                <div class="care-hero__stat">
                    <dt>Needs attention</dt>
                    <dd>{{ $attention->total() }}</dd>
                </div>
                <div class="care-hero__stat">
                    <dt>Private space</dt>
                    <dd>Secure</dd>
                </div>
            </dl>
        </div>

        <aside class="care-hero__art" aria-label="Appointment overview">
            <div class="care-panel care-panel--preview">
                <span class="care-panel__badge">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3 4.5 6v5.4c0 4.7 3.2 8.1 7.5 9.6 4.3-1.5 7.5-4.9 7.5-9.6V6L12 3Z"/><path d="m8.7 12.2 2.1 2.1 4.5-4.5"/></svg>
                    Your private space
                </span>
                <div class="care-preview-row">
                    <span class="care-preview-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/><path d="m9 16 2 2 4-4"/></svg>
                    </span>
                    <span><strong>Confirmed appointments</strong><small>Times and meeting details</small></span>
                </div>
                <div class="care-preview-line"></div>
                <div class="care-preview-row">
                    <span class="care-preview-icon is-amber" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9 2.4 17.1A2 2 0 0 0 4.1 20h15.8a2 2 0 0 0 1.7-2.9L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg>
                    </span>
                    <span><strong>Anything to action</strong><small>Payments and cancellation reviews</small></span>
                </div>
                <div class="care-preview-row">
                    <span class="care-preview-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a4 4 0 0 1 8 0v2"/></svg>
                    </span>
                    <span><strong>Online or in person</strong><small>All consultation types together</small></span>
                </div>
            </div>
        </aside>
    </div>
</section>

<section class="care-body care-lift">
    <div class="container">
        <section class="appt-section">
            <div class="appt-section__head">
                <div>
                    <span class="care-kicker">Your schedule</span>
                    <h2>Confirmed and scheduled</h2>
                    <p>Your upcoming confirmed appointments, ordered by date.</p>
                </div>
                <span class="care-result-pill"><strong>{{ $appointments->total() }}</strong> {{ \Illuminate\Support\Str::plural('appointment', $appointments->total()) }}</span>
            </div>

            <div class="appt-list">
                @forelse($appointments as $appointment)
                    @php($payment = $appointment->payments->first())
                    <article class="appt-card">
                        <div class="appt-card__main">
                            <div class="appt-card__doctor">
                                @if($appointment->doctorProfile->profile_photo_path)
                                    <img class="appt-avatar" src="{{ route('doctors.photo', $appointment->doctorProfile) }}" alt="Profile photo of {{ $appointment->doctorProfile->user->name }}">
                                @else
                                    <span class="appt-avatar appt-avatar--placeholder" aria-hidden="true">{{ strtoupper(substr($appointment->doctorProfile->user->first_name ?: $appointment->doctorProfile->user->name, 0, 1)) }}</span>
                                @endif
                                <div>
                                    <h3>{{ $appointment->doctorProfile->professional_title }} {{ $appointment->doctorProfile->user->name }}</h3>
                                    <p>{{ $appointment->doctorProfile->headline ?: ($appointment->doctorProfile->specialties->pluck('name')->join(', ') ?: 'Professional consultancy') }}</p>
                                </div>
                            </div>

                            <div class="appt-card__meta">
                                <div class="appt-meta">
                                    <span class="appt-meta__icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                                    </span>
                                    <span><small>Date and time</small><strong>{{ $appointment->localStartsAt()->format('D, M j, Y · g:i A') }}</strong><em>{{ $appointment->timezone }}</em></span>
                                </div>
                                <div class="appt-meta">
                                    <span class="appt-meta__icon is-amber" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M4 12h16M4 17h10"/></svg>
                                    </span>
                                    <span><small>Reference</small><strong>LS-APT-{{ $appointment->id }}</strong><em>{{ ucfirst(str_replace('_', ' ', $appointment->consultation_type)) }}</em></span>
                                </div>
                                <div class="appt-meta">
                                    <span class="appt-meta__icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                                    </span>
                                    <span><small>Payment</small><strong>{{ $payment?->status ? ucfirst(str_replace('_', ' ', $payment->status)) : 'Not required' }}</strong><em>Status: {{ ucfirst(str_replace('_', ' ', $appointment->status)) }}</em></span>
                                </div>
                            </div>
                        </div>

                        <div class="appt-card__footer">
                            @if($appointment->consultation_type === 'online' && $appointment->meeting_link && (! $payment || $payment->status === \App\Models\PaymentTransaction::PAID))
                                <a class="care-btn care-btn--primary care-btn--small" href="{{ $appointment->meeting_link }}" target="_blank" rel="noopener noreferrer">
                                    Join meeting
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 3h6v6M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></svg>
                                </a>
                            @endif
                            <a class="care-btn care-btn--outline care-btn--small" href="{{ route('appointments.show', $appointment) }}">View details</a>
                        </div>
                    </article>
                @empty
                    <div class="care-empty">
                        <span class="care-empty__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/><path d="m9 16 2 2 4-4"/></svg>
                        </span>
                        <h3>No upcoming confirmed appointments</h3>
                        <p>You do not have upcoming confirmed appointments. Find an available doctor or review your appointment history.</p>
                        <div class="care-empty__actions">
                            <a class="care-btn care-btn--primary" href="{{ route('doctors.index') }}">Find an available doctor</a>
                            <a class="care-btn care-btn--outline" href="{{ route('appointments.history') }}">Appointment history</a>
                        </div>
                    </div>
                @endforelse
            </div>

            {{ $appointments->links('pagination.care', ['label' => 'appointment', 'ariaLabel' => 'Upcoming appointments pagination']) }}
        </section>

        <section class="appt-section appt-section--attention">
            <div class="appt-section__head">
                <div>
                    <span class="care-kicker">Action needed</span>
                    <h2>Needs attention</h2>
                    <p>Appointments waiting for payment, confirmation, or a cancellation review.</p>
                </div>
                <span class="care-result-pill care-result-pill--amber"><strong>{{ $attention->total() }}</strong> {{ \Illuminate\Support\Str::plural('appointment', $attention->total()) }}</span>
            </div>

            <div class="appt-list">
                @forelse($attention as $appointment)
                    @php($payment = $appointment->payments->first())
                    <article class="appt-card appt-card--attention">
                        <div class="appt-card__main">
                            <div class="appt-card__doctor">
                                @if($appointment->doctorProfile->profile_photo_path)
                                    <img class="appt-avatar" src="{{ route('doctors.photo', $appointment->doctorProfile) }}" alt="Profile photo of {{ $appointment->doctorProfile->user->name }}">
                                @else
                                    <span class="appt-avatar appt-avatar--placeholder" aria-hidden="true">{{ strtoupper(substr($appointment->doctorProfile->user->first_name ?: $appointment->doctorProfile->user->name, 0, 1)) }}</span>
                                @endif
                                <div>
                                    <h3>{{ $appointment->doctorProfile->professional_title }} {{ $appointment->doctorProfile->user->name }}</h3>
                                    <p>{{ $appointment->localStartsAt()->format('D, M j, Y · g:i A') }} &middot; Reference LS-APT-{{ $appointment->id }}</p>
                                </div>
                            </div>

                            <div class="appt-card__meta">
                                <div class="appt-meta">
                                    <span class="appt-meta__icon is-amber" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9 2.4 17.1A2 2 0 0 0 4.1 20h15.8a2 2 0 0 0 1.7-2.9L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg>
                                    </span>
                                    <span><small>Appointment status</small><strong>{{ ucfirst(str_replace('_', ' ', $appointment->status)) }}</strong><em>Payment: {{ $payment?->status ? ucfirst(str_replace('_', ' ', $payment->status)) : 'Not required' }}</em></span>
                                </div>
                                @if($appointment->status === \App\Models\Appointment::PENDING_CANCELLATION)
                                    <div class="appt-meta">
                                        <span class="appt-meta__icon" aria-hidden="true">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                                        </span>
                                        <span><small>Cancellation request</small><strong>Awaiting review</strong><em>Your appointment remains scheduled until a decision is recorded.</em></span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="appt-card__footer">
                            <a class="care-btn care-btn--primary care-btn--small" href="{{ route('appointments.show', $appointment) }}">View details</a>
                        </div>
                    </article>
                @empty
                    <div class="care-empty care-empty--soft">
                        <span class="care-empty__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                        </span>
                        <h3>Nothing needs your attention</h3>
                        <p>No appointments are waiting for payment or review. Everything is up to date.</p>
                    </div>
                @endforelse
            </div>

            {{ $attention->links('pagination.care', ['label' => 'appointment', 'ariaLabel' => 'Appointments needing attention pagination']) }}
        </section>

        <div class="care-help-strip">
            <span class="care-help-strip__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2Z"/></svg>
            </span>
            <p><strong>Need help with a booking?</strong> Visit the contact page if you have a question about an appointment, payment, or your account.</p>
            <a class="care-btn care-btn--outline care-btn--small" href="{{ route('contact.create') }}">Contact support</a>
        </div>
    </div>
</section>
@endsection
