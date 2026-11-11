@extends('layouts.doctor')
@section('title','Appointments')
@section('heading','Appointments')
@section('doctor-content')
<section class="doctor-panel">
    <div class="panel-heading"><div><span class="eyebrow">Patient care</span><h2>Appointment overview</h2></div><div class="btn-row"><a class="btn btn-outline btn-small" href="{{ route('doctor.appointments.history') }}">Appointment history</a><a class="btn btn-outline btn-small" href="{{ route('doctor.revenue') }}">Revenue</a></div></div>
    <div class="appointment-tabs" role="tablist" aria-label="Appointment status">
        @foreach([['upcoming','Upcoming',$upcoming],['pending','Pending',$pending],['completed','Completed',$completed],['cancelled','Cancelled',$closed]] as [$key,$label,$items])
            <button type="button" role="tab" id="tab-{{ $key }}" aria-controls="panel-{{ $key }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}" tabindex="{{ $loop->first ? '0' : '-1' }}" data-appointment-tab="{{ $key }}">{{ $label }} <span>{{ $items->total() }}</span></button>
        @endforeach
    </div>
    @foreach([['upcoming','Upcoming',$upcoming],['pending','Pending',$pending],['completed','Completed',$completed],['cancelled','Cancelled',$closed]] as [$key,$label,$items])
        <section id="panel-{{ $key }}" role="tabpanel" aria-labelledby="tab-{{ $key }}" data-appointment-panel="{{ $key }}" @if(!$loop->first) hidden @endif>
            <h3>{{ $label }} appointments</h3>
            @if($key==='pending')
                <h4>Cancellation requests</h4>
                @forelse($cancellationRequests as $appointment)
                    <article class="doctor-panel"><h4>{{ $appointment->patient->name }} · {{ $appointment->localStartsAt()->format('D, M j, Y · g:i A') }}</h4><p>Requested {{ $appointment->cancellationRequest?->requested_at?->diffForHumans() }} · {{ $appointment->cancellationRequest?->reason }}</p><form method="POST" action="{{ route('doctor.appointments.cancellation-review', $appointment) }}">@csrf @method('PATCH')<label for="response-{{ $appointment->id }}">Response for patient</label><textarea id="response-{{ $appointment->id }}" name="response" maxlength="1000"></textarea><button class="btn btn-blue" name="decision" value="approve">Approve cancellation</button> <button class="btn btn-outline" name="decision" value="reject">Decline request</button></form></article>
                @empty<p class="muted">No cancellation requests need review.</p>@endforelse{{ $cancellationRequests->links() }}
            @endif
            @forelse($items as $appointment)
                <article class="doctor-panel appointment-list-card"><div><strong>{{ $appointment->localStartsAt()->format('D, M j, Y · g:i A') }} ({{ $appointment->timezone }})</strong><p>{{ $appointment->patient->name }} · {{ ucfirst(str_replace('_',' ',$appointment->consultation_type)) }} · {{ ucfirst(str_replace('_',' ',$appointment->status)) }} · Payment {{ $appointment->payments->first()?->status ?? 'not required' }}</p></div><a class="btn btn-outline btn-small" href="{{ route('doctor.appointments.show',$appointment) }}">View appointment</a></article>
            @empty<p class="muted">No {{ strtolower($label) }} appointments.</p>@endforelse
            {{ $items->links() }}
        </section>
    @endforeach
</section>
<script>
document.querySelectorAll('[data-appointment-tab]').forEach(tab=>tab.addEventListener('click',()=>{document.querySelectorAll('[data-appointment-tab]').forEach(item=>{item.setAttribute('aria-selected','false');item.tabIndex=-1});document.querySelectorAll('[data-appointment-panel]').forEach(panel=>panel.hidden=true);tab.setAttribute('aria-selected','true');tab.tabIndex=0;document.getElementById(tab.getAttribute('aria-controls')).hidden=false}));
</script>
@endsection
