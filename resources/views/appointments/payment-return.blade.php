@extends('layouts.auth')
@section('title', 'Appointment payment status')
@section('content')
<main class="booking-page">
    <a class="booking-back-link" href="{{ route('appointments.history') }}">← Back to appointment history</a>
    <section class="booking-panel">
        <span class="eyebrow">Appointment payment</span>
        <h1>Payment status</h1>
        <h2>{{ $appointment->doctorProfile->professional_title }} {{ $appointment->doctorProfile->user->name }}</h2>
        <p>{{ $appointment->localStartsAt()->format('l, F j, Y · g:i A') }} ({{ $appointment->timezone }})</p>

        @php($payment = $appointment->payments->sortByDesc('id')->first())
        @if($payment?->status === \App\Models\PaymentTransaction::PAID)
            @if($appointment->status === \App\Models\Appointment::CONFIRMED)
                <p role="status">Payment verified. Your appointment is confirmed.</p>
            @else
                <p role="status">Safepay verified the payment. Your appointment needs administrator review; the payment record has been preserved.</p>
            @endif
            <a class="btn btn-blue" href="{{ route('appointments.show', $appointment) }}">View appointment</a>
        @elseif($payment?->status === \App\Models\PaymentTransaction::FAILED || $payment?->status === \App\Models\PaymentTransaction::CANCELLED)
            <p role="alert">This payment attempt did not complete. The appointment is not confirmed. Choose another available time to book again.</p>
            <a class="btn btn-blue" href="{{ route('appointments.create', $appointment->doctorProfile) }}">Choose another time</a>
        @elseif($appointment->status === \App\Models\Appointment::PENDING_PAYMENT && $appointment->holdExpiresAtUtc()?->isFuture())
            <p role="status">
                @if($checkoutWasCancelled)
                    Safepay returned without confirming this payment. If checkout showed an error, you can retry below. The appointment will be confirmed only after the server verifies Safepay’s notification.
                @else
                    Safepay has returned you to Little Steps. We are waiting for its verified payment notification; this page does not confirm payment by itself.
                @endif
            </p>
            @if($checkoutWasCancelled)
                <form method="POST" action="{{ route('appointments.payment.retry', $appointment) }}">
                    @csrf
                    <button class="btn btn-blue" type="submit">Retry Safepay checkout</button>
                </form>
                <p class="muted">Retrying refreshes the secure checkout session for this same appointment. It does not create a second appointment.</p>
            @else
                <p>We’ll take you to your appointment details as soon as Safepay confirms the payment.</p>
                <script>window.setTimeout(() => window.location.reload(), 4000);</script>
            @endif
        @elseif($appointment->status === \App\Models\Appointment::PENDING_PAYMENT)
            <p role="alert">Safepay has not confirmed this payment and the appointment hold has expired. Check payment history or contact support before attempting another payment.</p>
            <a class="btn btn-outline" href="{{ route('payments.history') }}">View payment history</a>
        @else
            <p role="status">The appointment is currently {{ str_replace('_', ' ', $appointment->status) }}. Review its latest details below.</p>
        @endif

        <p><a class="btn btn-outline btn-small" href="{{ route('appointments.show', $appointment) }}">Open appointment details</a></p>
    </section>
</main>
@endsection
