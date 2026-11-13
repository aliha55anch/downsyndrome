@extends('layouts.auth')

@section('title', 'Reset password')

@section('role_label', 'Account security')
@section('form_heading', 'Set a new password')
@section('form_subheading', 'Choose a strong password for your Little Steps account. You will use it the next time you sign in.')

@section('switch_text')
    Back to <a href="{{ route('login') }}">sign in</a>
@endsection

@section('content')
<form method="POST" action="{{ route('password.store') }}" class="auth-form">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">

    <div class="auth-field">
        <label for="email">Email address</label>
        <input type="email" id="email" name="email" value="{{ old('email', $email) }}" autocomplete="email" required autofocus>
        @error('email')<p class="field-error">{{ $message }}</p>@enderror
    </div>

    <div class="auth-field">
        <label for="password">New password</label>
        <div class="password-field">
            <input type="password" id="password" name="password" placeholder="Create a new password" minlength="8" autocomplete="new-password" required>
            <button type="button" class="password-toggle" data-toggle-password="password" aria-label="Show password" aria-pressed="false">
                <svg class="eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.5 12s3.5-6.5 9.5-6.5S21.5 12 21.5 12s-3.5 6.5-9.5 6.5S2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="2.7"/></svg>
                <svg class="eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 3 18 18"/><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/><path d="M9.9 5.2A10.7 10.7 0 0 1 12 5c6.5 0 9.5 7 9.5 7a17.8 17.8 0 0 1-3.1 4.2"/><path d="M6.1 6.1A17.5 17.5 0 0 0 2.5 12s3.5 7 9.5 7c1.6 0 3-.4 4.3-1"/></svg>
            </button>
        </div>
        <p class="hint">Use at least 8 characters.</p>
        @error('password')<p class="field-error">{{ $message }}</p>@enderror
    </div>

    <div class="auth-field">
        <label for="password_confirmation">Confirm new password</label>
        <div class="password-field">
            <input type="password" id="password_confirmation" name="password_confirmation" placeholder="Write the password again" minlength="8" autocomplete="new-password" required>
            <button type="button" class="password-toggle" data-toggle-password="password_confirmation" aria-label="Show confirmation password" aria-pressed="false">
                <svg class="eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.5 12s3.5-6.5 9.5-6.5S21.5 12 21.5 12s-3.5 6.5-9.5 6.5S2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="2.7"/></svg>
                <svg class="eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 3 18 18"/><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/><path d="M9.9 5.2A10.7 10.7 0 0 1 12 5c6.5 0 9.5 7 9.5 7a17.8 17.8 0 0 1-3.1 4.2"/><path d="M6.1 6.1A17.5 17.5 0 0 0 2.5 12s3.5 7 9.5 7c1.6 0 3-.4 4.3-1"/></svg>
            </button>
        </div>
    </div>

    <button class="btn btn-blue auth-submit" type="submit">Reset password</button>
</form>
@endsection
