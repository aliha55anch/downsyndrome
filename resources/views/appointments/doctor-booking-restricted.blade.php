@extends('layouts.auth')
@section('title', 'Patient booking only')
@section('content')
<main class="container section">
    <section class="doctor-panel">
        <h1>Appointment booking is for patients</h1>
        <p>You are currently logged in with a doctor account. Appointment booking is available only to patient accounts.</p>
        <p>You can still browse this public profile: <strong>{{ $profile->professional_title }} {{ $profile->user->name }}</strong>.</p>
        <a class="btn btn-blue" href="{{ route('doctor.dashboard') }}">Back to Doctor Dashboard</a>
        <a class="btn btn-outline" href="{{ route('doctors.index') }}">Browse doctors</a>
    </section>
</main>
@endsection
