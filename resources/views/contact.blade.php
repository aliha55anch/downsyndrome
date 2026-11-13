@extends('layouts.public')

@section('title', 'Contact Us')
@section('body_class', 'care-page contact-page')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/care.css') }}">
@endpush

@section('content')
<section class="care-hero care-hero--contact">
    <div class="care-hero__grain" aria-hidden="true"></div>
    <div class="container care-hero__inner">
        <div class="care-hero__copy">
            <span class="care-eyebrow">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2Z"/><path d="M8 9h8M8 13h5"/></svg>
                Contact Us
            </span>
            <h1>We&rsquo;re here for your family&rsquo;s <span>next step</span>.</h1>
            <p>Whether you have a question about activities, need help finding the right professional, or simply want to share an idea &mdash; our team would love to hear from you.</p>
            <ul class="care-hero__points">
                <li>
                    <span class="care-hero__point-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                    </span>
                    <span><strong>A real person replies</strong><small>Every message is read by our family support team.</small></span>
                </li>
                <li>
                    <span class="care-hero__point-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 4.5 6v5.4c0 4.7 3.2 8.1 7.5 9.6 4.3-1.5 7.5-4.9 7.5-9.6V6L12 3Z"/><path d="m8.7 12.2 2.1 2.1 4.5-4.5"/></svg>
                    </span>
                    <span><strong>Private and respectful</strong><small>Your details are only used to respond to your message.</small></span>
                </li>
            </ul>
        </div>

        <aside class="care-hero__art" aria-label="Our response promise">
            <figure class="contact-hero-photo">
                <img src="{{ asset('assets/images/hero.jpeg') }}" alt="A parent and child sharing a calm moment together" loading="eager">
                <figcaption>
                    <span>Here to listen</span>
                    A thoughtful reply, not an automated loop.
                </figcaption>
            </figure>
            <div class="care-float care-float--top">
                <span class="care-float__icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2Z"/></svg>
                </span>
                <span><strong>Two working days</strong><small>Our usual reply time</small></span>
            </div>
            <div class="care-float care-float--bottom">
                <span class="care-float__icon is-amber">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-7-4.35-7-10a4 4 0 0 1 7-2.65A4 4 0 0 1 19 11c0 5.65-7 10-7 10Z"/></svg>
                </span>
                <span><strong>Family-first support</strong><small>Guidance with care and understanding</small></span>
            </div>
        </aside>
    </div>
</section>

<section class="care-body care-lift">
    <div class="container">
        <div class="contact-layout">
            <div class="contact-aside">
                <span class="care-kicker">How to reach us</span>
                <h2>A simple conversation can be the start of something helpful.</h2>
                <p>Tell us what you need and we will point you in the right direction. For appointment or account questions, include the email address linked to your Little Steps account.</p>

                <div class="contact-details">
                    <a class="contact-detail" href="mailto:hello@example.com">
                        <span class="contact-detail__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                        </span>
                        <span><small>Email us</small><strong>hello@example.com</strong></span>
                    </a>
                    <a class="contact-detail" href="tel:+923000000000">
                        <span class="contact-detail__icon is-amber" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.3 1.8.6 2.7a2 2 0 0 1-.4 2.1L8.1 9.7a16 16 0 0 0 6.2 6.2l1.2-1.2a2 2 0 0 1 2.1-.4c.9.3 1.8.5 2.7.6a2 2 0 0 1 1.7 2Z"/></svg>
                        </span>
                        <span><small>Call us</small><strong>+92 300 000 0000</strong></span>
                    </a>
                    <div class="contact-detail">
                        <span class="contact-detail__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.7"/></svg>
                        </span>
                        <span><small>Visit us</small><strong>Placeholder, Karachi, Pakistan</strong></span>
                    </div>
                    <div class="contact-detail">
                        <span class="contact-detail__icon is-amber" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                        </span>
                        <span><small>Office hours</small><strong>Monday&ndash;Friday, 9am&ndash;5pm</strong></span>
                    </div>
                </div>

                <div class="contact-help-card">
                    <span class="contact-help-card__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/><path d="m9 16 2 2 4-4"/></svg>
                    </span>
                    <div>
                        <strong>Looking to book a consultation?</strong>
                        <p>Browse verified specialists and choose a time that works for your family.</p>
                        <a class="care-btn care-btn--outline care-btn--small" href="{{ route('doctors.index') }}">Find a professional</a>
                    </div>
                </div>
            </div>

            <div class="contact-form-card">
                <div class="contact-form-card__head">
                    <span class="care-kicker">Send a message</span>
                    <h2>How can we help?</h2>
                    <p>Fill in the form and our team will get back to you. Fields marked with <span aria-hidden="true">*</span> are required.</p>
                </div>

                <form class="care-form" method="POST" action="{{ route('contact.store') }}">
                    @csrf
                    <div class="care-form__grid">
                        <div class="care-field">
                            <label for="name">Your name <span class="care-required" aria-hidden="true">*</span></label>
                            <input type="text" id="name" name="name" value="{{ old('name') }}" placeholder="e.g. Ayesha Khan" autocomplete="name" required @class(['is-invalid' => $errors->has('name')])>
                            @error('name')
                                <p class="care-field__error">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="care-field">
                            <label for="email">Your email <span class="care-required" aria-hidden="true">*</span></label>
                            <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" autocomplete="email" required @class(['is-invalid' => $errors->has('email')])>
                            @error('email')
                                <p class="care-field__error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="care-field">
                        <label for="subject">Subject <span class="care-required" aria-hidden="true">*</span></label>
                        <input type="text" id="subject" name="subject" value="{{ old('subject') }}" placeholder="What would you like to talk about?" required @class(['is-invalid' => $errors->has('subject')])>
                        @error('subject')
                            <p class="care-field__error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="care-field">
                        <label for="message">Your message <span class="care-required" aria-hidden="true">*</span></label>
                        <textarea id="message" name="message" maxlength="5000" placeholder="Share a little detail so we can help you properly." required @class(['is-invalid' => $errors->has('message')])>{{ old('message') }}</textarea>
                        <div class="care-field__meta">
                            <span>Please avoid sharing private medical details.</span>
                            <span>Up to 5,000 characters</span>
                        </div>
                        @error('message')
                            <p class="care-field__error">{{ $message }}</p>
                        @enderror
                    </div>

                    <button class="care-btn care-btn--primary care-btn--wide" type="submit">
                        Send message
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 2 11 13"/><path d="m22 2-7 20-4-9-9-4Z"/></svg>
                    </button>

                    <p class="contact-form-card__note">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3 4.5 6v5.4c0 4.7 3.2 8.1 7.5 9.6 4.3-1.5 7.5-4.9 7.5-9.6V6L12 3Z"/><path d="M12 11v4M12 8h.01"/></svg>
                        We usually reply within two working days. Your message is not shared publicly.
                    </p>
                </form>
            </div>
        </div>

        <div class="contact-emergency">
            <span class="contact-emergency__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9 2.4 17.1A2 2 0 0 0 4.1 20h15.8a2 2 0 0 0 1.7-2.9L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg>
            </span>
            <p><strong>Please do not send private medical details here.</strong> This form is not for emergencies. If your child needs urgent medical help, call a doctor or go to a hospital.</p>
        </div>
    </div>
</section>
@endsection
