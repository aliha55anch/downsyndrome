@extends('layouts.auth')

@section('title', 'Forgot Password')

@section('content')
<div class="form-box form-narrow">
    <h2 class="center">Forgot your password?</h2>
    <p class="muted">Enter your email address and, if an account matches, we will send a password reset link.</p>

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
        <button class="btn btn-blue" type="submit" style="width:100%">Email password reset link</button>
    </form>

    <p class="center" style="margin-top:16px"><a href="{{ route('login') }}">Return to login</a></p>
</div>
@endsection
