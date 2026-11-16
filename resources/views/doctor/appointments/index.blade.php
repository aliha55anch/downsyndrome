@extends('layouts.doctor')
@section('title','Appointments')
@section('heading','Appointments')
@section('doctor-content')
<section class="doctor-panel appointment-overview-panel">
    <div class="panel-heading appointment-overview-heading"><div><span class="eyebrow">Patient care</span><h2>Appointment overview</h2><p>Review upcoming visits, requests, and completed care.</p></div><div class="btn-row appointment-overview-links"><a class="btn btn-outline btn-small" href="{{ route('doctor.appointments.history') }}">Appointment history</a><a class="btn btn-outline btn-small" href="{{ route('doctor.revenue') }}">Revenue</a></div></div>
    <div class="appointment-tabs" role="tablist" aria-label="Appointment status">
        @foreach([['upcoming','Upcoming',$upcoming],['pending','Pending',$pending],['completed','Completed',$completed],['cancelled','Cancelled',$closed]] as [$key,$label,$items])
            <button type="button" role="tab" id="tab-{{ $key }}" aria-controls="panel-{{ $key }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}" tabindex="{{ $loop->first ? '0' : '-1' }}" data-appointment-tab="{{ $key }}">{{ $label }} <span>{{ $items->total() }}</span></button>
        @endforeach
    </div>
    @foreach([['upcoming','Upcoming',$upcoming],['pending','Pending',$pending],['completed','Completed',$completed],['cancelled','Cancelled',$closed]] as [$key,$label,$items])
        <section class="appointment-status-panel" id="panel-{{ $key }}" role="tabpanel" aria-labelledby="tab-{{ $key }}" data-appointment-panel="{{ $key }}" @if(!$loop->first) hidden @endif>
            <div class="appointment-status-heading"><div><span class="eyebrow">{{ $label }} care</span><h3>{{ $label }} appointments</h3></div><span class="appointment-status-count">{{ $items->total() }} {{ strtolower($label) }}</span></div>
            @if($key==='pending')
                <h4>Cancellation requests</h4>
                @forelse($cancellationRequests as $appointment)
                    <article class="doctor-panel appointment-cancellation-card"><h4>{{ $appointment->patient->name }} <span>{{ $appointment->localStartsAt()->format('D, M j, Y · g:i A') }}</span></h4><p>Requested {{ $appointment->cancellationRequest?->requested_at?->diffForHumans() }}</p><p>{{ $appointment->cancellationRequest?->reason }}</p><form method="POST" action="{{ route('doctor.appointments.cancellation-review', $appointment) }}">@csrf @method('PATCH')<label for="response-{{ $appointment->id }}">Response for patient</label><textarea id="response-{{ $appointment->id }}" name="response" maxlength="1000"></textarea><div class="appointment-review-actions"><button class="btn btn-blue" name="decision" value="approve">Approve cancellation</button><button class="btn btn-outline" name="decision" value="reject">Decline request</button></div></form></article>
                @empty<p class="muted">No cancellation requests need review.</p>@endforelse{{ $cancellationRequests->links() }}
            @endif
            @forelse($items as $appointment)
                <article class="doctor-panel appointment-list-card"><div class="appointment-list-main"><strong>{{ $appointment->localStartsAt()->format('D, M j, Y · g:i A') }} <span>({{ $appointment->timezone }})</span></strong><p>{{ $appointment->patient->name }}</p><div class="appointment-list-meta"><span>{{ ucfirst(str_replace('_',' ',$appointment->consultation_type)) }}</span><span>{{ ucfirst(str_replace('_',' ',$appointment->status)) }}</span><span>Payment {{ $appointment->payments->first()?->status ?? 'not required' }}</span></div></div><a class="btn btn-outline btn-small" href="{{ route('doctor.appointments.show',$appointment) }}">View appointment</a></article>
            @empty<p class="muted">No {{ strtolower($label) }} appointments.</p>@endforelse
            {{ $items->links() }}
        </section>
    @endforeach
</section>
<script>
document.querySelectorAll('[data-appointment-tab]').forEach(tab=>tab.addEventListener('click',()=>{document.querySelectorAll('[data-appointment-tab]').forEach(item=>{item.setAttribute('aria-selected','false');item.tabIndex=-1});document.querySelectorAll('[data-appointment-panel]').forEach(panel=>panel.hidden=true);tab.setAttribute('aria-selected','true');tab.tabIndex=0;document.getElementById(tab.getAttribute('aria-controls')).hidden=false}));
</script>
@endsection
