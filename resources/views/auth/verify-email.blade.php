@extends('layouts.auth')

@section('title', 'Verify email')

@section('role_label', 'One quick step')
@section('form_heading', 'Verify your email address')
@section('form_subheading', 'Confirm your email so we can keep your Little Steps account secure and send important updates.')

@section('content')
<div class="auth-verify-icon" aria-hidden="true">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16v16H4z"/><path d="m4 7 8 6 8-6"/><path d="m15.5 16.5 1.5 1.5 3-3"/></svg>
</div>

<p class="auth-verify-text">We sent a verification link to <strong>{{ auth()->user()->email }}</strong>. Open the link in that email to activate your account.</p>

<form method="POST" action="{{ route('verification.send') }}" class="auth-form">
    @csrf
    <button class="btn btn-blue auth-submit" type="submit">Send verification email again</button>
</form>

<div class="auth-divider"><span>Wrong account?</span></div>

<form method="POST" action="{{ route('logout') }}">
    @csrf
    <button class="btn btn-outline auth-submit" type="submit" style="margin-top:0;">Log out and use another account</button>
</form>

<div class="auth-note">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5"/><path d="M12 8h.01"/></svg>
    <span>Didn’t receive the email? Check your spam folder, then request a new link above. Links expire for your security.</span>
</div>
@endsection
