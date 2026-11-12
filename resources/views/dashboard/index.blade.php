@extends('layouts.dashboard')
@section('heading', 'My Dashboard')
@section('dashboard-content')
@if ($needsProfile)
    <section class="welcome-card"><div><span class="eyebrow">Family setup</span><h2>Add a child profile to personalize progress</h2><p>You can browse and enroll in activities now, then connect progress to a child later.</p></div><div class="btn-row"><a class="btn btn-blue" href="{{ route('dashboard.profile') }}">Complete profile</a><a class="btn btn-outline" href="{{ route('dashboard.progress') }}">Add later</a></div></section>
@endif

<section class="dashboard-panel"><div class="panel-heading"><div><span class="eyebrow">Your care</span><h2>Upcoming appointments</h2></div><a class="btn btn-outline btn-small" href="{{ route('appointments.index') }}">View appointment details</a></div>
    @forelse($upcomingAppointments as $appointment)
        <article class="enrolled-activity-row">
            <div>
                <strong>{{ $appointment->doctorProfile->professional_title }} {{ $appointment->doctorProfile->user->name }}</strong>
                <p>{{ $appointment->localStartsAt()->format('D, M j, Y · g:i A') }} · {{ $appointment->timezone }} · {{ $appointment->consultation_type === 'online' ? 'Online' : 'In person' }}</p>
                <span class="status-pill is-active">{{ ucfirst(str_replace('_',' ',$appointment->status)) }}</span> · Payment {{ $appointment->payments->first()?->status ?? 'not required' }}
            </div>
            <a class="btn btn-outline btn-small" href="{{ route('appointments.show',$appointment) }}">View details</a>
        </article>
    @empty
        <div class="dashboard-empty-action"><p>No upcoming appointments.</p><a class="btn btn-blue btn-small" href="{{ route('doctors.index') }}">Browse professional consultancy</a></div>
    @endforelse
</section>

<section class="dashboard-cards enrollment-stats" aria-label="Activity enrollment summary">
    <article class="dashboard-card sky"><small>Total enrolled activities</small><h2>{{ $enrolledCount }}</h2><a class="btn btn-outline btn-small" href="{{ route('dashboard.progress') }}">Activity progress</a></article>
    <article class="dashboard-card sand"><small>Activities in progress</small><h2>{{ $inProgressCount }}</h2><p>Started and ready to continue</p></article>
    <article class="dashboard-card mint"><small>Completed activities</small><h2>{{ $completedCount }}</h2><p>Activities finished</p></article>
    <article class="dashboard-card coral"><small>Overall activity progress</small><h2>{{ $overallProgress }}%</h2><div class="progress-track"><span style="width:{{ $overallProgress }}%"></span></div></article>
</section>

<div class="dashboard-secondary-grid">
<section class="dashboard-panel">
    <div class="panel-heading"><div><span class="eyebrow">Your activities</span><h2>Start where you left off</h2></div><a class="btn btn-outline btn-small" href="{{ route('dashboard.progress') }}">View Activity Progress</a></div>
    @forelse ($enrollments as $enrollment)
        @if ($enrollment->activity)
            @php($resumeUrl = route('activities.show', ['activity' => $enrollment->activity->id, 'child_id' => $enrollment->child_id, 'resume' => 1]))
            @if ($enrollment->last_section_id) @php($resumeUrl .= '#section-'.$enrollment->last_section_id) @endif
            <article class="enrolled-activity-row">
                <div class="enrolled-activity-main">
                    <span class="eyebrow">{{ $enrollment->child?->name ? 'For '.$enrollment->child->name : 'Enrolled activity' }}</span>
                    <h3>{{ $enrollment->activity->title }}</h3>
                    <p>{{ $enrollment->activity->short_description }}</p>
                    <span class="status-pill {{ $enrollment->status === 'completed' ? 'is-complete' : 'is-active' }}">{{ ucfirst(str_replace('_', ' ', $enrollment->status)) }}</span>
                </div>
                <div class="enrolled-activity-progress"><strong>{{ $enrollment->progress_percent }}%</strong><div class="progress-track"><span style="width:{{ $enrollment->progress_percent }}%"></span></div>
                    @if ($enrollment->status === 'completed')<span class="completion-badge">Completed</span>@else<a class="btn btn-blue btn-small" href="{{ $resumeUrl }}">{{ $enrollment->status === 'enrolled' ? 'Start' : 'Continue' }}</a>@endif
                </div>
            </article>
        @endif
    @empty
        <div class="empty-state"><h3>No activities enrolled yet</h3><p>Enrolled activities and their progress summaries will appear here.</p><a class="btn btn-blue" href="{{ route('dashboard.progress') }}">View Activity Progress</a></div>
    @endforelse
</section>

<section class="dashboard-panel">
    <div class="panel-heading"><div><span class="eyebrow">Your family</span><h2>Children</h2></div><a class="btn btn-outline btn-small" href="{{ route('dashboard.children.index') }}">Manage children</a></div>
    @forelse ($user->children as $child)
        <a class="dashboard-child-card" href="{{ route('dashboard.children.edit', $child) }}"><strong>{{ $child->name }}</strong><span>{{ $child->date_of_birth?->age }} years old</span></a>
    @empty
        <p class="muted">No child profiles saved yet.</p>
    @endforelse
</section>
</div>
@endsection

