@extends('layouts.auth')

@section('title', 'Login')

@section('content')
<div class="form-box form-narrow">
    <h2 class="center">Welcome back 👋</h2>

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus>

        <label for="password">Password</label>
        <div class="password-field"><input type="password" id="password" name="password" autocomplete="current-password" required><button type="button" class="password-toggle" data-toggle-password="password" aria-label="Show password">◌</button></div>

        <label style="font-weight:400; margin-top:12px;">
            <input type="checkbox" name="remember" value="1" style="width:auto; min-height:0; margin-right:6px;">
            Remember me
        </label>

        <button class="btn btn-blue" type="submit" style="width:100%">Login</button>
    </form>

    <p class="center" style="margin-top:16px"><a href="{{ route('password.request') }}">Forgot your password?</a></p>
    <p class="center">Are you a doctor? <a href="{{ route('doctor.register') }}">Apply for professional verification</a></p>
    <p class="center">New here? <a href="{{ route('register') }}">Create a free account</a></p>
</div>
@endsection
