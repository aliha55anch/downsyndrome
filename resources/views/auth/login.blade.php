@extends('layouts.auth')

@section('title', 'Login')

@section('role_label', 'Family & caregiver')
@section('form_heading', 'Welcome back')
@section('form_subheading', 'Sign in to save activities, follow your child’s progress, and manage appointments.')

@section('switch_text')
    New to Little Steps? <a href="{{ route('register') }}">Create a free account</a>
@endsection

@section('content')
<form method="POST" action="{{ route('login') }}" class="auth-form">
    @csrf
    <input type="hidden" name="portal" value="family">

    <div class="auth-field">
        <label for="email">Email address</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" autocomplete="email" required autofocus>
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

    <button class="btn btn-blue auth-submit" type="submit">Sign in to Little Steps</button>
</form>

<div class="auth-divider"><span>Other sign-in options</span></div>

<div class="auth-role-links">
    <a class="auth-role-link" href="{{ route('doctor.login') }}">
        <span class="auth-role-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3v5a4 4 0 0 0 4 4h0a4 4 0 0 1 4 4v5"/><path d="M18 3v5a4 4 0 0 1-4 4h0"/><circle cx="6" cy="3" r="1.5"/><circle cx="18" cy="3" r="1.5"/><circle cx="12" cy="21" r="1.5"/></svg>
        </span>
        <span><strong>Doctor sign in</strong><small>For verified healthcare professionals</small></span>
        <span class="auth-role-arrow" aria-hidden="true">→</span>
    </a>

    <a class="auth-role-link" href="{{ route('admin.login') }}">
        <span class="auth-role-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 4.5 6v5.5c0 4.4 3.1 7.8 7.5 9.5 4.4-1.7 7.5-5.1 7.5-9.5V6L12 3Z"/><path d="m9 12 2 2 4-4"/></svg>
        </span>
        <span><strong>Administrator sign in</strong><small>For Little Steps platform administrators</small></span>
        <span class="auth-role-arrow" aria-hidden="true">→</span>
    </a>
</div>

<p class="auth-fineprint">Are you a medical professional without an account? <a href="{{ route('doctor.register') }}">Apply for verification</a>.</p>
@endsection
