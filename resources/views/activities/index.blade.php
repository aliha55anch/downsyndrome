@extends('layouts.activities')

@section('title', 'Activities')

@section('content')
@php
    $hasFilters = filled($filters['search'] ?? null) || filled($filters['age_group'] ?? null) || filled($filters['development_area'] ?? null);
    $tabParams = array_filter([
        'search' => $filters['search'] ?? null,
        'age_group' => $filters['age_group'] ?? null,
        'development_area' => $filters['development_area'] ?? null,
        'child_id' => $selectedChild?->id,
    ], fn ($value) => $value !== null && $value !== '');

    if ($tab === 'my') {
        $kicker = 'My activities';
        $heading = 'Pick up where you left off';
        $description = 'Your enrolled activities, ready whenever you have a few minutes together.';
    } elseif ($tab === 'completed') {
        $kicker = 'Completed activities';
        $heading = 'Small steps worth celebrating';
        $description = 'A record of the activities you have finished together. Every one counts.';
    } else {
        $kicker = 'Activity library';
        $heading = 'Find the next little win';
        $description = 'Practical, play-based ideas for everyday routines. Filter by age or focus area to find a good fit.';
    }
@endphp

<section class="al-hero">
    <div class="container al-hero__inner">
        <div class="al-hero__copy">
            <span class="al-eyebrow">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v2M12 19v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M3 12h2M19 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/><circle cx="12" cy="12" r="4"/></svg>
                Little Steps activity library
            </span>
            <h1>Learning that feels like <span>play</span>.</h1>
            <p>Discover simple, play-based activities made for real homes and real routines. Support your child's development with ideas that fit the time you have.</p>
            <div class="al-hero__actions">
                <a class="al-cta al-cta--primary" href="#activity-library">
                    Browse activities
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </a>
                @auth
                    @if (auth()->user()->hasRole(\App\Models\User::ROLE_PARENT))
                        <a class="al-cta al-cta--ghost" href="{{ route('dashboard') }}">Open my dashboard</a>
                    @endif
                @else
                    <a class="al-cta al-cta--ghost" href="{{ route('login') }}">Log in to track progress</a>
                @endauth
            </div>
            <dl class="al-hero__stats">
                <div class="al-hero__stat">
                    <dt>Activities</dt>
                    <dd>{{ $totalActivities }}</dd>
                </div>
                <div class="al-hero__stat">
                    <dt>Age groups</dt>
                    <dd>{{ count($ageGroups) }}</dd>
                </div>
                <div class="al-hero__stat">
                    <dt>Focus areas</dt>
                    <dd>{{ count($developmentAreas) }}</dd>
                </div>
            </dl>
        </div>

        <div class="al-hero__art" aria-hidden="true">
            <div class="al-hero__photo">
                <img src="{{ asset('assets/images/hero.jpeg') }}" alt="">
                <div class="al-hero__caption">
                    <span>Made for everyday moments</span>
                    A library of small wins.
                </div>
            </div>
            <div class="al-float al-float--top">
                <span class="al-float__icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                </span>
                <span><strong>10–15 minutes</strong><small>Short and doable</small></span>
            </div>
            <div class="al-float al-float--bottom">
                <span class="al-float__icon is-amber">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                </span>
                <span><strong>Celebrate progress</strong><small>Every step counts</small></span>
            </div>
        </div>
    </div>
</section>

<section class="al-library" id="activity-library">
    <div class="container">
        <div class="al-toolbar">
            <nav class="al-tabs" aria-label="Activity lists">
                <a class="al-tab" href="{{ route('activities.index', array_merge($tabParams, ['tab' => 'my'])) }}" @if ($tab === 'my') aria-current="page" @endif>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 4h12a1 1 0 0 1 1 1v16l-7-4-7 4V5a1 1 0 0 1 1-1Z"/></svg>
                    My Activities
                </a>
                <a class="al-tab" href="{{ route('activities.index', array_merge($tabParams, ['tab' => 'all'])) }}" @if ($tab === 'all') aria-current="page" @endif>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
                    All Activities
                </a>
                <a class="al-tab" href="{{ route('activities.index', array_merge($tabParams, ['tab' => 'completed'])) }}" @if ($tab === 'completed') aria-current="page" @endif>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8.5 12 2.5 2.5 4.5-5"/></svg>
                    Completed
                </a>
            </nav>
            <span class="al-toolbar__note">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z"/><path d="M9.2 9a3 3 0 1 1 4.3 2.7c-.9.4-1.5 1.1-1.5 2.1v.2"/><path d="M12 17.2h.01"/></svg>
                Fresh ideas for everyday learning
            </span>
        </div>

        @if ($tab === 'all')
            <form method="GET" action="{{ route('activities.index') }}" class="al-filters" role="search">
                <input type="hidden" name="tab" value="all">
                <div class="al-filters__grid">
                    <div class="al-field al-field--search">
                        <label for="search">Search activities</label>
                        <div class="al-input-wrap">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                            <input type="search" id="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Try “sensory play” or “language”">
                        </div>
                    </div>

                    @if ($children->isNotEmpty())
                        <div class="al-field">
                            <label for="child_id">Recommendations for</label>
                            <select id="child_id" name="child_id" data-auto-submit>
                                @foreach ($children as $child)
                                    <option value="{{ $child->id }}" @selected($selectedChild?->id === $child->id)>{{ $child->name }} ({{ $child->date_of_birth->age }} years)</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="al-field">
                        <label for="age_group">Age group</label>
                        <select id="age_group" name="age_group" data-auto-submit>
                            <option value="">All ages</option>
                            @foreach ($ageGroups as $ageGroup)
                                <option value="{{ $ageGroup }}" @selected(($filters['age_group'] ?? '') === $ageGroup)>{{ $ageGroup }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="al-field">
                        <label for="development_area">Development area</label>
                        <select id="development_area" name="development_area" data-auto-submit>
                            <option value="">All areas</option>
                            @foreach ($developmentAreas as $area)
                                <option value="{{ $area }}" @selected(($filters['development_area'] ?? '') === $area)>{{ $area }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="al-filters__actions">
                        <button class="al-cta al-cta--teal" type="submit">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                            Find activities
                        </button>
                        @if ($hasFilters)
                            <a class="al-clear" href="{{ route('activities.index', ['tab' => 'all']) }}">Clear filters</a>
                        @endif
                    </div>
                </div>
            </form>
        @endif

        <div class="al-section-head">
            <div>
                <span class="al-kicker">{{ $kicker }}</span>
                <h2>{{ $heading }}</h2>
                <p>{{ $description }}</p>
            </div>
            <span class="al-result-pill"><strong>{{ $activities->total() }}</strong> {{ $activities->total() === 1 ? 'activity' : 'activities' }}</span>
        </div>

        @if ($tab !== 'all' && ! auth()->check())
            <div class="al-notice">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <p><a href="{{ route('login') }}">Log in</a> to see the activities enrolled in your account and your completion history.</p>
            </div>
        @endif

        @if ($tab === 'all' && $selectedChild)
            <div class="al-notice al-notice--warm">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3 15 9l6 .8-4.5 4.3 1.1 6.1L12 17.3 6.4 20.2l1.1-6.1L3 9.8 9 9l3-6Z"/></svg>
                <p><strong>Recommendations for {{ $selectedChild->name }}:</strong> age-matched activities appear first in the list.</p>
            </div>
        @endif

        <div class="al-grid">
            @forelse ($activities as $activity)
                @php
                    $enrollment = $enrollmentByActivity->get($activity->id);
                    $isRecommended = $selectedChild && $activity->min_age !== null && $activity->max_age !== null && $selectedChild->date_of_birth->age >= $activity->min_age && $selectedChild->date_of_birth->age <= $activity->max_age;
                    $isComplete = $enrollment?->status === 'completed';
                    $areaSlug = \Illuminate\Support\Str::slug($activity->development_area ?: 'activity');
                    $openUrl = route('activities.show', $activity);
                    $resumeUrl = $enrollment ? route('activities.show', ['activity' => $activity->id, 'child_id' => $enrollment->child_id ?? $selectedChild?->id, 'resume' => 1]) : null;
                    if ($enrollment && $enrollment->last_section_id) {
                        $resumeUrl .= '#section-'.$enrollment->last_section_id;
                    }
                @endphp
                <article class="al-card al-area-{{ $areaSlug }}">
                    <div class="al-card__media">
                        @if ($activity->featured_image_path)
                            <img src="{{ route('activities.image', $activity) }}" alt="{{ $activity->title }}" loading="lazy">
                        @else
                            <div class="al-card__fallback">
                                <span class="al-card__fallback-icon">
                                    @if ($activity->icon_path)
                                        <img src="{{ route('activities.icon', $activity) }}" alt="">
                                    @else
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v15H6.5A2.5 2.5 0 0 0 4 20.5v-15Z"/><path d="M4 20.5A2.5 2.5 0 0 1 6.5 18H20"/><path d="M9 7h7M9 11h5"/></svg>
                                    @endif
                                </span>
                                <span class="al-card__fallback-label">{{ $activity->development_area ?: 'Little Steps activity' }}</span>
                            </div>
                        @endif

                        <div class="al-card__badges">
                            <span class="al-badge al-badge--area">{{ $activity->development_area ?: 'Activity' }}</span>
                            @if ($isRecommended)
                                <span class="al-badge al-badge--recommended">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                    Recommended
                                </span>
                            @endif
                            @if ($isComplete)
                                <span class="al-badge al-badge--done">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                    Completed
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="al-card__body">
                        <div class="al-card__eyebrow">
                            <span class="al-card__age">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 3v3M17 3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v14H4V6a1 1 0 0 1 1-1Z"/></svg>
                                {{ $activity->age_group ?: 'All ages' }}
                            </span>
                            @if ($activity->difficulty)
                                <span class="al-card__level">{{ ucfirst($activity->difficulty) }}</span>
                            @endif
                        </div>

                        <h3><a href="{{ $openUrl }}">{{ $activity->title }}</a></h3>
                        <p class="al-card__desc">{{ $activity->short_description }}</p>

                        <div class="al-card__facts">
                            <span class="al-card__fact">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                                {{ $activity->estimated_minutes ? $activity->estimated_minutes.' min' : 'Self-paced' }}
                            </span>
                            <span class="al-card__fact al-card__fact--muted">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 10 9-7 9 7v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-9Z"/><path d="M9 21v-8h6v8"/></svg>
                                Home activity
                            </span>
                        </div>

                        @if ($enrollment)
                            <div class="al-progress">
                                <div class="al-progress__top">
                                    <span class="al-progress__status {{ $isComplete ? 'is-complete' : '' }}">{{ ucfirst(str_replace('_', ' ', $enrollment->status)) }}</span>
                                    <span class="al-progress__percent">{{ $enrollment->progress_percent }}%</span>
                                </div>
                                <div class="al-progress__track" role="progressbar" aria-label="{{ $activity->title }} progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $enrollment->progress_percent }}">
                                    <span style="width:{{ min(100, max(0, (int) $enrollment->progress_percent)) }}%"></span>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="al-card__footer">
                        @if ($isComplete)
                            <a class="al-btn al-btn--done" href="{{ $openUrl }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                View completed activity
                            </a>
                        @elseif ($enrollment)
                            <a class="al-btn al-btn--primary" href="{{ $resumeUrl }}">
                                {{ $enrollment->status === 'enrolled' ? 'Start activity' : 'Continue learning' }}
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                            </a>
                            <a class="al-btn al-btn--secondary" href="{{ $openUrl }}">Details</a>
                        @elseif (auth()->check() && auth()->user()->hasRole(\App\Models\User::ROLE_PARENT))
                            <form method="POST" action="{{ route('activities.enroll', $activity) }}">
                                @csrf
                                @if ($selectedChild)
                                    <input type="hidden" name="child_id" value="{{ $selectedChild->id }}">
                                @endif
                                <button class="al-btn al-btn--primary" type="submit">Enroll now</button>
                            </form>
                            <a class="al-btn al-btn--secondary" href="{{ $openUrl }}">Preview</a>
                        @elseif (auth()->guest())
                            <a class="al-btn al-btn--primary" href="{{ route('login') }}">Log in to enroll</a>
                            <a class="al-btn al-btn--secondary" href="{{ $openUrl }}">Preview</a>
                        @else
                            <a class="al-btn al-btn--secondary" href="{{ $openUrl }}">View activity</a>
                        @endif
                    </div>
                </article>
            @empty
                <div class="al-empty">
                    <span class="al-empty__icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v15H6.5A2.5 2.5 0 0 0 4 20.5v-15Z"/><path d="M4 20.5A2.5 2.5 0 0 1 6.5 18H20"/><path d="M9 8h7M9 12h5"/></svg>
                    </span>
                    @if ($tab === 'my')
                        <h3>Your activity list is waiting</h3>
                        <p>Explore the library and enroll in an activity that fits your family's day. It will be saved here for easy access.</p>
                        <a class="al-cta al-cta--teal" href="{{ route('activities.index', ['tab' => 'all']) }}">Explore all activities</a>
                    @elseif ($tab === 'completed')
                        <h3>Your first win is ahead</h3>
                        <p>Activities you finish together will be saved here, ready to revisit whenever you like.</p>
                        <a class="al-cta al-cta--teal" href="{{ route('activities.index', ['tab' => 'my']) }}">See my activities</a>
                    @else
                        <h3>No activities match those filters</h3>
                        <p>Try a different age group or development area, or clear the filters to see the whole library.</p>
                        <a class="al-cta al-cta--teal" href="{{ route('activities.index', ['tab' => 'all']) }}">Show all activities</a>
                    @endif
                </div>
            @endforelse
        </div>

        {{ $activities->links('pagination.activities') }}

        @if ($tab === 'all')
            <div class="al-disclaimer">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8h.01M11 12h1v4h1"/></svg>
                <span>These activities are educational resources, not medical advice or therapy. If you have concerns about your child's development, speak with a qualified professional.</span>
            </div>
        @endif
    </div>
</section>
@endsection
