@extends('layouts.public')

@section('title', 'Patient booking only')
@section('body_class', 'care-page booking-page')

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
                Appointment booking
            </span>
            <h1>Appointment booking is for <span>patients</span>.</h1>
            <p>You are currently signed in with a doctor account, so patient booking is not available from this page.</p>
            <div class="care-hero__actions">
                <a class="care-btn care-btn--primary" href="{{ route('doctor.dashboard') }}">Back to Doctor Dashboard</a>
                <a class="care-btn care-btn--ghost" href="{{ route('doctors.index') }}">Browse doctors</a>
            </div>
        </div>

        <aside class="care-hero__art" aria-label="Account notice">
            <div class="care-panel care-panel--preview">
                <span class="care-panel__badge">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/></svg>
                    Account notice
                </span>
                <div class="care-preview-row">
                    <span class="care-preview-icon is-amber" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </span>
                    <span><strong>Doctor workspace</strong><small>Manage your availability and appointments.</small></span>
                </div>
                <div class="care-preview-line"></div>
                <div class="care-preview-row">
                    <span class="care-preview-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-7-4.35-7-10a4 4 0 0 1 7-2.65A4 4 0 0 1 19 11c0 5.65-7 10-7 10Z"/></svg>
                    </span>
                    <span><strong>Public profile</strong><small>Families can still view your profile.</small></span>
                </div>
            </div>
        </aside>
    </div>
</section>

<main class="bk-main">
    <div class="container bk-layout">
        <div class="bk-content">
            <section class="bk-panel">
                <div class="bk-panel__head">
                    <div>
                        <span class="care-kicker">Patient booking only</span>
                        <h2>Appointment booking is for patients</h2>
                        <p>Appointment booking is available only to patient accounts. You can still browse this public profile: <strong>{{ $profile->professional_title }} {{ $profile->user->name }}</strong>.</p>
                    </div>
                </div>
                <div class="bk-restricted-actions">
                    <a class="care-btn care-btn--primary" href="{{ route('doctor.dashboard') }}">Back to Doctor Dashboard</a>
                    <a class="care-btn care-btn--outline" href="{{ route('doctors.index') }}">Browse doctors</a>
                </div>
            </section>
        </div>
    </div>
</main>
@endsection
