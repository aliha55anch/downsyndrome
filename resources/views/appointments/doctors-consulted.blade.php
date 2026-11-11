@extends('layouts.dashboard')
@section('title','Doctors consulted')
@section('heading','Doctors consulted')
@section('dashboard-content')
<section class="doctor-panel"><h2>Your consultation history by doctor</h2>
@forelse($appointments as $appointment)<article class="doctor-panel"><p><strong>{{ $appointment->doctorProfile->professional_title }} {{ $appointment->doctorProfile->user->name }}</strong></p><p>Consultation date: {{ $appointment->localStartsAt()->format('F j, Y') }} ({{ $appointment->timezone }})</p><p>{{ ucfirst(str_replace('_',' ',$appointment->consultation_type)) }} · {{ ucfirst(str_replace('_',' ',$appointment->status)) }}</p><a href="{{ route('appointments.show',$appointment) }}">View appointment</a></article>@empty<p>Completed consultations with doctors will appear here.</p>@endforelse{{ $appointments->links() }}</section>
@endsection
