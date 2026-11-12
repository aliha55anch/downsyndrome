@extends('layouts.dashboard')
@section('title', 'Payment history')
@section('heading', 'Payment history')
@section('dashboard-content')
<section class="doctor-panel"><h2>Your appointment payments</h2>@forelse($payments as $payment)<article class="doctor-document-row"><div><strong>{{ $payment->payable?->doctorProfile?->professional_title }} {{ $payment->payable?->doctorProfile?->user?->name ?? 'Appointment' }}</strong><p>{{ $payment->currency }} {{ number_format($payment->amount_minor / 100, 2) }} · {{ ucfirst($payment->status) }} · {{ $payment->paid_at?->format('M j, Y g:i A') ?? 'Not paid' }}</p>@if($payment->provider_reference)<small>Payment reference: {{ $payment->provider_reference }}</small>@endif</div>@if($payment->status === \App\Models\PaymentTransaction::PAID)<a class="btn btn-outline btn-small" href="{{ route('payments.receipt', $payment) }}">View receipt</a>@endif</article>@empty<p>No appointment payment transactions yet.</p>@endforelse{{ $payments->links() }}</section>
@endsection
