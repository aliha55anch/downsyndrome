@extends('layouts.auth')

@section('title', 'Create account')

@section('role_label', 'Family & caregiver')
@section('form_heading', 'Create your free account')
@section('form_subheading', 'Save activities, follow your child’s progress, and book trusted professionals from one calm, private space.')

@section('switch_text')
    Already have an account? <a href="{{ route('login') }}">Sign in</a>
@endsection

@section('content')
<form method="POST" action="{{ route('register') }}" class="auth-form">
    @csrf

    <div class="form-row">
        <div class="auth-field">
            <label for="first_name">First name</label>
            <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}" placeholder="First name" autocomplete="given-name" required autofocus>
            @error('first_name')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div class="auth-field">
            <label for="last_name">Last name</label>
            <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}" placeholder="Last name" autocomplete="family-name" required>
            @error('last_name')<p class="field-error">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="auth-field">
        <label for="email">Email address</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" autocomplete="email" required>
        @error('email')<p class="field-error">{{ $message }}</p>@enderror
    </div>

    <div class="auth-field">
        <label for="password">Password</label>
        <div class="password-field">
            <input type="password" id="password" name="password" placeholder="Create a password" minlength="8" autocomplete="new-password" required>
            <button type="button" class="password-toggle" data-toggle-password="password" aria-label="Show password" aria-pressed="false">
                <svg class="eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.5 12s3.5-6.5 9.5-6.5S21.5 12 21.5 12s-3.5 6.5-9.5 6.5S2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="2.7"/></svg>
                <svg class="eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 3 18 18"/><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/><path d="M9.9 5.2A10.7 10.7 0 0 1 12 5c6.5 0 9.5 7 9.5 7a17.8 17.8 0 0 1-3.1 4.2"/><path d="M6.1 6.1A17.5 17.5 0 0 0 2.5 12s3.5 7 9.5 7c1.6 0 3-.4 4.3-1"/></svg>
            </button>
        </div>
        <p class="hint">Use at least 8 characters.</p>
        @error('password')<p class="field-error">{{ $message }}</p>@enderror
    </div>

    <div class="auth-field">
        <label for="password_confirmation">Confirm password</label>
        <div class="password-field">
            <input type="password" id="password_confirmation" name="password_confirmation" placeholder="Write the password again" minlength="8" autocomplete="new-password" required>
            <button type="button" class="password-toggle" data-toggle-password="password_confirmation" aria-label="Show confirmation password" aria-pressed="false">
                <svg class="eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.5 12s3.5-6.5 9.5-6.5S21.5 12 21.5 12s-3.5 6.5-9.5 6.5S2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="2.7"/></svg>
                <svg class="eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 3 18 18"/><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/><path d="M9.9 5.2A10.7 10.7 0 0 1 12 5c6.5 0 9.5 7 9.5 7a17.8 17.8 0 0 1-3.1 4.2"/><path d="M6.1 6.1A17.5 17.5 0 0 0 2.5 12s3.5 7 9.5 7c1.6 0 3-.4 4.3-1"/></svg>
            </button>
        </div>
    </div>

    <button class="btn btn-yellow auth-submit" type="submit">Create my free account</button>
</form>

<div class="auth-doctor-callout">
    <span class="auth-role-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3v5a4 4 0 0 0 4 4h0a4 4 0 0 1 4 4v5"/><path d="M18 3v5a4 4 0 0 1-4 4h0"/><circle cx="6" cy="3" r="1.5"/><circle cx="18" cy="3" r="1.5"/><circle cx="12" cy="21" r="1.5"/></svg>
    </span>
    <span><strong>Are you a healthcare professional?</strong><small>Apply for verification to join our trusted network.</small></span>
    <a href="{{ route('doctor.register') }}">Apply</a>
</div>

<div class="auth-note">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
    <span><strong>Your privacy matters.</strong> Your family details and progress are only visible to you and the professionals you choose to connect with.</span>
</div>
@endsection
