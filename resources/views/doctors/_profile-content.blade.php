<header class="doctor-profile-hero">
    <div class="doctor-profile-identity">
        @if($profile->profile_photo_path)
            <img class="doctor-profile-photo" src="{{ ($isDoctorPreview ?? false) ? route('doctor.profile.photo') : route('doctors.photo', $profile) }}" alt="Profile photo of {{ $profile->user->name }}">
        @else
            <div class="doctor-profile-photo doctor-profile-photo--placeholder" aria-hidden="true">{{ strtoupper(substr($profile->user->first_name ?: $profile->user->name, 0, 1)) }}</div>
        @endif
        <div class="doctor-profile-heading-copy">
            <span class="doctor-verified-badge"><span aria-hidden="true">✓</span> Verified professional</span>
            <h1>{{ $profile->professional_title }} {{ $profile->user->name }}</h1>
            <p class="doctor-profile-specialty">{{ $profile->specialties->pluck('name')->join(', ') ?: $profile->specialty }}</p>
            <div class="doctor-profile-facts"><span>{{ $profile->city }}</span><span>{{ $profile->years_experience }} years of experience</span>@if($profile->workplace)<span>{{ $profile->workplace }}</span>@endif</div>
        </div>
    </div>
    <div class="doctor-profile-hero-actions">
        @if(auth()->guest() || auth()->user()->hasRole(\App\Models\User::ROLE_PARENT))
            @if($profile->consultationOptions->where('is_active', true)->isNotEmpty())
                <a class="pc-cta pc-cta--primary" href="{{ route('appointments.create', $profile) }}">Book a consultation <span aria-hidden="true">→</span></a>
            @endif
        @else
            <span class="doctor-booking-unavailable">Available to patient accounts</span>
        @endif
    </div>
</header>

<div class="doctor-public-grid">
    <div class="doctor-profile-main-column">
        <section class="doctor-profile-card doctor-profile-about">
            <span class="doctor-profile-kicker">About the professional</span>
            <h2>About</h2>
            <p class="doctor-profile-biography">{{ $profile->biography ?: 'This doctor has not added a biography yet.' }}</p>
        </section>

        <section class="doctor-profile-card">
            <span class="doctor-profile-kicker">Education and training</span>
            <h2>Qualifications</h2>
            <ul class="doctor-profile-qualifications">
                @forelse($profile->qualifications as $qualification)
                    <li><strong>{{ $qualification->degree }}</strong><span>{{ $qualification->institution }}@if($qualification->completion_year) · {{ $qualification->completion_year }}@endif</span></li>
                @empty
                    <li><strong>{{ $profile->qualification }}</strong><span>{{ $profile->institution }}</span></li>
                @endforelse
            </ul>
            @if($profile->languages)<div class="doctor-profile-languages"><strong>Languages</strong><span>{{ implode(', ', $profile->languages) }}</span></div>@endif
        </section>
    </div>

    <aside class="doctor-profile-sidebar">
        <section class="doctor-profile-card doctor-consultation-card">
            <span class="doctor-profile-kicker">Appointments</span>
            <h2>Consultation options</h2>
            @php($activeOptions = $profile->consultationOptions->where('is_active', true))
            <div class="doctor-consultation-options">
                @forelse($activeOptions as $option)
                    <article class="doctor-consultation-option"><div><strong>{{ $option->type === 'online' ? 'Online consultation' : 'In-person consultation' }}</strong><span>{{ $option->duration_minutes }} minutes</span></div><b>@if($option->fee !== null){{ config('app.currency', 'PKR') }} {{ number_format((float)$option->fee, 2) }}@else Fee not specified @endif</b></article>
                @empty
                    <p class="doctor-profile-muted">Consultation options have not been published.</p>
                @endforelse
            </div>
            <p><strong>Consultation fee:</strong> @if($profile->consultation_fee !== null){{ config('app.currency', 'PKR') }} {{ number_format((float)$profile->consultation_fee, 2) }}@else Not set @endif</p>
            @if(auth()->guest() || auth()->user()->hasRole(\App\Models\User::ROLE_PARENT))
                @if($activeOptions->isNotEmpty())
                    @if(!$profile->hasPublishedAvailability())<p class="doctor-profile-muted" role="status">Appointment times are not currently published. Booking will show the latest availability.</p>@endif
                    <a class="pc-cta pc-cta--primary doctor-booking-button" href="{{ route('appointments.create', $profile) }}">Choose an appointment time <span aria-hidden="true">→</span></a>
                @endif
                <a class="pc-cta {{ $activeOptions->isEmpty() ? 'pc-cta--primary' : 'pc-cta--ghost' }} doctor-booking-button" href="{{ route('consultations.create', $profile) }}">Request Consultation <span aria-hidden="true">→</span></a>
            @else
                <p class="doctor-profile-muted">Appointment booking and consultation requests are available to patient accounts.</p>
            @endif
        </section>

        @if($profile->workplace || $activeOptions->contains('type', 'in_person'))
            <section class="doctor-profile-card doctor-profile-contact-card">
                <span class="doctor-profile-kicker">Visit or contact</span>
                @if($profile->workplace)<h2>Workplace</h2><p>{{ $profile->workplace }}@if($profile->workplace_address)<br>{{ $profile->workplace_address }}@endif<br>{{ $profile->city }}</p>@endif
                @if($activeOptions->contains('type', 'in_person'))
                    <h2>Clinic contact</h2>
                    @if($profile->phone)<p><a href="tel:{{ $profile->phone }}">{{ $profile->phone }}</a></p><a class="doctor-contact-link" rel="noopener noreferrer" target="_blank" href="https://wa.me/{{ preg_replace('/\\D+/', '', $profile->phone) }}">Message on WhatsApp</a>@endif
                    <p><a href="mailto:{{ $profile->user->email }}">{{ $profile->user->email }}</a></p>
                @endif
            </section>
        @endif

        <section class="doctor-profile-card doctor-profile-availability-card">
            <span class="doctor-profile-kicker">Usual schedule</span>
            <h2>Regular availability</h2>
            <ul class="doctor-availability-list">@forelse($profile->availabilityPeriods as $period)<li><strong>{{ ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'][$period->day_of_week] }}</strong><span>{{ \Carbon\CarbonImmutable::parse($period->starts_at)->format('g:i A') }} – {{ \Carbon\CarbonImmutable::parse($period->ends_at)->format('g:i A') }}</span></li>@empty<li>Availability not configured</li>@endforelse</ul>
            <p class="doctor-profile-muted">Specific dates may be unavailable. Choose a consultation option to see current times.</p>
        </section>
    </aside>
</div>
