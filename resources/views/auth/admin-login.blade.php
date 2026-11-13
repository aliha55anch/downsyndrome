@extends('layouts.auth')

@section('title', 'Administrator login')

@section('story_eyebrow', 'Administration')
@section('story_heading', 'Clear oversight. Secure access.')
@section('story_text', 'Sign in to manage the Little Steps platform, review professional applications, and keep the community running smoothly.')
@section('story_points')
    <li>
        <span class="auth-point-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
        <span><strong>Protected workspace</strong><small>Administrator-only access with clear audit trails.</small></span>
    </li>
    <li>
        <span class="auth-point-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
        <span><strong>Professional review</strong><small>Verify doctors and manage platform quality.</small></span>
    </li>
    <li>
        <span class="auth-point-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
        <span><strong>Community care</strong><small>Support families with a safe, reliable service.</small></span>
    </li>
@endsection

@section('role_label', 'Administrator')
@section('form_heading', 'Administrator sign in')
@section('form_subheading', 'Use your Little Steps administrator account to manage operations, people, and platform content.')

@section('switch_text')
    Not an administrator? <a href="{{ route('login') }}">Return to family login</a>
@endsection

@section('content')
<form method="POST" action="{{ route('login') }}" class="auth-form">
    @csrf
    <input type="hidden" name="portal" value="admin">

    <div class="auth-field">
        <label for="email">Administrator email</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="admin@example.com" autocomplete="email" required autofocus>
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

    <button class="btn btn-blue auth-submit" type="submit">Sign in to administration</button>
</form>

<div class="auth-note">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3 4.5 6v5.5c0 4.4 3.1 7.8 7.5 9.5 4.4-1.7 7.5-5.1 7.5-9.5V6L12 3Z"/><path d="m9 12 2 2 4-4"/></svg>
    <span><strong>Authorised administrators only.</strong> This portal is monitored for security. If you need administrator access, contact the platform owner.</span>
</div>

<div class="auth-divider"><span>Other portals</span></div>

<div class="auth-role-links">
    <a class="auth-role-link" href="{{ route('login') }}">
        <span class="auth-role-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></span>
        <span><strong>Family sign in</strong><small>Access a parent or caregiver account</small></span>
        <span class="auth-role-arrow" aria-hidden="true">→</span>
    </a>
    <a class="auth-role-link" href="{{ route('doctor.login') }}">
        <span class="auth-role-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3v5a4 4 0 0 0 4 4h0a4 4 0 0 1 4 4v5"/><path d="M18 3v5a4 4 0 0 1-4 4h0"/><circle cx="6" cy="3" r="1.5"/><circle cx="18" cy="3" r="1.5"/><circle cx="12" cy="21" r="1.5"/></svg></span>
        <span><strong>Doctor sign in</strong><small>Access a professional account</small></span>
        <span class="auth-role-arrow" aria-hidden="true">→</span>
    </a>
</div>
@endsection
