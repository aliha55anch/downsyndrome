@extends('layouts.auth')

@section('title', 'Activities')

@section('content')
<section class="page-title" id="activities">
    <div class="container">
        <h1>Home Activities</h1>
        <p>Explore activities, save the ones you choose, and continue learning at your own pace.</p>
    </div>
</section>

<nav class="activity-tabs" aria-label="Activity lists">
    <a href="{{ route('activities.index', ['tab' => 'my', 'child_id' => $selectedChild?->id]) }}" @if($tab === 'my') aria-current="page" @endif>My Activities</a>
    <a href="{{ route('activities.index', ['tab' => 'all', 'child_id' => $selectedChild?->id]) }}" @if($tab === 'all') aria-current="page" @endif>All Activities</a>
    <a href="{{ route('activities.index', ['tab' => 'completed', 'child_id' => $selectedChild?->id]) }}" @if($tab === 'completed') aria-current="page" @endif>Completed Activities</a>
</nav>

@if ($tab === 'my')
    <h2 class="activity-list-heading">Activities you’re taking</h2>
@elseif ($tab === 'completed')
    <h2 class="activity-list-heading">Completed activities</h2>
@else
    <h2 class="activity-list-heading">Explore all activities</h2>
@endif

@if ($tab !== 'all' && ! auth()->check())
    <div class="notice success"><a href="{{ route('login') }}">Log in</a> to see activities enrolled in your account and your completion history.</div>
@endif

@if ($tab === 'all')
    <form method="GET" action="{{ route('activities.index') }}" class="filter-bar">
        <input type="hidden" name="tab" value="all">
        <div class="form-row">
            <div><label for="search">Search</label><input type="search" id="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search activities"></div>
            @if ($children->isNotEmpty())
                <div><label for="child_id">Recommendations for</label><select id="child_id" name="child_id" onchange="this.form.submit()">@foreach ($children as $child)<option value="{{ $child->id }}" @selected($selectedChild?->id === $child->id)>{{ $child->name }} ({{ $child->date_of_birth->age }} years)</option>@endforeach</select></div>
            @endif
            <div><label for="age_group">Age group</label><select id="age_group" name="age_group"><option value="">All ages</option>@foreach ($ageGroups as $ageGroup)<option value="{{ $ageGroup }}" @selected(($filters['age_group'] ?? '') === $ageGroup)>{{ $ageGroup }}</option>@endforeach</select></div>
            <div><label for="development_area">Development area</label><select id="development_area" name="development_area"><option value="">All areas</option>@foreach ($developmentAreas as $area)<option value="{{ $area }}" @selected(($filters['development_area'] ?? '') === $area)>{{ $area }}</option>@endforeach</select></div>
            <div style="align-self:end"><button class="btn btn-blue" type="submit">Find activities</button></div>
        </div>
    </form>
    @if ($selectedChild)<div class="notice success"><strong>Recommendations for {{ $selectedChild->name }}:</strong> age-matched activities appear first.</div>@endif
@endif

<p class="muted">{{ $activities->total() }} activities found.</p>
<div class="grid activity-grid">
    @forelse ($activities as $activity)
        @php($enrollment = $enrollmentByActivity->get($activity->id))
        @php($isRecommended = $selectedChild && $activity->min_age !== null && $activity->max_age !== null && $selectedChild->date_of_birth->age >= $activity->min_age && $selectedChild->date_of_birth->age <= $activity->max_age)
        @php($resumeUrl = $enrollment ? route('activities.show', ['activity' => $activity->id, 'child_id' => $enrollment->child_id ?? $selectedChild?->id, 'resume' => 1]) : null)
        @if ($enrollment && $enrollment->last_section_id) @php($resumeUrl .= '#section-'.$enrollment->last_section_id) @endif
        <article class="card activity-management-card">
            <header class="activity-card-header">
                @if ($activity->icon_path)<img class="activity-icon" src="{{ route('activities.icon', $activity) }}" alt="">@endif
                <div><span class="eyebrow">Activity {{ $activities->firstItem() + $loop->index }}</span><h2>{{ $activity->title }}</h2></div>
            </header>
            @if ($activity->featured_image_path)<img src="{{ route('activities.image', $activity) }}" alt="" class="activity-list-image">@endif
            <div class="activity-tag-row"><span class="tag">{{ $activity->development_area }}</span><span class="tag tag-age">{{ $activity->age_group }}</span>@if($isRecommended)<span class="tag tag-recommended">Recommended</span>@endif</div>
            <p>{{ $activity->short_description }}</p>
            @if ($activity->estimated_minutes)<p class="muted">Estimated time: {{ $activity->estimated_minutes }} minutes</p>@endif

            @if ($enrollment)
                <div class="enrollment-summary">
                    <span class="status-pill {{ $enrollment->status === 'completed' ? 'is-complete' : 'is-active' }}">{{ ucfirst(str_replace('_', ' ', $enrollment->status)) }}</span>
                    <div class="progress-track" role="progressbar" aria-label="{{ $activity->title }} progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $enrollment->progress_percent }}"><span style="width:{{ $enrollment->progress_percent }}%"></span></div>
                    <small>{{ $enrollment->progress_percent }}% complete</small>
                </div>
                @if ($enrollment->status === 'completed')
                    <span class="completion-badge">Completed</span>
                @elseif (auth()->check() && auth()->user()->hasRole('parent'))
                    <a class="btn btn-blue btn-small" href="{{ $resumeUrl }}">{{ $enrollment->status === 'enrolled' ? 'Start' : 'Continue' }}</a>
                @else
                    <a class="btn btn-outline btn-small" href="{{ route('activities.show', $activity) }}">Open activity</a>
                @endif
            @elseif (auth()->check() && auth()->user()->hasRole('parent'))
                <form method="POST" action="{{ route('activities.enroll', $activity) }}">
                    @csrf
                    @if ($selectedChild)<input type="hidden" name="child_id" value="{{ $selectedChild->id }}">@endif
                    <button class="btn btn-blue btn-small" type="submit">Enroll Now</button>
                </form>
            @elseif (auth()->guest())
                <div class="btn-row"><a class="btn btn-blue btn-small" href="{{ route('login') }}">Log in to enroll</a><a class="btn btn-outline btn-small" href="{{ route('activities.show', $activity) }}">Preview</a></div>
            @else
                <a class="btn btn-outline btn-small" href="{{ route('activities.show', $activity) }}">View activity</a>
            @endif
        </article>
    @empty
        <div class="card empty-state">
            @if ($tab === 'my')
                <h2>No activities yet</h2><p>Choose All Activities to explore and enroll in an activity.</p><a class="btn btn-blue" href="{{ route('activities.index', ['tab' => 'all']) }}">Explore activities</a>
            @elseif ($tab === 'completed')
                <h2>No completed activities yet</h2><p>Completed activities will appear here after you finish them.</p>
            @else
                <h2>No activities found</h2><p>Try changing the search or filters.</p>
            @endif
        </div>
    @endforelse
</div>

{{ $activities->links() }}
@if ($tab === 'all')
    <div class="disclaimer">These activities are educational resources, not medical advice or therapy. If you have concerns about your child's development, speak with a qualified professional.</div>
@endif
@endsection
