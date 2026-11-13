@extends('layouts.auth')

@section('title', 'Doctor login')

@section('story_eyebrow', 'Professional network')
@section('story_heading', 'Care that families can trust.')
@section('story_text', 'Sign in to manage your verification, availability, appointments, and the professional profile families see.')
@section('story_points')
    <li>
        <span class="auth-point-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
        <span><strong>Verified identity</strong><small>Your credentials are reviewed by our team.</small></span>
    </li>
    <li>
        <span class="auth-point-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
        <span><strong>Simple scheduling</strong><small>Keep appointments and availability in one place.</small></span>
    </li>
    <li>
        <span class="auth-point-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
        <span><strong>Family-focused care</strong><small>Help parents take confident next steps.</small></span>
    </li>
@endsection

@section('role_label', 'Healthcare professional')
@section('form_heading', 'Professional sign in')
@section('form_subheading', 'Access your doctor workspace to manage verification, appointments, availability, and your public profile.')

@section('switch_text')
    New doctor? <a href="{{ route('doctor.register') }}">Apply for verification</a> &nbsp;·&nbsp; <a href="{{ route('login') }}">Family login</a>
@endsection

@section('content')
<form method="POST" action="{{ route('login') }}" class="auth-form">
    @csrf
    <input type="hidden" name="portal" value="doctor">

    <div class="auth-field">
        <label for="email">Professional email</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="doctor@example.com" autocomplete="email" required autofocus>
        @error('email')<p class="field-error">{{ $message }}</p>@enderror
    </div>

    <div class="auth-field">
        <div class="auth-label-row">
            <label for="password">Password</label>
            <a href="{{ route('password.request') }}">Forgot password?</a>
        </div>
        <div class="password-field">
            <input type="password" id="password" name="password" placeholder="Enter your password" autocomplete="current-password" required>
            <button type="button" class="password-toggle" data-toggle-password="password" aria-label="Show password" aria-pressed="false">
                <svg class="eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.5 12s3.5-6.5 9.5-6.5S21.5 12 21.5 12s-3.5 6.5-9.5 6.5S2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="2.7"/></svg>
                <svg class="eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 3 18 18"/><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/><path d="M9.9 5.2A10.7 10.7 0 0 1 12 5c6.5 0 9.5 7 9.5 7a17.8 17.8 0 0 1-3.1 4.2"/><path d="M6.1 6.1A17.5 17.5 0 0 0 2.5 12s3.5 7 9.5 7c1.6 0 3-.4 4.3-1"/></svg>
            </button>
        </div>
        @error('password')<p class="field-error">{{ $message }}</p>@enderror
    </div>

    <label class="auth-check">
        <input type="checkbox" name="remember" value="1">
        <span>Keep me signed in on this device</span>
    </label>

    <button class="btn btn-blue auth-submit" type="submit">Sign in to doctor workspace</button>
</form>

<div class="auth-note">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3 4.5 6v5.5c0 4.4 3.1 7.8 7.5 9.5 4.4-1.7 7.5-5.1 7.5-9.5V6L12 3Z"/><path d="m9 12 2 2 4-4"/></svg>
    <span><strong>Verification protects families.</strong> New professional accounts can sign in after applying, but public booking features are enabled only after administrator approval.</span>
</div>

<div class="auth-divider"><span>Other portals</span></div>

<div class="auth-role-links">
    <a class="auth-role-link" href="{{ route('login') }}">
        <span class="auth-role-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></span>
        <span><strong>Family sign in</strong><small>Access a parent or caregiver account</small></span>
        <span class="auth-role-arrow" aria-hidden="true">→</span>
    </a>
    <a class="auth-role-link" href="{{ route('admin.login') }}">
        <span class="auth-role-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 4.5 6v5.5c0 4.4 3.1 7.8 7.5 9.5 4.4-1.7 7.5-5.1 7.5-9.5V6L12 3Z"/><path d="m9 12 2 2 4-4"/></svg></span>
        <span><strong>Administrator sign in</strong><small>Access the platform administration</small></span>
        <span class="auth-role-arrow" aria-hidden="true">→</span>
    </a>
</div>
@endsection
