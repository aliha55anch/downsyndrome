@extends('layouts.auth')

@section('title', 'Verify Email')

@section('content')
<div class="form-box form-narrow">
    <h2 class="center">Verify your email address</h2>
    <p>Thanks for creating a Little Steps account. Please open the verification link sent to <strong>{{ auth()->user()->email }}</strong> before continuing.</p>

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button class="btn btn-blue" type="submit" style="width:100%">Send verification email again</button>
    </form>

    <form method="POST" action="{{ route('logout') }}" style="text-align:center; margin-top:16px;">
        @csrf
        <button class="btn btn-outline" type="submit">Log out</button>
    </form>
</div>
@endsection
