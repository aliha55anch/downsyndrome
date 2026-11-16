@extends('layouts.admin')
@section('title', 'Consultation Request #'.$requestItem->id)

@section('content')
<div class="page-title admin-dashboard-heading">
    <div>
        <a class="doctor-review-back-link" href="{{ route('admin.consultations.index') }}"><span aria-hidden="true">←</span> Consultation Requests</a>
        <span class="eyebrow">Manage request</span>
        <h1>Consultation Request #{{ $requestItem->id }}</h1>
        <p>Update request status, payment status, and notes.</p>
    </div>
</div>

<div class="doctor-admin-review-grid">
    <section class="doctor-panel">
        <h2>Request details</h2>
        <dl class="doctor-detail-grid">
            <div><dt>Doctor</dt><dd>{{ $requestItem->doctorProfile->user->name ?? 'N/A' }} (ID: {{ $requestItem->doctor_profile_id }})</dd></div>
            <div><dt>Patient</dt><dd>{{ $requestItem->patient->name ?? 'N/A' }} (ID: {{ $requestItem->patient_user_id }})<br>{{ $requestItem->patient->email ?? '' }}</dd></div>
            <div><dt>Request date</dt><dd>{{ $requestItem->request_date?->format('M j, Y g:i A') }}</dd></div>
            <div><dt>Consultation type</dt><dd>{{ ucwords(str_replace('_', ' ', $requestItem->consultation_type)) }}</dd></div>
            <div><dt>Consultation fee</dt><dd>{{ config('app.currency', 'PKR') }} {{ number_format((float)$requestItem->consultation_fee, 2) }}</dd></div>
            <div><dt>Payment method</dt><dd>{{ ucwords(str_replace('_', ' ', $requestItem->payment_method)) }}</dd></div>
            <div><dt>Current payment status</dt><dd>{{ ucwords(str_replace('_', ' ', $requestItem->payment_status)) }}</dd></div>
            <div><dt>Current request status</dt><dd>{{ ucfirst($requestItem->status) }}</dd></div>
        </dl>
        @if($requestItem->patient_notes)<div class="doctor-review-subsection"><h3>Patient notes</h3><p>{{ $requestItem->patient_notes }}</p></div>@endif
    </section>

    <section class="doctor-panel">
        <h2>Update request</h2>
        <form method="POST" action="{{ route('admin.consultations.update', $requestItem) }}" class="doctor-form">
            @csrf
            @method('PATCH')
            <label for="status">Request status</label>
            <select id="status" name="status" required>
                @foreach(['pending','approved','completed','cancelled'] as $s)
                    <option value="{{ $s }}" @selected(old('status', $requestItem->status)===$s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>

            <label for="payment_status">Payment status</label>
            <select id="payment_status" name="payment_status" required>
                @foreach(['pending','paid','failed','not_required'] as $s)
                    <option value="{{ $s }}" @selected(old('payment_status', $requestItem->payment_status)===$s)>{{ ucwords(str_replace('_', ' ', $s)) }}</option>
                @endforeach
            </select>

            <label for="admin_notes">Admin notes</label>
            <textarea id="admin_notes" name="admin_notes" rows="5" maxlength="2000">{{ old('admin_notes', $requestItem->admin_notes) }}</textarea>

            <button class="btn btn-blue" type="submit">Save changes</button>
        </form>
    </section>
</div>
@endsection
