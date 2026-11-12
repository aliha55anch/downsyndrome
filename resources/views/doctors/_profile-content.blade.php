<header class="doctor-profile-header">
    @if($profile->profile_photo_path)
        <img class="public-doctor-photo" src="{{ ($isDoctorPreview ?? false) ? route('doctor.profile.photo') : route('doctors.photo', $profile) }}" alt="Profile photo of {{ $profile->user->name }}">
    @else
        <div class="public-doctor-photo doctor-photo-placeholder" aria-hidden="true">{{ strtoupper(substr($profile->user->first_name ?: $profile->user->name, 0, 1)) }}</div>
    @endif
    <div><span class="doctor-verified-badge">Verified doctor</span><h1>{{ $profile->professional_title }} {{ $profile->user->name }}</h1>
        <p>{{ $profile->specialties->pluck('name')->join(', ') ?: $profile->specialty }} · {{ $profile->city }}</p><p>{{ $profile->years_experience }} years of experience</p>
    </div>
</header>
<div class="doctor-public-grid">
    <section class="doctor-panel">
        <h2>About</h2><p>{{ $profile->biography ?: 'This doctor has not added a biography yet.' }}</p>
        <h2>Qualifications</h2>
        <ul>@forelse($profile->qualifications as $qualification)<li>{{ $qualification->degree }} — {{ $qualification->institution }}@if($qualification->completion_year), {{ $qualification->completion_year }}@endif</li>@empty<li>{{ $profile->qualification }} — {{ $profile->institution }}</li>@endforelse</ul>
        @if($profile->languages)<p><strong>Languages:</strong> {{ implode(', ', $profile->languages) }}</p>@endif
    </section>
    <aside class="doctor-panel">
        <h2>Consultation options</h2>
        @php($activeOptions = $profile->consultationOptions->where('is_active', true))
        @forelse($activeOptions as $option)
            <p><strong>{{ $option->type === 'online' ? 'Online' : 'In person' }}</strong><br>{{ $option->duration_minutes }} minutes · @if($option->fee !== null){{ config('app.currency', 'PKR') }} {{ number_format((float)$option->fee, 2) }}@else Fee not specified @endif</p>
        @empty
            <p>Consultation options have not been published.</p>
        @endforelse
        @if(auth()->guest() || auth()->user()->hasRole(\App\Models\User::ROLE_PARENT))
            @if($activeOptions->isEmpty())
                <p role="status">This doctor has not published consultation options yet.</p>
                <button class="btn btn-outline" type="button" disabled aria-disabled="true">Booking unavailable</button>
            @else
                @if(!$profile->hasPublishedAvailability())<p role="status">Appointment times are not currently published. You can still open booking to see the latest availability status.</p>@endif
                <a class="btn btn-blue" href="{{ route('appointments.create', $profile) }}">Book appointment</a>
            @endif
        @else
            <p class="hint">Appointment booking is available to patient accounts.</p>
        @endif
        @if($profile->workplace)<h2>Workplace</h2><p>{{ $profile->workplace }}@if($profile->workplace_address)<br>{{ $profile->workplace_address }}@endif<br>{{ $profile->city }}</p>@endif
        @if($activeOptions->contains('type', 'in_person'))
            <h2>Clinic contact</h2>
            @if($profile->phone)<p><a href="tel:{{ $profile->phone }}">{{ $profile->phone }}</a></p><p><a class="btn btn-outline btn-small" rel="noopener noreferrer" target="_blank" href="https://wa.me/{{ preg_replace('/\\D+/', '', $profile->phone) }}">WhatsApp clinic</a></p>@endif
            <p><a href="mailto:{{ $profile->user->email }}">{{ $profile->user->email }}</a></p>
        @endif
        <h2>Regular availability</h2>
        <ul>@forelse($profile->availabilityPeriods as $period)<li>{{ ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'][$period->day_of_week] }}: {{ \Carbon\CarbonImmutable::parse($period->starts_at)->format('g:i A') }} – {{ \Carbon\CarbonImmutable::parse($period->ends_at)->format('g:i A') }}</li>@empty<li>Availability not configured</li>@endforelse</ul>
        <p class="hint">Specific dates may be unavailable. Select a consultation option and a current available time to book.</p>
    </aside>
</div>
