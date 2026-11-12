@extends('layouts.auth')

@section('title', 'Reset Password')

@section('content')
<div class="form-box form-narrow">
    <h2 class="center">Set a new password</h2>

    <form method="POST" action="{{ route('password.store') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="{{ old('email', $email) }}" autocomplete="email" required autofocus>

        <label for="password">New password</label>
        <input type="password" id="password" name="password" minlength="8" autocomplete="new-password" required>
        <label style="font-weight:400; margin-top:8px;">
            <input type="checkbox" data-toggle-password="password" style="width:auto; min-height:0; margin-right:6px;">
            Show password
        </label>
        <p class="hint">Use at least 8 characters.</p>

        <label for="password_confirmation">Confirm new password</label>
        <input type="password" id="password_confirmation" name="password_confirmation" minlength="8" autocomplete="new-password" required>
        <label style="font-weight:400; margin-top:8px;">
            <input type="checkbox" data-toggle-password="password_confirmation" style="width:auto; min-height:0; margin-right:6px;">
            Show confirmation password
        </label>

        <button class="btn btn-blue" type="submit" style="width:100%">Reset password</button>
    </form>
</div>
@endsection
