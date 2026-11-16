@extends('layouts.admin')
@section('title', 'Consultation Requests')

@section('content')
<div class="page-title admin-dashboard-heading">
    <div>
        <span class="eyebrow">Consultancy</span>
        <h1>Consultation Requests</h1>
        <p>View consultation requests, payment statuses, and update request progress.</p>
    </div>
</div>

<section class="doctor-panel">
    <form method="GET" action="{{ route('admin.consultations.index') }}" class="form-row" style="align-items:flex-end; gap:1rem; flex-wrap:wrap;">
        <div>
            <label for="status">Request status</label>
            <select id="status" name="status">
                <option value="">All</option>
                @foreach(['pending','approved','completed','cancelled'] as $s)
                    <option value="{{ $s }}" @selected($status===$s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="payment_status">Payment status</label>
            <select id="payment_status" name="payment_status">
                <option value="">All</option>
                @foreach(['pending','paid','failed','not_required'] as $s)
                    <option value="{{ $s }}" @selected($paymentStatus===$s)>{{ ucwords(str_replace('_', ' ', $s)) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <button class="btn btn-blue" type="submit">Filter</button>
            <a class="btn btn-outline" href="{{ route('admin.consultations.index') }}">Clear</a>
        </div>
    </form>
</section>

<section class="doctor-panel">
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Doctor</th>
                    <th>Patient</th>
                    <th>Request date</th>
                    <th>Type</th>
                    <th>Fee</th>
                    <th>Payment method</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $item)
                    <tr>
                        <td>{{ $item->id }}</td>
                        <td>{{ $item->doctorProfile->user->name ?? 'N/A' }}</td>
                        <td>{{ $item->patient->name ?? 'N/A' }}<br><small>{{ $item->patient->email ?? '' }}</small></td>
                        <td>{{ $item->request_date?->format('M j, Y') }}</td>
                        <td>{{ ucwords(str_replace('_', ' ', $item->consultation_type)) }}</td>
                        <td>{{ config('app.currency', 'PKR') }} {{ number_format((float)$item->consultation_fee, 2) }}</td>
                        <td>{{ ucwords(str_replace('_', ' ', $item->payment_method)) }}</td>
                        <td><span class="doctor-status">{{ ucwords(str_replace('_', ' ', $item->payment_status)) }}</span></td>
                        <td><span class="doctor-status">{{ ucfirst($item->status) }}</span></td>
                        <td><a class="btn btn-outline btn-small" href="{{ route('admin.consultations.show', $item) }}">Manage</a></td>
                    </tr>
                @empty
                    <tr><td colspan="10">No consultation requests found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $requests->links() }}
</section>
@endsection
