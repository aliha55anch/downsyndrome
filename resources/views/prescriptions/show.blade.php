@extends(auth()->user()->hasRole(\App\Models\User::ROLE_APPROVED_DOCTOR) ? 'layouts.doctor' : (auth()->user()->hasRole(\App\Models\User::ROLE_ADMIN) ? 'layouts.admin' : 'layouts.dashboard'))
@section('title', 'Digital prescription')
@section('heading', 'Digital prescription')
@section(auth()->user()->hasRole(\App\Models\User::ROLE_ADMIN) ? 'content' : (auth()->user()->hasRole(\App\Models\User::ROLE_APPROVED_DOCTOR) ? 'doctor-content' : 'dashboard-content'))
<article class="doctor-panel prescription-paper">
    <p><strong>{{ config('app.name') }}</strong> · Digital prescription</p>
    <p>Appointment LS-APT-{{ $appointment->id }} · Issued {{ $prescription->published_at?->format('F j, Y') }}</p>
    <p>Patient: {{ $appointment->patient->name }}<br>Doctor: {{ $appointment->doctorProfile->professional_title }} {{ $appointment->doctorProfile->user->name }} · {{ $appointment->doctorProfile->specialty }}<br>Consultation: {{ $appointment->localStartsAt()->format('F j, Y g:i A') }} ({{ $appointment->timezone }})</p>
    <h2>Diagnosis</h2><p>{{ $prescription->diagnosis ?: 'Not provided' }}</p>
    <h2>Medicines</h2>@forelse($prescription->medicines ?? [] as $medicine)<p><strong>{{ $medicine['name'] }}</strong> · {{ $medicine['dosage'] }} · {{ $medicine['frequency'] }} · {{ $medicine['duration'] }}</p>@empty<p>No medicines prescribed.</p>@endforelse
    @foreach(['Instructions' => $prescription->instructions, 'Additional notes' => $prescription->notes, 'Follow-up' => $prescription->follow_up] as $heading => $text)<h2>{{ $heading }}</h2><p>{{ $text ?: 'None' }}</p>@endforeach
    <a class="btn btn-blue" href="{{ route(auth()->user()->hasRole(\App\Models\User::ROLE_APPROVED_DOCTOR) ? 'doctor.prescriptions.download' : 'prescriptions.download', $appointment) }}">Download PDF</a>
    <button class="btn btn-outline" onclick="window.print()" type="button">Print</button>
</article>
@endsection


