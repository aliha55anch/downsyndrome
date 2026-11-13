@extends('layouts.auth')

@section('title', 'Forgot password')

@section('role_label', 'Account help')
@section('form_heading', 'Forgot your password?')
@section('form_subheading', 'Enter the email address linked to your account and we will send you a secure password reset link.')

@section('switch_text')
    Remembered your password? <a href="{{ route('login') }}">Return to login</a>
@endsection

@section('content')
<form method="POST" action="{{ route('password.email') }}" class="auth-form">
    @csrf

    <div class="auth-field">
        <label for="email">Email address</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" autocomplete="email" required autofocus>
        @error('email')<p class="field-error">{{ $message }}</p>@enderror
    </div>

    <button class="btn btn-blue auth-submit" type="submit">Send password reset link</button>
</form>

<div class="auth-note">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 4h16v16H4z"/><path d="m4 7 8 6 8-6"/></svg>
    <span>For your security, we do not confirm whether an email address is registered. If an account matches, the reset link will arrive shortly.</span>
</div>
@endsection
