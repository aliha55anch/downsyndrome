@extends('layouts.public')

@section('title', 'Upcoming appointments')
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
            <p>Keep every consultation, payment update, and meeting detail together in one calm, private space.</p>
            <div class="care-hero__actions">
                <a class="care-btn care-btn--primary" href="{{ route('appointments.login') }}">Log in to view appointments</a>
                <a class="care-btn care-btn--ghost" href="{{ route('doctors.index') }}">Find a professional</a>
            </div>
        </div>

        <aside class="care-hero__art" aria-label="Your private appointment space">
            <div class="care-panel care-panel--preview">
                <span class="care-panel__badge">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                    Private
                </span>
                <div class="care-preview-row">
                    <span class="care-preview-avatar">A</span>
                    <span><strong>Your next consultation</strong><small>Doctor, date, and payment details</small></span>
                </div>
                <div class="care-preview-line"></div>
                <div class="care-preview-row">
                    <span class="care-preview-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a4 4 0 0 1 8 0v2"/></svg>
                    </span>
                    <span><strong>Online or in person</strong><small>Details in one place</small></span>
                </div>
                <div class="care-preview-row">
                    <span class="care-preview-icon is-amber" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-7-4.35-7-10a4 4 0 0 1 7-2.65A4 4 0 0 1 19 11c0 5.65-7 10-7 10Z"/></svg>
                    </span>
                    <span><strong>Support that fits your family</strong><small>Review and manage with confidence</small></span>
                </div>
            </div>
        </aside>
    </div>
</section>

<section class="care-body care-lift">
    <div class="container">
        <div class="appt-guest-card">
            <span class="appt-guest-card__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/><circle cx="12" cy="15" r="1"/></svg>
            </span>
            <span class="care-kicker">A private space</span>
            <h2>Your appointment list is private.</h2>
            <p>Log in to see bookings, payment updates, and appointment details.</p>
            <div class="appt-guest-card__actions">
                <a class="care-btn care-btn--primary" href="{{ route('appointments.login') }}">Log in to view appointments</a>
                <a class="care-btn care-btn--outline" href="{{ route('doctors.index') }}">Find a professional</a>
            </div>
            <p class="appt-guest-card__note">New to Little Steps? <a href="{{ route('register') }}">Create a parent account</a> to book and manage appointments.</p>
        </div>

        <div class="care-trust-grid">
            <article class="care-trust-card">
                <span class="care-trust-card__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 4.5 6v5.4c0 4.7 3.2 8.1 7.5 9.6 4.3-1.5 7.5-4.9 7.5-9.6V6L12 3Z"/><path d="m8.7 12.2 2.1 2.1 4.5-4.5"/></svg>
                </span>
                <h3>Private by design</h3>
                <p>Only you can see your bookings, payment progress, and consultation details.</p>
            </article>
            <article class="care-trust-card">
                <span class="care-trust-card__icon is-amber" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                </span>
                <h3>Everything in one place</h3>
                <p>Check upcoming times, meeting links, and any appointment that needs attention.</p>
            </article>
            <article class="care-trust-card">
                <span class="care-trust-card__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.7"/></svg>
                </span>
                <h3>Care close to home</h3>
                <p>Find verified professionals for online consultations or in-person visits.</p>
            </article>
        </div>
    </div>
</section>
@endsection
