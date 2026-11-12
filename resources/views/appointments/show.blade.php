@extends('layouts.dashboard')
@section('title', 'Appointment details')
@section('heading', 'Appointment details')
@section('dashboard-content')
@php($payment = $appointment->payments->first())
<section class="doctor-panel">
    <h2>{{ $appointment->doctorProfile->professional_title }} {{ $appointment->doctorProfile->user->name }}</h2>
    <p>{{ $appointment->localStartsAt()->format('l, F j, Y · g:i A') }}–{{ $appointment->localEndsAt()->format('g:i A') }} ({{ $appointment->timezone }})</p>
    <p>{{ ucfirst(str_replace('_',' ',$appointment->consultation_type)) }} · {{ $appointment->duration_minutes }} minutes</p>
    @if($appointment->consultation_type === 'in_person' && $appointment->doctorSlot?->clinic_location)<p><strong>Clinic:</strong> {{ $appointment->doctorSlot->clinic_location }}</p>@endif
    <p>Appointment: <strong>{{ ucfirst(str_replace('_',' ',$appointment->status)) }}</strong> · Payment: <strong>{{ $payment?->status ?? 'not required' }}</strong></p>
    @if($payment?->failure_code === 'checkout_initialization_failed')
        <p role="alert">Safepay could not start checkout, so no payment was taken and this appointment slot was released. You can choose another available time and try again.</p>
        <p><a class="btn btn-blue" href="{{ route('appointments.create', $appointment->doctorProfile) }}">Book another time</a></p>
    @endif
    @if($payment?->provider_reference)
        <p>Payment reference: {{ $payment->provider_reference }}</p>
    @endif
    @if($appointment->status === \App\Models\Appointment::PENDING_PAYMENT)
        <p>Waiting for Safepay's verified server notification. Returning from checkout does not confirm payment.</p>
        <p><a class="btn btn-outline" href="{{ route('appointments.payment.cancel', $appointment) }}">Open payment status and retry options</a></p>
    @endif
    @if($appointment->status === \App\Models\Appointment::PENDING_CONFIRMATION)
        <p>Your payment was received, and the booking needs administrator review. The payment record is preserved.</p>
    @endif
    @if($appointment->status === \App\Models\Appointment::PENDING_CANCELLATION)
        <p role="status">Your cancellation request is waiting for the doctor to review it. The appointment remains scheduled until a decision is recorded.</p>
        @if($appointment->cancellationRequest)<p>Requested {{ $appointment->cancellationRequest->requested_at->format('M j, Y g:i A') }}. Estimated refund: {{ $payment?->currency ?? $appointment->currency }} {{ number_format($appointment->cancellationRequest->refund_amount_minor / 100, 2) }}.</p>@endif
    @endif
    @if($appointment->status === \App\Models\Appointment::COMPLETED && $appointment->prescription?->status === \App\Models\AppointmentPrescription::PUBLISHED)
        <h3>Digital prescription</h3><a class="btn btn-blue" href="{{ route('prescriptions.show',$appointment) }}">View prescription</a> <a class="btn btn-outline" href="{{ route('prescriptions.download',$appointment) }}">Download PDF</a>
    @endif
    @if($appointment->consultation_type === 'online' && in_array($appointment->status,[\App\Models\Appointment::CONFIRMED,\App\Models\Appointment::RESCHEDULED],true) && (!$payment || $payment->status === \App\Models\PaymentTransaction::PAID))
        <h3>Consultation link</h3>
        @if($appointment->meeting_link)
            <p><a class="btn btn-blue" href="{{ $appointment->meeting_link }}" target="_blank" rel="noopener noreferrer">Join consultation</a></p>
        @else
            <p>Your doctor has not added the meeting link yet. Please check again before the appointment.</p>
        @endif
    @endif
    @if(in_array($appointment->status,[\App\Models\Appointment::CONFIRMED,\App\Models\Appointment::RESCHEDULED],true) && $appointment->startsAtUtc()->greaterThan(now('UTC')->addHours(24)))
        <form method="POST" action="{{ route('appointments.cancel',$appointment) }}">@csrf
            <label for="reason">Why are you requesting cancellation?</label><textarea id="reason" name="reason" minlength="5" maxlength="1000" required></textarea>
            <p>Your request is reviewed by the doctor. If payment was captured, the estimated refund will be shown and payment processing begins after approval.</p>
            <button class="btn btn-outline" onclick="return confirm('Send a cancellation request to your doctor?')">Request cancellation</button>
        </form>
        <hr><h3>Reschedule</h3>
        <form method="POST" action="{{ route('appointments.reschedule',$appointment) }}" id="reschedule-form">@csrf
            <label for="reschedule-date">New date</label><input type="date" id="reschedule-date" min="{{ now($appointment->timezone)->toDateString() }}" required>
            <label for="reschedule-start">Available time</label><select id="reschedule-start" name="starts_at" required disabled><option>Choose a date first</option></select>
            <button class="btn btn-blue">Reschedule appointment</button>
        </form>
        <script>(()=>{const d=document.getElementById('reschedule-date'),s=document.getElementById('reschedule-start');d.addEventListener('change',async()=>{s.disabled=true;s.innerHTML='<option>Loading…</option>';try{const u=new URL(@json(route('appointments.slots',$appointment)),location.origin);u.searchParams.set('date',d.value);const r=await fetch(u,{headers:{Accept:'application/json'}}),j=await r.json();s.innerHTML='<option value="">Select a time</option>';j.slots.forEach(x=>{const o=document.createElement('option');o.value=x.value;o.textContent=x.label;s.appendChild(o)});s.disabled=!j.slots.length}catch{s.innerHTML='<option>Could not load times</option>'}})})();</script>
    @endif
    <h3>Appointment history</h3><ul>@foreach($appointment->events as $event)<li>{{ str_replace('_',' ',$event->event_type) }} · {{ $event->created_at }}</li>@endforeach</ul>
</section>
@endsection
