@extends('layouts.auth')

@section('title', 'Create Account')

@section('content')
<div class="form-box form-narrow">
    <h2 class="center">Create a free account</h2>
    <p class="center muted">For parents and caregivers</p>

    <form method="POST" action="{{ route('register') }}">
        @csrf
        <label for="first_name">First name</label>
        <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}" autocomplete="given-name" required autofocus>

        <label for="last_name">Last name</label>
        <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}" autocomplete="family-name" required>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="email" required>

        <label for="password">Password</label>
        <div class="password-field"><input type="password" id="password" name="password" minlength="8" autocomplete="new-password" required><button type="button" class="password-toggle" data-toggle-password="password" aria-label="Show password">◌</button></div>
        <p class="hint">Use at least 8 characters.</p>

        <label for="password_confirmation">Write the password again</label>
        <div class="password-field"><input type="password" id="password_confirmation" name="password_confirmation" minlength="8" autocomplete="new-password" required><button type="button" class="password-toggle" data-toggle-password="password_confirmation" aria-label="Show confirmation password">◌</button></div>

        <button class="btn btn-yellow" type="submit" style="width:100%">Create account</button>
    </form>

    <p class="center" style="margin-top:16px">Are you a medical professional? <a href="{{ route('doctor.register') }}">Apply for a doctor account</a></p>
    <p class="center" style="margin-top:16px">Already have an account? <a href="{{ route('login') }}">Login</a></p>
</div>
@endsection
