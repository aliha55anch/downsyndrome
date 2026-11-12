@extends('layouts.doctor')
@section('title','Appointment history')
@section('heading','Appointment history')
@section('doctor-content')
<section class="doctor-panel">
    <div class="panel-heading"><div><span class="eyebrow">Completed and closed care</span><h2>Past appointments</h2></div><a class="btn btn-outline btn-small" href="{{ route('doctor.appointments.index') }}">Back to appointments</a></div>
    @forelse($appointments as $appointment)
        <article class="doctor-panel appointment-list-card"><div><strong>{{ $appointment->localStartsAt()->format('D, M j, Y · g:i A') }} ({{ $appointment->timezone }})</strong><p>{{ $appointment->patient->name }} · {{ ucfirst(str_replace('_',' ',$appointment->consultation_type)) }} · {{ ucfirst(str_replace('_',' ',$appointment->status)) }} · Payment {{ $appointment->payments->first()?->status ?? 'not required' }}</p></div><a class="btn btn-outline btn-small" href="{{ route('doctor.appointments.show',$appointment) }}">View record</a></article>
    @empty<p class="muted">Past appointment records will appear here.</p>@endforelse
    {{ $appointments->links() }}
</section>
@endsection
