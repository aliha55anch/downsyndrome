@extends('layouts.public')
@section('title', 'My Consultation Requests')
@section('body_class', 'consultancy-page')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/consultancy.css') }}">
@endpush

@section('content')
<section class="pc-directory">
    <div class="container">
        <div class="pc-results-head">
            <div>
                <span class="pc-kicker">My requests</span>
                <h1>Consultation requests</h1>
                <p>Track the status and payment details of your consultation requests.</p>
            </div>
            <span class="pc-result-pill"><strong>{{ $requests->total() }}</strong> {{ \Illuminate\Support\Str::plural('request', $requests->total()) }}</span>
        </div>

        <div class="pc-grid" style="grid-template-columns:1fr;">
            @forelse($requests as $item)
                <article class="pc-doctor-card" style="padding:1.25rem;">
                    <h3 style="margin-top:0;">{{ $item->doctorProfile->professional_title ?? '' }} {{ $item->doctorProfile->user->name ?? 'Doctor' }}</h3>
                    <p><strong>Reference:</strong> LS-CNS-{{ str_pad((string) $item->id, 8, '0', STR_PAD_LEFT) }} &middot; <strong>Date:</strong> {{ $item->request_date?->format('M j, Y') }}</p>
                    <p><strong>Type:</strong> {{ ucwords(str_replace('_', ' ', $item->consultation_type)) }} &middot; <strong>Fee:</strong> {{ config('app.currency', 'PKR') }} {{ number_format((float)$item->consultation_fee, 2) }}</p>
                    <p><strong>Payment method:</strong> {{ ucwords(str_replace('_', ' ', $item->payment_method)) }} &middot; <strong>Payment status:</strong> {{ ucwords(str_replace('_', ' ', $item->payment_status)) }}</p>
                    <p><strong>Request status:</strong> {{ ucfirst($item->status) }}</p>
                    @if($item->patient_notes)<p><strong>Your notes:</strong> {{ $item->patient_notes }}</p>@endif
                    @if($item->admin_notes)<p><strong>Admin notes:</strong> {{ $item->admin_notes }}</p>@endif
                </article>
            @empty
                <div class="pc-empty">
                    <h3>No consultation requests yet</h3>
                    <p>When you request a consultation from a doctor profile, it will appear here.</p>
                    <a class="pc-cta pc-cta--teal" href="{{ route('doctors.index') }}">Browse professionals</a>
                </div>
            @endforelse
        </div>

        {{ $requests->links() }}
    </div>
</section>
@endsection
