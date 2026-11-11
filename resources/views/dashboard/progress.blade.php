@extends('layouts.dashboard')
@section('heading', 'Activity progress')
@section('dashboard-content')
<section class="dashboard-panel">
    <div class="panel-heading">
        <div><span class="eyebrow">Learning participation</span><h2>Follow one child at a time</h2></div>
        <a class="btn btn-outline" href="{{ route('dashboard.children.index') }}">Manage children</a>
    </div>
    @if ($children->isEmpty())
        <p>No child profile exists yet. Add one when you are ready.</p>
    @else
        <form method="GET" action="{{ route('dashboard.progress') }}" class="child-switcher">
            <label for="child_id">Selected child</label>
            <select id="child_id" name="child_id" onchange="this.form.submit()">
                @foreach ($children as $child)
                    <option value="{{ $child->id }}" @selected($selectedChild?->id === $child->id)>{{ $child->name }}</option>
                @endforeach
            </select>
            <span class="hint">Enrollment and progress below belong only to this child.</span>
        </form>
    @endif
</section>

@if ($selectedChild)
    <section class="dashboard-panel">
        <div class="panel-heading"><div><span class="eyebrow">By development area</span><h2>Overview for {{ $selectedChild->name }}</h2></div></div>
        @if ($cards->isEmpty())
            <p class="muted">Activities you enroll in will appear here once participation begins.</p>
        @else
            <div class="area-grid">
                @foreach ($areaSummary as $area => $summary)
                    <div class="area-card"><strong>{{ $area }}</strong><span>{{ $summary['completed'] }} of {{ $summary['total'] }} tracked activities completed</span><div class="progress-track"><span style="width:{{ $summary['percent'] }}%"></span></div><b>{{ $summary['percent'] }}%</b></div>
                @endforeach
            </div>
        @endif
    </section>

    <section class="dashboard-panel">
        <div class="panel-heading"><div><span class="eyebrow">Learning progress</span><h2>Enrolled activities</h2></div><span class="muted">Reading sections count after 6 seconds in view; videos count when playback ends or you mark them complete.</span></div>
        @forelse ($cards as $card)
            <article class="progress-activity">
                <div class="panel-heading"><div><h3>{{ $card['activity']->title }}</h3><small>{{ $card['activity']->development_area }} · {{ ucfirst(str_replace('_', ' ', $card['status'])) }}</small></div><b>{{ $card['percent'] }}%</b></div>
                <div class="progress-track"><span style="width:{{ $card['percent'] }}%"></span></div>
                <p class="hint">{{ $card['total'] ? $card['completed'].' of '.$card['total'].' tracked learning items completed.' : 'Activity-level completion' }} @if ($card['total_lessons']) · {{ $card['completed_lessons'] }} of {{ $card['total_lessons'] }} lessons complete @endif · {{ $card['videos_completed'] }} video(s) watched to the end</p>
                @if ($card['lessons']->isNotEmpty())
                    <details class="lesson-progress-details">
                        <summary>View lesson progress</summary>
                        <ul>
                            @foreach ($card['lessons'] as $lessonProgress)
                                <li><span>{{ $lessonProgress['lesson']->title }}</span><strong>{{ ucfirst(str_replace('_', ' ', $lessonProgress['status'])) }} · {{ $lessonProgress['percent'] }}%</strong></li>
                            @endforeach
                        </ul>
                    </details>
                @endif
                <a class="btn btn-outline btn-small" href="{{ route('activities.show', [$card['activity'], 'child_id' => $selectedChild->id]) }}">Open activity</a>
            </article>
        @empty
            <p class="muted">No activities are enrolled for {{ $selectedChild->name }} yet. Enroll from the activity library to see progress here.</p>
        @endforelse
    </section>

    <section class="dashboard-panel">
        <div class="panel-heading"><div><span class="eyebrow">Completion history</span><h2>Recent learning moments</h2></div></div>
        @forelse ($history as $event)
            <div class="history-row"><div><strong>{{ $event->lesson_title_snapshot ?: ($event->section_title_snapshot ?: $event->activity_title_snapshot) }}</strong><small>{{ $event->activity_title_snapshot }} · {{ ucfirst(str_replace('_', ' ', $event->completion_method ?: $event->status)) }}</small></div><time datetime="{{ $event->created_at->toIso8601String() }}">{{ $event->created_at->format('d M Y, H:i') }}</time></div>
        @empty
            <p class="muted">Completed sections and lessons will appear here for enrolled activities.</p>
        @endforelse
    </section>
@endif
@endsection
