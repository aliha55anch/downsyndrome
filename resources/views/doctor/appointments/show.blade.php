@extends('layouts.doctor')
@section('title','Appointment details')
@section('heading','Appointment details')
@section('doctor-content')
@php($payment=$appointment->payments->first())
<section class="doctor-panel">
    <h2>{{ $appointment->localStartsAt()->format('l, F j, Y · g:i A') }} ({{ $appointment->timezone }})</h2>
    <p>Patient: {{ $appointment->patient->name }}</p>
    <p>{{ ucfirst(str_replace('_',' ',$appointment->consultation_type)) }} · {{ $appointment->duration_minutes }} minutes</p>
    @if($appointment->consultation_type === 'in_person' && $appointment->doctorSlot?->clinic_location)<p><strong>Clinic:</strong> {{ $appointment->doctorSlot->clinic_location }}</p>@endif
    <p>Status: {{ ucfirst(str_replace('_',' ',$appointment->status)) }} · Payment: {{ $payment?->status ?? 'not required' }}</p>
    @if($appointment->cancellationRequest?->status === \App\Models\AppointmentCancellationRequest::PENDING)<div class="notice"><strong>Patient cancellation request</strong><p>{{ $appointment->cancellationRequest->reason }}</p><p>Estimated refund: {{ $payment?->currency ?? config('app.currency', 'PKR') }} {{ number_format($appointment->cancellationRequest->refund_amount_minor / 100, 2) }}</p><form method="POST" action="{{ route('doctor.appointments.cancellation-review',$appointment) }}">@csrf @method('PATCH')<label for="response">Response</label><textarea id="response" name="response" maxlength="1000"></textarea><button class="btn btn-blue" name="decision" value="approve">Approve</button><button class="btn btn-outline" name="decision" value="reject">Decline</button></form></div>@endif
    @if(in_array($appointment->status,[\App\Models\Appointment::CONFIRMED,\App\Models\Appointment::RESCHEDULED],true))
        @if($appointment->startsAtUtc()->greaterThan(now('UTC')))
            <h3>Reschedule appointment</h3><p>Choose a new time within your published availability. The server checks for conflicts before saving.</p>
            <form method="POST" action="{{ route('doctor.appointments.reschedule',$appointment) }}">@csrf
                <label for="doctor-reschedule-start">New local time ({{ $appointment->timezone }})</label><input id="doctor-reschedule-start" type="datetime-local" name="starts_at" required>
                <button class="btn btn-outline">Reschedule and notify patient</button>
            </form>
            <h3>Cancel appointment</h3><form method="POST" action="{{ route('doctor.appointments.cancel',$appointment) }}">@csrf
                <label for="doctor-cancel-reason">Reason for cancellation</label><textarea id="doctor-cancel-reason" name="reason" minlength="5" maxlength="1000" required></textarea>
                <button class="btn btn-outline" type="submit">Cancel and release time</button>
            </form>
        @endif
        <h3>Meeting link</h3>
        <p>Use a secure Google Meet or Zoom URL beginning with https://. The parent can see it only on their own confirmed appointment.</p>
        @if($appointment->consultation_type === 'online' && $appointment->meeting_link && (!$payment || $payment->status === \App\Models\PaymentTransaction::PAID))<p><a class="btn btn-blue" href="{{ $appointment->meeting_link }}" target="_blank" rel="noopener noreferrer">Join consultation</a></p>@endif
        <form method="POST" action="{{ route('doctor.appointments.meeting-link',$appointment) }}">
            @csrf @method('PUT')
            <label for="meeting_link">Google Meet or Zoom link</label>
            <input id="meeting_link" name="meeting_link" type="url" inputmode="url" maxlength="2048" placeholder="https://..." value="{{ old('meeting_link',$appointment->meeting_link) }}">
            <button class="btn btn-blue">Save meeting link</button>
            @if($appointment->meeting_link)<button class="btn btn-outline" name="meeting_link" value="" formnovalidate>Remove link</button>@endif
        </form>
        @if($appointment->startsAtUtc()->lessThanOrEqualTo(now('UTC')))
            <form method="POST" action="{{ route('doctor.appointments.update',$appointment) }}">
                @csrf @method('PATCH')
                <label for="status">After the appointment, mark the visit</label>
                <select id="status" name="status"><option value="completed">Completed</option><option value="no_show">No show</option></select>
                <button class="btn btn-blue">Save status</button>
            </form>
        @else
            <p>Status changes become available after the scheduled start time.</p>
        @endif
    @endif
    @if($appointment->status === \App\Models\Appointment::COMPLETED)
        <hr><h3>Digital prescription</h3>@if($appointment->prescription?->status === \App\Models\AppointmentPrescription::PUBLISHED)<a class="btn btn-outline" href="{{ route('doctor.prescriptions.show',$appointment) }}">View published prescription</a>@else<a class="btn btn-blue" href="{{ route('doctor.appointments.prescription.edit',$appointment) }}">Write prescription</a>@endif
    @endif
</section>
@endsection

