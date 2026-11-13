@extends('layouts.public')

@section('title', 'Professional Consultancy')
@section('body_class', 'consultancy-page')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/consultancy.css') }}">
@endpush

@section('content')
@php
    $hasFilters = filled($name) || filled($specialty) || filled($city);
    $specialtyName = $specialty ? $specialties->firstWhere('id', (int) $specialty)?->name : null;
@endphp

<section class="pc-hero">
    <div class="container pc-hero__inner">
        <div class="pc-hero__copy">
            <span class="pc-eyebrow">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s-7-4.35-7-10a4 4 0 0 1 7-2.65A4 4 0 0 1 19 11c0 5.65-7 10-7 10Z"/></svg>
                Little Steps professional consultancy
            </span>
            <h1>Trusted specialists for <span>every step</span> of your child&rsquo;s journey.</h1>
            <p>Connect with verified paediatricians, therapists and specialists who understand Down syndrome. Compare consultation options and book the support that fits your family.</p>
            <div class="pc-hero__actions">
                <a class="pc-cta pc-cta--primary" href="#find-a-professional">
                    Find a professional
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </a>
                @guest
                    <a class="pc-cta pc-cta--ghost" href="{{ route('register') }}">Create a family account</a>
                @else
                    @if (auth()->user()->hasRole(\App\Models\User::ROLE_PARENT))
                        <a class="pc-cta pc-cta--ghost" href="{{ route('dashboard') }}">Open my dashboard</a>
                    @endif
                @endguest
            </div>
            <dl class="pc-hero__stats">
                <div class="pc-hero__stat">
                    <dt>Profiles verified</dt>
                    <dd>100%</dd>
                </div>
                <div class="pc-hero__stat">
                    <dt>Specialties</dt>
                    <dd>{{ $specialties->count() }}</dd>
                </div>
                <div class="pc-hero__stat">
                    <dt>Cities covered</dt>
                    <dd>{{ $cities->count() }}</dd>
                </div>
            </dl>
        </div>

        <div class="pc-hero__art" aria-hidden="true">
            <figure class="pc-hero__photo">
                <img src="{{ asset('assets/images/doctor.jpeg') }}" alt="" loading="eager">
                <figcaption>
                    <span>Family-centred care</span>
                    Support that meets you where you are.
                </figcaption>
            </figure>
            <div class="pc-float pc-float--verified">
                <span class="pc-float__icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 4.5 6v5.4c0 4.7 3.2 8.1 7.5 9.6 4.3-1.5 7.5-4.9 7.5-9.6V6L12 3Z"/><path d="m8.7 12.2 2.1 2.1 4.5-4.5"/></svg>
                </span>
                <span><strong>Every profile reviewed</strong><small>Credentials checked before publishing</small></span>
            </div>
            <div class="pc-float pc-float--care">
                <span class="pc-float__icon is-amber">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 7V5a4 4 0 0 1 8 0v2"/><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M12 11v5M9.5 13.5h5"/></svg>
                </span>
                <span><strong>Online or in person</strong><small>Choose what works for your family</small></span>
            </div>
        </div>
    </div>
</section>

<section class="pc-directory" id="find-a-professional">
    <div class="container">
        <div class="pc-toolbar">
            @include('partials.care-switcher', ['activeCarePage' => 'consultancy'])
            <span class="pc-toolbar__note">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z"/><path d="M9.2 9a3 3 0 1 1 4.3 2.7c-.9.4-1.5 1.1-1.5 2.1v.2"/><path d="M12 17.2h.01"/></svg>
                Appointments and professional consultancy, in one place
            </span>
        </div>

        <form class="pc-filters" method="GET" action="{{ route('doctors.index') }}" role="search">
            <div class="pc-filters__head">
                <span class="pc-filters__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.2-3.2"/></svg>
                </span>
                <div>
                    <h2>Find the right professional</h2>
                    <p>Search by name, specialty or city to narrow the directory.</p>
                </div>
            </div>

            <div class="pc-filters__grid">
                <div class="pc-field">
                    <label for="name">Doctor name</label>
                    <div class="pc-input-wrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path d="M5 20a7 7 0 0 1 14 0"/></svg>
                        <input id="name" name="name" value="{{ $name }}" placeholder="Search by name" autocomplete="off">
                    </div>
                </div>
                <div class="pc-field">
                    <label for="specialty">Specialty</label>
                    <div class="pc-input-wrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h10"/></svg>
                        <select id="specialty" name="specialty">
                            <option value="">All specialties</option>
                            @foreach ($specialties as $item)
                                <option value="{{ $item->id }}" @selected((string) $specialty === (string) $item->id)>{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="pc-field">
                    <label for="city">City</label>
                    <div class="pc-input-wrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.7"/></svg>
                        <select id="city" name="city">
                            <option value="">All cities</option>
                            @foreach ($cities as $item)
                                <option value="{{ $item }}" @selected($city === $item)>{{ $item }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="pc-filters__actions">
                    <button class="pc-cta pc-cta--primary" type="submit">
                        Search
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.2-3.2"/></svg>
                    </button>
                    @if ($hasFilters)
                        <a class="pc-clear" href="{{ route('doctors.index') }}">Clear filters</a>
                    @endif
                </div>
            </div>
        </form>

        <div class="pc-results-head">
            <div>
                <span class="pc-kicker">Verified directory</span>
                <h2>{{ $hasFilters ? 'Professionals that match your search' : 'Meet the people behind the care' }}</h2>
                <p>{{ $hasFilters ? 'Review the profiles below, or adjust your filters to explore more of the directory.' : 'Every professional is reviewed before appearing here. Browse their experience, consultation options and availability.' }}</p>
            </div>
            <span class="pc-result-pill">
                <strong>{{ $doctors->total() }}</strong>
                {{ \Illuminate\Support\Str::plural('professional', $doctors->total()) }}
            </span>
        </div>

        @if ($hasFilters)
            <div class="pc-active-filters" aria-label="Active filters">
                <span class="pc-active-filters__label">Filtering by</span>
                @if (filled($name))
                    <span class="pc-chip">Name: {{ $name }}</span>
                @endif
                @if (filled($specialtyName))
                    <span class="pc-chip">Specialty: {{ $specialtyName }}</span>
                @endif
                @if (filled($city))
                    <span class="pc-chip">City: {{ $city }}</span>
                @endif
                <a class="pc-active-filters__clear" href="{{ route('doctors.index') }}">Clear all</a>
            </div>
        @endif

        <div class="pc-grid">
            @forelse ($doctors as $doctor)
                @php($activeOptions = $doctor->consultationOptions->where('is_active', true))
                <article class="pc-doctor-card">
                    <div class="pc-doctor-card__header">
                        <span class="pc-verified">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3 4.5 6v5.4c0 4.7 3.2 8.1 7.5 9.6 4.3-1.5 7.5-4.9 7.5-9.6V6L12 3Z"/><path d="m8.7 12.2 2.1 2.1 4.5-4.5"/></svg>
                            Verified
                        </span>

                        @if ($doctor->profile_photo_path)
                            <img class="pc-avatar" src="{{ route('doctors.photo', $doctor) }}" alt="Profile photo of {{ $doctor->user->name }}" loading="lazy">
                        @else
                            <span class="pc-avatar pc-avatar--placeholder" aria-hidden="true">{{ strtoupper(substr($doctor->user->first_name ?: $doctor->user->name, 0, 1)) }}</span>
                        @endif
                    </div>

                    <div class="pc-doctor-card__body">
                        <h3><a href="{{ route('doctors.show', $doctor) }}">{{ $doctor->professional_title }} {{ $doctor->user->name }}</a></h3>
                        <p class="pc-doctor-card__specialty">{{ $doctor->specialties->pluck('name')->join(', ') ?: $doctor->specialty }}</p>

                        <ul class="pc-doctor-card__facts">
                            <li>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.7"/></svg>
                                {{ $doctor->city ?: 'City not listed' }}
                            </li>
                            <li>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a4 4 0 0 1 8 0v2"/></svg>
                                {{ $doctor->years_experience }} {{ \Illuminate\Support\Str::plural('year', $doctor->years_experience) }} experience
                            </li>
                            @if ($doctor->qualification)
                                <li>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 8.5 9-4.5 9 4.5-9 4.5-9-4.5Z"/><path d="M7 10.5V16c2.6 1.7 7.4 1.7 10 0v-5.5"/><path d="M21 8.5V14"/></svg>
                                    {{ $doctor->qualification }}
                                </li>
                            @endif
                        </ul>

                        <div class="pc-options">
                            <span class="pc-options__label">Consultation options</span>
                            @if ($activeOptions->isNotEmpty())
                                <ul class="pc-options__list">
                                    @foreach ($activeOptions->take(2) as $option)
                                        <li class="pc-option">
                                            <span class="pc-option__type">
                                                @if ($option->type === 'online')
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="13" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
                                                    Online
                                                @else
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.7"/></svg>
                                                    In person
                                                @endif
                                            </span>
                                            <span class="pc-option__meta">
                                                @if ($option->duration_minutes){{ $option->duration_minutes }} min &middot; @endif
                                                @if ($option->fee !== null)
                                                    {{ config('app.currency', 'PKR') }} {{ number_format((float) $option->fee, 2) }}
                                                @else
                                                    Fee on request
                                                @endif
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                                @if ($activeOptions->count() > 2)
                                    <span class="pc-options__more">+{{ $activeOptions->count() - 2 }} more option{{ $activeOptions->count() - 2 === 1 ? '' : 's' }}</span>
                                @endif
                            @else
                                <p class="pc-options__empty">Consultation options coming soon</p>
                            @endif
                        </div>
                    </div>

                    <div class="pc-doctor-card__footer">
                        <a class="pc-btn pc-btn--outline" href="{{ route('doctors.show', $doctor) }}">View profile</a>
                        @if (auth()->guest() || auth()->user()->hasRole(\App\Models\User::ROLE_PARENT))
                            @if ($activeOptions->isNotEmpty())
                                <a class="pc-btn pc-btn--primary" href="{{ route('appointments.create', $doctor) }}">Book appointment</a>
                            @endif
                        @else
                            <span class="pc-doctor-card__hint">Patient accounts can book appointments.</span>
                        @endif
                    </div>
                </article>
            @empty
                <div class="pc-empty">
                    <span class="pc-empty__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.2-3.2"/></svg>
                    </span>
                    <h3>No published profiles found</h3>
                    <p>Try another specialty or city. New profiles appear after administrator publication.</p>
                    <a class="pc-cta pc-cta--teal" href="{{ route('doctors.index') }}">Browse all professionals</a>
                </div>
            @endforelse
        </div>

        {{ $doctors->links('pagination.doctors') }}

        <div class="pc-note">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z"/><path d="M12 8v5M12 16.5h.01"/></svg>
            <p><strong>A note on privacy.</strong> Consultation details are shared only with the professional you choose. The information in this directory is general guidance and does not replace an individual medical assessment.</p>
        </div>
    </div>
</section>
@endsection
