@extends('layouts.auth')

@section('title', 'Upcoming appointments')

@section('content')
<section class="form-box form-narrow">
    <h1>Upcoming appointments</h1>
    <p>Your appointment list is private. Log in to see bookings, payment updates, and appointment details.</p>
    <a class="btn btn-blue" href="{{ route('appointments.login') }}">Log in to view appointments</a>
    <p class="muted">New to Little Steps? <a href="{{ route('register') }}">Create a parent account</a> to book and manage appointments.</p>
</section>
@endsection
