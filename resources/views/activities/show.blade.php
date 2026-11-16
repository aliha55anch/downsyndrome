@extends('layouts.auth')

@section('title', $activity->title)
@section('shell_class', 'auth-shell--activity')

@section('content')
@if ($selectedLesson && $selectedLesson->sections->contains('content_type', 'powerpoint'))<link rel="stylesheet" href="{{ asset('assets/js/pptx-viewer.bundle.css') }}">@endif
<a class="back-link" href="{{ route('activities.index') }}">Back to all activities</a>

<article class="detail-box">
    <header class="activity-header">
        @if ($activity->icon_path)<img class="activity-icon activity-icon-large" src="{{ ($isPreview ?? false) ? route('admin.activities.icon', $activity) : route('activities.icon', $activity) }}" alt="">@endif
        <div><div class="activity-header-tags"><span class="tag">Activity {{ $activity->id }}</span><span class="tag">{{ $activity->development_area }}</span><span class="tag tag-age">{{ $activity->age_group }}</span></div><h1>{{ $activity->title }}</h1><p><strong>{{ $activity->short_description }}</strong></p></div>
    </header>
    @auth
        @if (auth()->user()->hasRole('parent'))
            @if ($enrollment)
                <div class="activity-enrollment-banner">
                    <div><span class="eyebrow">Your activity</span><strong>{{ $enrollment->status === 'completed' ? 'Completed' : ucfirst(str_replace('_', ' ', $enrollment->status)) }}</strong><span data-activity-progress-text>{{ $enrollment->progress_percent }}% complete</span></div>
                    <div class="progress-track" data-activity-progress-track role="progressbar" aria-label="Activity progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $enrollment->progress_percent }}"><span style="width:{{ $enrollment->progress_percent }}%"></span></div>
                </div>
                @if ($selectedChild && $isTracking && $enrollment->status !== 'completed')
                    @php($activityVideoRequired = (bool) ($activity->video_path || $activity->youtube_url))
                    @php($activityCompletionUnlocked = (!$activityVideoRequired || $activityVideoProgressPercent >= 75) && $readyVideoSectionCount === $videoSectionCount)
                    <form method="POST" action="{{ route('dashboard.progress.complete', $activity) }}" class="activity-complete-form" data-activity-completion-form data-activity-video-required="{{ $activityVideoRequired ? '1' : '0' }}" data-activity-video-unlocked="{{ $activityVideoProgressPercent >= 75 ? '1' : '0' }}" data-required-video-count="{{ $videoSectionCount }}" data-ready-video-count="{{ $readyVideoSectionCount }}">
                        @csrf
                        <input type="hidden" name="child_id" value="{{ $selectedChild->id }}">
                        <p data-video-completion-hint>{{ $activityCompletionUnlocked ? 'Finished this activity together?' : 'Watch at least 75% of each activity video to unlock completion.' }}</p>
                        <button class="btn btn-outline btn-small" type="submit" @disabled(!$activityCompletionUnlocked)>Mark activity complete</button>
                    </form>
                @endif
            @else
                <form method="POST" action="{{ route('activities.enroll', $activity) }}" class="activity-enrollment-banner">
                    @csrf
                    @if ($selectedChild)<input type="hidden" name="child_id" value="{{ $selectedChild->id }}">@endif
                    <div><span class="eyebrow">Save this activity</span><strong>Enroll to keep it in My Activities and save your progress.</strong></div>
                    <button class="btn btn-blue btn-small" type="submit">Enroll Now</button>
                </form>
            @endif
        @endif
    @endauth
    @if ($selectedChild)
        <section class="activity-tracking-control" aria-label="Activity progress tracking">
            <div><span class="eyebrow">Progress tracking</span><h2>{{ $isTracking ? 'Tracking for '.$selectedChild->name : 'Choose whether to track this activity' }}</h2><p class="hint">{{ $isTracking ? 'Your lesson and section progress for this child is being saved.' : 'This activity will not appear on the progress dashboard until you start tracking it.' }}</p></div>
            <form method="POST" action="{{ route('dashboard.progress.tracking', $activity) }}">
                @csrf
                <input type="hidden" name="child_id" value="{{ $selectedChild->id }}">
                <input type="hidden" name="is_tracking" value="{{ $isTracking ? '0' : '1' }}">
                <button class="btn {{ $isTracking ? 'btn-outline' : 'btn-blue' }}" type="submit">{{ $isTracking ? 'Stop tracking for '.$selectedChild->name : 'Track progress for '.$selectedChild->name }}</button>
            </form>
        </section>
        @if (count($children) > 1)
            <form method="GET" action="{{ route('activities.show', $activity) }}" class="child-switcher">
                @if ($selectedLesson)<input type="hidden" name="lesson_id" value="{{ $selectedLesson->id }}">@endif
                <label for="activity-child">View for child</label>
                <select id="activity-child" name="child_id" onchange="this.form.submit()">@foreach($children as $child)<option value="{{ $child->id }}" @selected($selectedChild->id === $child->id)>{{ $child->name }}</option>@endforeach</select>
            </form>
        @endif
    @endif
    @if ($activity->featured_image_path)
        <img src="{{ route('activities.image', $activity) }}" alt="" style="width:100%; max-height:320px; object-fit:cover; border-radius:12px; margin-bottom:18px;">
    @endif

    @if ($activity->detailed_description)
        <p>{{ $activity->detailed_description }}</p>
    @endif
    <div class="facts">
        <div class="fact"><small>Age</small><strong>{{ $activity->age_group }}</strong></div>
        <div class="fact"><small>Area</small><strong>{{ $activity->development_area }}</strong></div>
        @if ($activity->difficulty)<div class="fact"><small>Difficulty</small><strong>{{ $activity->difficulty }}</strong></div>@endif
        @if ($activity->estimated_minutes)<div class="fact"><small>Time</small><strong>{{ $activity->estimated_minutes }} minutes</strong></div>@endif
    </div>

    @if ($activity->learning_objective)
        <h2>Learning objective</h2>
        <p>{{ $activity->learning_objective }}</p>
    @endif

    @if ($activity->materials)
        <h2>Materials</h2>
        <ul>
            @foreach (preg_split('/\r\n|\r|\n/', $activity->materials) as $item)
                @if (trim($item) !== '')<li>{{ $item }}</li>@endif
            @endforeach
        </ul>
    @endif

    @if ($activity->instructions)
        <h2>Instructions</h2>
        <ol>
            @foreach (preg_split('/\r\n|\r|\n/', $activity->instructions) as $step)
                @if (trim($step) !== '')<li>{{ $step }}</li>@endif
            @endforeach
        </ol>
    @endif

    @foreach ($activity->content_blocks ?? [] as $block)
        @switch($block['type'] ?? '')
            @case('heading') <h2>{{ $block['text'] ?? '' }}</h2> @break
            @case('paragraph') <p>{{ $block['text'] ?? '' }}</p> @break
            @case('image') <img src="{{ $block['url'] ?? '' }}" alt="{{ $block['title'] ?? '' }}" style="max-width:100%; border-radius:12px;"> @break
            @case('tips')
                <div class="card"><h2>Parent guidance</h2><p>{{ $block['text'] ?? '' }}</p></div>
                @break
            @case('materials')
                <h2>{{ $block['title'] ?? 'Materials' }}</h2><ul>@foreach ($block['items'] ?? [] as $item)<li>{{ $item }}</li>@endforeach</ul>
                @break
            @case('steps')
                <h2>{{ $block['title'] ?? 'Steps' }}</h2><ol>@foreach ($block['items'] ?? [] as $item)<li>{{ $item }}</li>@endforeach</ol>
                @break
            @case('matching')
                <div class="card"><h2>{{ $block['title'] ?? 'Matching activity' }}</h2><p>{{ $block['text'] ?? '' }}</p></div>
                @break
            @case('routine')
                <div class="card"><h2>{{ $block['title'] ?? 'Routine card' }}</h2><p>{{ $block['text'] ?? '' }}</p></div>
                @break
            @case('video')
                <h2>{{ $block['title'] ?? 'Educational video' }}</h2><div style="aspect-ratio:16/9; max-width:760px;"><iframe src="{{ $block['url'] ?? '' }}" title="Educational video" style="width:100%;height:100%;border:0;border-radius:12px;" allowfullscreen loading="lazy"></iframe></div>
                @break
            @case('presentation')
                <section class="native-presentation" data-presentation>
                    <h2>Learning presentation</h2>
                    <div class="presentation-slide" data-slide></div>
                    <div class="btn-row">
                        <button class="btn btn-outline" type="button" data-previous>Previous</button>
                        <span class="hint" data-counter></span>
                        <button class="btn btn-blue" type="button" data-next>Next</button>
                        <button class="btn btn-outline" type="button" data-fullscreen>Full screen</button>
                        <button class="btn btn-outline" type="button" data-replay>Replay animation</button>
                    </div>
                    <label style="font-weight:400;"><input type="checkbox" data-reduced-motion> Reduce motion</label>
                    <script type="application/json" data-slides>@json($block['slides'] ?? [])</script>
                </section>
                @break
        @endswitch
    @endforeach

    @if ($activity->lessons->isEmpty())
    @foreach ($activity->sections as $section)
        @php($sectionState = $sectionStates->get($section->id))
        @php($sectionComplete = ($sectionState?->status ?? '') === 'completed')
        <section id="section-{{ $section->id }}" class="activity-section" style="--section-color: {{ $section->color }}" @if($isTracking) data-track-section data-track-url="{{ route('dashboard.progress.section.track', [$activity, $section]) }}" data-child-id="{{ $selectedChild->id }}" data-content-type="{{ $section->content_type }}" data-completed="{{ $sectionComplete ? '1' : '0' }}" data-watched-percent="{{ $sectionState?->watched_percent ?? 0 }}" data-video-threshold-reached="{{ in_array($section->content_type, ['video', 'youtube'], true) && ($sectionState?->watched_percent ?? 0) >= 75 ? '1' : '0' }}" @endif>
            <div class="activity-section-bar">
                @if ($section->icon_path)
                    <img src="{{ route('activities.section-icon', [$activity, $section]) }}" alt="">
                @endif
                @if ($section->title)
                    <div class="section-heading"><strong>{{ $section->title }}</strong></div>
                @endif
            </div>
            <div>
                @if (($section->content['text'] ?? '') !== '')<p>{{ $section->content['text'] }}</p>@endif
                @if (in_array($section->content_type, ['steps', 'materials']))
                    <ol>@foreach ($section->content['items'] ?? [] as $item)<li>{{ $item }}</li>@endforeach</ol>
                @elseif ($section->content_type === 'image' && ($section->content['url'] ?? ''))
                    <img src="{{ $section->content['url'] }}" alt="" style="max-width:100%">
                @elseif ($section->content_type === 'video' && ($section->content['url'] ?? ''))
                    <div class="video-frame"><iframe data-youtube-completion src="{{ $section->content['url'] }}?enablejsapi=1&amp;origin={{ urlencode(request()->getSchemeAndHttpHost()) }}" title="{{ $section->title ?: 'Activity video' }}" allowfullscreen></iframe></div>
                @endif
            </div>
            @if ($isTracking)
                <div class="section-completion-panel">@if(in_array($section->content_type, ['video', 'youtube'], true))<div class="progress-track section-video-progress-track" data-section-video-track role="progressbar" aria-label="Video watched" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $sectionState?->watched_percent ?? 0 }}"><span style="width:{{ $sectionState?->watched_percent ?? 0 }}%"></span></div>@endif<span class="section-progress-status" data-section-progress-status role="status">{{ $sectionComplete ? 'Completed' : (in_array($section->content_type, ['video', 'youtube'], true) ? 'Video watched: '.($sectionState?->watched_percent ?? 0).'% · 75% needed' : 'Not completed') }}</span><form method="POST" action="{{ route('dashboard.progress.section', [$activity, $section]) }}">@csrf<input type="hidden" name="child_id" value="{{ $selectedChild->id }}"><input type="hidden" name="status" value="{{ $sectionComplete ? 'not_started' : 'completed' }}"><button class="btn btn-outline btn-small" type="submit" @disabled(in_array($section->content_type, ['video', 'youtube'], true) && ! $sectionComplete && ($sectionState?->watched_percent ?? 0) < 75)>{{ $sectionComplete ? 'Reset section progress' : 'Mark section complete' }}</button></form></div>
            @endif
        </section>
    @endforeach
    @endif

    @if ($activity->lessons->isNotEmpty() && ! $selectedLesson)
        <section class="lesson-picker">
            <div class="lesson-picker-heading"><span class="eyebrow">Choose a lesson</span><h2>Where would you like to begin?</h2><p>Select one lesson to view its saved sections.</p></div>
            <div class="lesson-card-grid">
                @foreach ($activity->lessons as $lesson)
                    @php
                        $lessonSectionCount = $lesson->sections->count();
                        $lessonCompleted = $lesson->sections->filter(fn ($section) => ($sectionStates->get($section->id)?->status ?? '') === 'completed')->count();
                        $lessonExplicitStatus = $lessonStates->get($lesson->id)?->status ?? 'not_started';
                        $lessonStatus = $lessonSectionCount ? ($lessonCompleted === $lessonSectionCount ? 'completed' : ($lessonCompleted ? 'in_progress' : $lessonExplicitStatus)) : $lessonExplicitStatus;
                        $lessonPercent = $lessonSectionCount ? (int) round(($lessonCompleted / $lessonSectionCount) * 100) : ($lessonStatus === 'completed' ? 100 : 0);
                    @endphp
                    <article class="lesson-card"><span class="eyebrow">Lesson {{ $lesson->position }}</span><h3>{{ $lesson->title }}</h3><p>{{ $lessonCompleted }} of {{ $lessonSectionCount }} sections viewed or completed · {{ $lessonPercent }}%</p><p class="hint">{{ ucfirst(str_replace('_', ' ', $lessonStatus)) }}</p><a class="btn btn-blue btn-small" href="{{ route('activities.show', [$activity, 'lesson_id' => $lesson->id, 'child_id' => $selectedChild?->id]) }}">Open lesson</a></article>
                @endforeach
            </div>
        </section>
    @elseif ($selectedLesson)
        <nav class="lesson-switcher" aria-label="Choose another lesson">@foreach($activity->lessons as $lesson)<a class="{{ $lesson->is($selectedLesson) ? 'active' : '' }}" href="{{ route('activities.show', [$activity, 'lesson_id' => $lesson->id, 'child_id' => $selectedChild?->id]) }}">Lesson {{ $lesson->position }}: {{ $lesson->title }}</a>@endforeach</nav>
        <section class="lesson-content selected-lesson-content">
            <header class="lesson-heading"><span class="eyebrow">Lesson {{ $selectedLesson->position }}</span><h2>{{ $selectedLesson->title }}</h2></header>
            @if ($selectedLesson->description)<p>{{ $selectedLesson->description }}</p>@endif
            @if ($isTracking)
                @php($selectedLessonSectionCount = $selectedLesson->sections->count())
                @php($selectedLessonSectionsComplete = $selectedLesson->sections->filter(fn ($section) => ($sectionStates->get($section->id)?->status ?? '') === 'completed')->count())
                @php($selectedLessonStatus = $selectedLessonSectionCount && $selectedLessonSectionsComplete === $selectedLessonSectionCount ? 'completed' : ($lessonStates->get($selectedLesson->id)?->status ?? 'not_started'))
                @php($lessonVideoSections = $selectedLesson->sections->filter(fn ($section) => in_array($section->content_type, ['video', 'youtube'], true)))
                @php($lessonVideosReady = $lessonVideoSections->every(fn ($section) => (int) ($sectionStates->get($section->id)?->watched_percent ?? 0) >= 75))
                <div class="lesson-completion-panel">
                    <span class="hint" data-lesson-completion-hint>{{ $selectedLessonStatus === 'completed' ? 'Lesson completed for '.$selectedChild->name : ($lessonVideoSections->isNotEmpty() && ! $lessonVideosReady ? 'Watch at least 75% of each lesson video to unlock completion.' : 'Track this lesson for '.$selectedChild->name) }}</span>
                    @if ($selectedLessonStatus !== 'completed')
                        <form method="POST" action="{{ route('dashboard.progress.lesson.complete', [$activity, $selectedLesson]) }}" data-lesson-completion-form>
                            @csrf
                            <input type="hidden" name="child_id" value="{{ $selectedChild->id }}">
                            <input type="hidden" name="lesson_id" value="{{ $selectedLesson->id }}">
                            <button class="btn btn-blue btn-small" type="submit" @disabled(! $lessonVideosReady)>Mark lesson complete</button>
                        </form>
                    @else
                        <span class="completion-badge">Lesson complete</span>
                    @endif
                </div>
            @endif
            @foreach ($selectedLesson->sections as $section)
                @php($content = $section->content ?? [])
                @php($sectionState = $sectionStates->get($section->id))
                @php($sectionComplete = ($sectionState?->status ?? '') === 'completed')
                <article id="section-{{ $section->id }}" class="activity-section" style="--section-color: {{ $section->color }}" @if($isTracking) data-track-section data-track-url="{{ route('dashboard.progress.section.track', [$activity, $section]) }}" data-child-id="{{ $selectedChild->id }}" data-content-type="{{ $section->content_type }}" data-completed="{{ $sectionComplete ? '1' : '0' }}" data-watched-percent="{{ $sectionState?->watched_percent ?? 0 }}" data-video-threshold-reached="{{ in_array($section->content_type, ['video', 'youtube'], true) && ($sectionState?->watched_percent ?? 0) >= 75 ? '1' : '0' }}" @endif>
                    <div class="activity-section-bar">
                        @if ($section->icon_path)<img src="{{ route('activities.section-icon', [$activity, $section]) }}" alt="">@endif
                        @if ($section->title)<div class="section-heading"><strong>{{ $section->title }}</strong></div>@endif
                    </div>
                    <div class="activity-section-body">
                        @if ($section->content_type === 'heading')
                            @php($font = in_array($content['font_family'] ?? '', ['Arial', 'Georgia', 'Tahoma', 'Times New Roman', 'Trebuchet MS', 'Verdana'], true) ? $content['font_family'] : 'Arial')
                            @php($fontSize = max(20, min(48, (int) ($content['font_size'] ?? 32))) )
                            @php($fontColor = preg_match('/^#[0-9A-Fa-f]{6}$/', $content['font_color'] ?? '') ? $content['font_color'] : '#23384D')
                            @php($alignment = in_array($content['text_align'] ?? '', ['left', 'center', 'right'], true) ? $content['text_align'] : 'left')
                            <h3 class="activity-section-heading" style="font-family:{{ $font }};font-size:{{ $fontSize }}px;color:{{ $fontColor }};text-align:{{ $alignment }};font-weight:{{ !empty($content['bold']) ? '700' : '400' }};font-style:{{ !empty($content['italic']) ? 'italic' : 'normal' }};text-decoration:{{ !empty($content['underline']) ? 'underline' : 'none' }}">{{ $content['text'] ?? $section->title }}</h3>
                        @elseif (in_array($section->content_type, ['text', 'paragraph'], true))
                            @if (($content['html'] ?? '') !== '')<div class="rich-content">{!! \App\Support\RichTextSanitizer::sanitize($content['html']) !!}</div>@elseif(($content['text'] ?? '') !== '')<p>{{ $content['text'] }}</p>@endif
                        @elseif ($section->content_type === 'image')
                            @if ($content['media_path'] ?? null)<img class="activity-section-image" src="{{ route('activities.section-media', [$activity, $section]) }}" alt="{{ $content['alt'] ?? $section->title }}">@elseif($content['url'] ?? null)<img class="activity-section-image" src="{{ $content['url'] }}" alt="{{ $content['alt'] ?? $section->title }}">@endif
                        @elseif ($section->content_type === 'video' && ($content['media_path'] ?? null))
                            <video class="activity-section-video" data-completion-video controls playsinline preload="metadata"><source src="{{ route('activities.section-media', [$activity, $section]) }}">Your browser could not play this video. Try another browser or device.</video>
                        @elseif ($section->content_type === 'youtube' && ($content['url'] ?? null))
                            <div class="video-frame"><iframe data-youtube-completion src="{{ $content['url'] }}?enablejsapi=1&amp;origin={{ urlencode(request()->getSchemeAndHttpHost()) }}" title="{{ $section->title ?: 'YouTube video' }}" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe></div>
                        @elseif ($section->content_type === 'powerpoint' && ($content['media_path'] ?? null))
                            <div class="lesson-pptx-viewer" data-pptx-shell data-autoplay-delay="5000" data-pptx-source="{{ route('activities.section-media', [$activity, $section]) }}">
                                <div class="pptx-stage" data-pptx-stage></div><div class="pptx-controls"><button class="btn btn-outline btn-small" type="button" data-pptx-previous disabled>Previous</button><span class="hint" data-pptx-counter aria-live="polite">Loading slides</span><button class="btn btn-outline btn-small" type="button" data-pptx-next disabled>Next</button><button class="btn btn-outline btn-small" type="button" data-pptx-play aria-pressed="false">Play slideshow</button><button class="btn btn-outline btn-small" type="button" data-pptx-fullscreen>Full screen</button></div><p class="pptx-status" data-pptx-status role="status" aria-live="polite">Loading presentation…</p>
                            </div>
                        @elseif ($section->content_type === 'pdf' && ($content['media_path'] ?? null))
                            <div class="activity-section-document"><iframe src="{{ route('activities.section-media', [$activity, $section]) }}" title="{{ $section->title ?: 'PDF document' }}" loading="lazy"></iframe><a class="btn btn-outline btn-small" href="{{ route('activities.section-media', [$activity, $section]) }}" target="_blank" rel="noopener">Open PDF in a new tab</a></div>
                        @elseif ($section->content_type === 'word' && ($content['media_path'] ?? null))
                            <a class="btn btn-outline" data-content-open href="{{ route('activities.section-media', [$activity, $section]) }}">Download {{ $content['media_filename'] ?? 'Word document' }}</a>
                        @elseif ($section->content_type === 'presentation' && !empty($content['slides']))
                            <section class="native-presentation" data-presentation><h4>{{ $section->title }}</h4><div class="presentation-slide" data-slide></div><div class="btn-row"><button class="btn btn-outline" type="button" data-previous>Previous</button><span class="hint" data-counter></span><button class="btn btn-blue" type="button" data-next>Next</button></div><script type="application/json" data-slides>@json($content['slides'])</script></section>
                        @elseif (in_array($section->content_type, ['steps', 'materials'], true) && !empty($content['items']))
                            <ol>@foreach($content['items'] as $item)<li>{{ $item }}</li>@endforeach</ol>
                        @elseif ($section->content_type === 'external_link' && ($content['url'] ?? null) && in_array(parse_url($content['url'], PHP_URL_SCHEME), ['http', 'https'], true))
                            <a href="{{ $content['url'] }}" target="_blank" rel="noopener noreferrer">{{ $section->title }}</a>
                        @elseif (($content['text'] ?? '') !== '')
                            <p>{{ $content['text'] }}</p>
                        @endif
                    </div>
                    @if ($isTracking)
                        <div class="section-completion-panel">
                            @if(in_array($section->content_type, ['video', 'youtube'], true))<div class="progress-track section-video-progress-track" data-section-video-track role="progressbar" aria-label="Video watched" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $sectionState?->watched_percent ?? 0 }}"><span style="width:{{ $sectionState?->watched_percent ?? 0 }}%"></span></div>@endif
                            <span class="section-progress-status" data-section-progress-status role="status">{{ $sectionComplete ? 'Completed'.($sectionState?->completion_method ? ' · '.str_replace('_', ' ', $sectionState->completion_method) : '') : (in_array($section->content_type, ['video', 'youtube'], true) ? 'Video watched: '.($sectionState?->watched_percent ?? 0).'% · 75% needed' : 'Not completed') }}</span>
                            <form method="POST" action="{{ route('dashboard.progress.section', [$activity, $section]) }}">
                                @csrf
                                <input type="hidden" name="child_id" value="{{ $selectedChild->id }}">
                                <input type="hidden" name="status" value="{{ $sectionComplete ? 'not_started' : 'completed' }}">
                                <button class="btn btn-outline btn-small" type="submit" @disabled(in_array($section->content_type, ['video', 'youtube'], true) && ! $sectionComplete && ($sectionState?->watched_percent ?? 0) < 75)>{{ $sectionComplete ? 'Reset section progress' : 'Mark section complete' }}</button>
                            </form>
                        </div>
                    @endif
                </article>
            @endforeach
        </section>
    @endif

    @if ($selectedLesson && $selectedLesson->sections->contains(fn ($section) => $section->content_type === 'powerpoint' && !empty($section->content['media_path'])))
        <script type="module" src="{{ asset('assets/js/pptx-viewer.bundle.js') }}"></script>
    @endif

    @if (collect($activity->content_blocks ?? [])->contains('type', 'presentation'))
        <div class="btn-row">
            <a class="btn btn-blue" href="{{ route('activities.slideshow', $activity) }}" target="_blank" rel="noopener">Open slideshow in a new tab</a>
        </div>
    @endif

    @if ($activity->parent_tips)
        <div class="card activity-parent-tips"><h2>Tips for parents</h2><p>{{ $activity->parent_tips }}</p></div>
    @endif
    @if ($activity->video_path)
        <section class="activity-video-section" data-activity-video-shell><h2>Watch this activity</h2><div class="activity-video-block"><video controls playsinline preload="metadata" @if($isTracking) data-activity-video data-progress-url="{{ route('dashboard.progress.video', $activity) }}" data-child-id="{{ $selectedChild->id }}" data-watched-percent="{{ $activityVideoProgressPercent }}" @endif><source src="{{ route('activities.video', $activity) }}">Your browser cannot play this video.</video></div>@if($isTracking)<div class="progress-track activity-video-progress-track" data-activity-video-track role="progressbar" aria-label="Video watched" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $activityVideoProgressPercent }}"><span style="width:{{ $activityVideoProgressPercent }}%"></span></div><p class="activity-video-progress" data-activity-video-status role="status">Video watched: {{ $activityVideoProgressPercent }}%. Completion unlocks at 75%.</p>@elseif($selectedChild)<p class="hint">Track progress for this child to save video watch progress.</p>@endif</section>
    @endif
    @if ($activity->youtube_url)
        <section class="activity-video-section" data-activity-video-shell><h2>Watch this activity</h2><div class="activity-video-block"><iframe @if($isTracking) data-activity-youtube data-progress-url="{{ route('dashboard.progress.video', $activity) }}" data-child-id="{{ $selectedChild->id }}" data-watched-percent="{{ $activityVideoProgressPercent }}" @endif src="{{ $activity->youtube_url }}{{ str_contains($activity->youtube_url, '?') ? '&' : '?' }}enablejsapi=1&amp;origin={{ urlencode(request()->getSchemeAndHttpHost()) }}" title="Educational video" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen loading="lazy"></iframe></div>@if($isTracking)<div class="progress-track activity-video-progress-track" data-activity-video-track role="progressbar" aria-label="Video watched" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $activityVideoProgressPercent }}"><span style="width:{{ $activityVideoProgressPercent }}%"></span></div><p class="activity-video-progress" data-activity-video-status role="status">Video watched: {{ $activityVideoProgressPercent }}%. Completion unlocks at 75%.</p>@elseif($selectedChild)<p class="hint">Track progress for this child to save video watch progress.</p>@endif</section>
    @endif

    <div class="btn-row">
        @auth
            @if (auth()->user()->hasVerifiedEmail() && auth()->user()->hasRole('parent', 'admin'))
                @if ($activity->pptx_path)<a class="btn btn-outline" href="{{ route('activities.download', [$activity, 'pptx']) }}">Download PowerPoint</a>@endif
                @if ($activity->pdf_path)<a class="btn btn-outline" href="{{ route('activities.download', [$activity, 'pdf']) }}">Download worksheet</a>@endif
            @endif
        @else
            @if ($activity->pptx_path || $activity->pdf_path)<a class="btn btn-outline" href="{{ route('login') }}">Log in to download resources</a>@endif
        @endauth
    </div>

    @if ($activity->disclaimer)
        <div class="disclaimer">{{ $activity->disclaimer }}</div>
    @endif
    <p class="hint">Source: {{ $activity->content_source ?: 'Little Steps educational content' }}. Reviewer: {{ $activity->reviewer_name ?: 'Not specified' }}.</p>
</article>

<script>
(function () {
    var csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    var sections = Array.from(document.querySelectorAll('[data-track-section]'));
    if (!csrf) return;
    var activityCompleteForm = document.querySelector('[data-activity-completion-form]');

    function refreshActivityCompletionLock() {
        if (!activityCompleteForm) return;
        var activityVideoReady = activityCompleteForm.dataset.activityVideoRequired !== '1' || activityCompleteForm.dataset.activityVideoUnlocked === '1';
        var sectionVideosReady = Number(activityCompleteForm.dataset.readyVideoCount || 0) >= Number(activityCompleteForm.dataset.requiredVideoCount || 0);
        var unlocked = activityVideoReady && sectionVideosReady;
        var button = activityCompleteForm.querySelector('button[type="submit"]');
        var hint = activityCompleteForm.querySelector('[data-video-completion-hint]');
        if (button) button.disabled = !unlocked;
        if (hint) hint.textContent = unlocked ? 'Finished this activity together?' : 'Watch at least 75% of each activity video to unlock completion.';
    }

    function setActivityWatchProgress(percent) {
        var video = document.querySelector('[data-activity-video], [data-activity-youtube]');
        if (!video || !video.dataset.progressUrl || video.dataset.progressPending === '1') return;
        var previous = Number(video.dataset.watchedPercent || 0);
        percent = Math.max(previous, Math.min(99, Math.floor(percent)));
        var thresholdJustReached = percent >= 75 && previous < 75;
        if (percent <= previous || (!thresholdJustReached && percent < previous + 4)) return;
        video.dataset.progressPending = '1';
        fetch(video.dataset.progressUrl, {method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrf}, body:JSON.stringify({child_id:Number(video.dataset.childId), watched_percent:percent})})
            .then(function (response) { if (!response.ok) throw new Error(); return response.json(); })
            .then(function (data) {
                video.dataset.watchedPercent = String(data.watched_percent);
                var status = document.querySelector('[data-activity-video-status]');
                if (status) status.textContent = 'Video watched: ' + percent + '%. ' + (data.completion_unlocked ? 'Activity completion is unlocked.' : 'Completion unlocks at 75%.');
                var videoTrack = document.querySelector('[data-activity-video-track]');
                if (videoTrack) { videoTrack.setAttribute('aria-valuenow', percent); var videoFill = videoTrack.querySelector('span'); if (videoFill) videoFill.style.width = percent + '%'; }
                if (activityCompleteForm && data.completion_unlocked) {
                    activityCompleteForm.dataset.activityVideoUnlocked = '1';
                    refreshActivityCompletionLock();
                }
                var track = document.querySelector('[data-activity-progress-track]');
                if (track) {
                    track.setAttribute('aria-valuenow', data.progress_percent);
                    var fill = track.querySelector('span');
                    if (fill) fill.style.width = data.progress_percent + '%';
                }
                var progressText = document.querySelector('[data-activity-progress-text]');
                if (progressText) progressText.textContent = data.progress_percent + '% complete';
            }).catch(function () {
                var status = document.querySelector('[data-activity-video-status]');
                if (status) status.textContent = 'Watch progress could not be saved yet. Keep watching and it will retry.';
            }).finally(function () { delete video.dataset.progressPending; });
    }

    function playedPercent(video) {
        if (!Number.isFinite(video.duration) || video.duration <= 0) return 0;
        var seconds = 0;
        for (var i = 0; i < video.played.length; i++) seconds += video.played.end(i) - video.played.start(i);
        return Math.min(100, Math.floor((seconds / video.duration) * 100));
    }

    function saveSectionVideoProgress(section, percent) {
        if (!section || section.dataset.completed === '1' || section.dataset.videoProgressPending === '1') return;
        var previous = Number(section.dataset.watchedPercent || 0);
        percent = Math.max(previous, Math.min(100, Math.floor(percent)));
        if (percent < previous + 4 && percent < 75) return;
        section.dataset.videoProgressPending = '1';
        fetch(section.dataset.trackUrl, {method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrf}, body:JSON.stringify({child_id:Number(section.dataset.childId), method:'video_progress', watched_percent:percent})})
            .then(function (response) { if (!response.ok) throw new Error(); return response.json(); })
            .then(function (data) {
                section.dataset.watchedPercent = String(data.watched_percent);
                var status = section.querySelector('[data-section-progress-status]');
                if (status) status.textContent = data.watched_percent >= 75 ? 'Video watched: ' + data.watched_percent + '% · Section complete' : 'Video watched: ' + data.watched_percent + '% · 75% needed';
                var videoTrack = section.querySelector('[data-section-video-track]');
                if (videoTrack) { videoTrack.setAttribute('aria-valuenow', data.watched_percent); var videoFill = videoTrack.querySelector('span'); if (videoFill) videoFill.style.width = data.watched_percent + '%'; }
                var button = section.querySelector('.section-completion-panel button');
                if (button) {
                    button.disabled = data.watched_percent < 75;
                    if (data.watched_percent >= 75) button.textContent = 'Reset section progress';
                }
                var state = section.querySelector('.section-completion-panel [name="status"]');
                if (state && data.watched_percent >= 75) state.value = 'not_started';
                if (data.watched_percent >= 75) {
                    section.dataset.completed = '1';
                    if (section.dataset.videoThresholdReached !== '1') {
                        section.dataset.videoThresholdReached = '1';
                        if (activityCompleteForm) activityCompleteForm.dataset.readyVideoCount = String(Number(activityCompleteForm.dataset.readyVideoCount || 0) + 1);
                    }
                    refreshActivityCompletionLock();
                    var lessonContent = section.closest('.selected-lesson-content');
                    var lessonVideos = lessonContent ? Array.from(lessonContent.querySelectorAll('[data-track-section][data-content-type="video"], [data-track-section][data-content-type="youtube"]')) : [];
                    var lessonReady = lessonVideos.every(function (item) { return Number(item.dataset.watchedPercent || 0) >= 75; });
                    var lessonButton = lessonContent && lessonContent.querySelector('[data-lesson-completion-form] button');
                    var lessonHint = lessonContent && lessonContent.querySelector('[data-lesson-completion-hint]');
                    if (lessonButton && lessonReady) lessonButton.disabled = false;
                    if (lessonHint && lessonReady) lessonHint.textContent = 'Video requirements met. You can now mark this lesson complete.';
                }
            }).catch(function () {
                var status = section.querySelector('[data-section-progress-status]');
                if (status) status.textContent = 'Watch progress could not be saved yet. Keep watching and it will retry.';
            }).finally(function () { delete section.dataset.videoProgressPending; });
    }

    function completeSection(section, method) {
        if (section.dataset.completed === '1' || section.dataset.completionPending === '1') return Promise.resolve();
        section.dataset.completionPending = '1';
        return fetch(section.dataset.trackUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf},
            body: JSON.stringify({child_id: Number(section.dataset.childId), method: method})
        }).then(function (response) {
            if (!response.ok) throw new Error('Progress could not be saved.');
            section.dataset.completed = '1';
            if (viewableSections) viewableSections.delete(section);
            var status = section.querySelector('[data-section-progress-status]');
            if (status) status.textContent = method === 'video_threshold' ? 'Video watched to 75% · Section complete' : 'Content viewed';
            var form = section.querySelector('.section-completion-panel form');
            if (form) {
                form.querySelector('[name="status"]').value = 'not_started';
                form.querySelector('button').textContent = 'Reset section progress';
            }
        }).catch(function () {
            var status = section.querySelector('[data-section-progress-status]');
            if (status) status.textContent = 'Progress could not be saved. Use the button to retry.';
        }).finally(function () {
            delete section.dataset.completionPending;
        });
    }

    sections.forEach(function (section) {
        var video = section.querySelector('[data-completion-video]');
        if (video && section.dataset.completed !== '1') {
            var sessionBasePercent = Number(section.dataset.watchedPercent || 0);
            video.addEventListener('timeupdate', function () { saveSectionVideoProgress(section, sessionBasePercent + playedPercent(video)); });
        }
        if (section.dataset.completed === '1') return;
        section.querySelectorAll('[data-content-open]').forEach(function (link) {
            link.addEventListener('click', function () { completeSection(section, 'content_viewed'); });
        });
    });

    var viewableSections = new Set();
    if ('IntersectionObserver' in window) {
        var timers = new WeakMap();
        function scheduleView(section) {
            if (section.dataset.completed === '1' || timers.has(section) || document.visibilityState !== 'visible') return;
            timers.set(section, window.setTimeout(function () {
                timers.delete(section);
                if (document.visibilityState === 'visible' && viewableSections.has(section)) completeSection(section, 'content_viewed');
            }, 6000));
        }
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                var section = entry.target;
                if (section.dataset.completed === '1') {
                    observer.unobserve(section);
                    return;
                }
                var type = section.dataset.contentType;
                if (['video', 'youtube', 'word'].includes(type)) return;
                if (entry.isIntersecting && entry.intersectionRatio >= 0.1) {
                    viewableSections.add(section);
                    scheduleView(section);
                } else if (timers.has(section)) {
                    viewableSections.delete(section);
                    window.clearTimeout(timers.get(section));
                    timers.delete(section);
                } else {
                    viewableSections.delete(section);
                }
            });
        }, {threshold: [0.1]});
        sections.forEach(function (section) { if (section.dataset.completed !== '1') observer.observe(section); });
        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'hidden') {
                viewableSections.forEach(function (section) {
                    if (timers.has(section)) window.clearTimeout(timers.get(section));
                    timers.delete(section);
                });
            } else {
                viewableSections.forEach(scheduleView);
            }
        });
    }

    var youtubeFrames = document.querySelectorAll('[data-youtube-completion], [data-activity-youtube]');
    var nativeActivityVideo = document.querySelector('[data-activity-video]');
    if (nativeActivityVideo) {
        var activitySessionBasePercent = Number(nativeActivityVideo.dataset.watchedPercent || 0);
        nativeActivityVideo.addEventListener('timeupdate', function () { setActivityWatchProgress(activitySessionBasePercent + playedPercent(nativeActivityVideo)); });
    }
    if (!youtubeFrames.length) return;
    function attachYoutubePlayers() {
        if (!window.YT || !window.YT.Player) return;
        youtubeFrames.forEach(function (frame) {
            var section = frame.closest('[data-track-section]');
            var isActivityVideo = frame.hasAttribute('data-activity-youtube');
            if ((!section && !isActivityVideo) || (section && section.dataset.completed === '1') || frame.dataset.playerReady) return;
            frame.dataset.playerReady = '1';
            var duration = 0, watchedSeconds = (Number(frame.dataset.watchedPercent || section?.dataset.watchedPercent || 0) / 100) * 100, lastTime = null, timer = null;
            function report() {
                if (duration <= 0) return;
                var percent = Math.min(100, Math.floor((watchedSeconds / duration) * 100));
                if (isActivityVideo) setActivityWatchProgress(percent); else saveSectionVideoProgress(section, percent);
            }
            new window.YT.Player(frame, {events: {
                onReady: function (event) { try { duration = event.target.getDuration(); watchedSeconds = (Number(frame.dataset.watchedPercent || section?.dataset.watchedPercent || 0) / 100) * duration; } catch (error) {} },
                onStateChange: function (event) {
                    if (event.data === window.YT.PlayerState.PLAYING) {
                        try { lastTime = event.target.getCurrentTime(); duration = event.target.getDuration() || duration; } catch (error) { lastTime = null; }
                        if (!timer) timer = window.setInterval(function () {
                            try { var now = event.target.getCurrentTime(); if (lastTime !== null && now > lastTime && now - lastTime <= 2.5) watchedSeconds += now - lastTime; lastTime = now; report(); } catch (error) {}
                        }, 1000);
                    } else {
                        if (timer) window.clearInterval(timer);
                        timer = null; lastTime = null;
                    }
                }
            }});
        });
    }
    if (window.YT && window.YT.Player) {
        attachYoutubePlayers();
    } else {
        window.onYouTubeIframeAPIReady = attachYoutubePlayers;
        var api = document.createElement('script');
        api.src = 'https://www.youtube.com/iframe_api';
        api.async = true;
        document.head.appendChild(api);
    }
})();

document.querySelectorAll('[data-presentation]').forEach(function (presentation) {
    var slides = JSON.parse(presentation.querySelector('[data-slides]').textContent || '[]');
    var current = 0;
    var slide = presentation.querySelector('[data-slide]');
    var counter = presentation.querySelector('[data-counter]');
    var reduced = presentation.querySelector('[data-reduced-motion]');
    function render() {
        var item = slides[current] || {};
        slide.innerHTML = '<h3>' + escapePresentation(item.title || '') + '</h3><p>' + escapePresentation(item.text || '') + '</p>';
        if (item.image_url) slide.innerHTML += '<img src="' + escapePresentation(item.image_url) + '" alt="" style="max-width:100%;border-radius:12px;">';
        if (Array.isArray(item.steps)) slide.innerHTML += '<ol>' + item.steps.map(function (step) { return '<li>' + escapePresentation(step) + '</li>'; }).join('') + '</ol>';
        counter.textContent = slides.length ? (current + 1) + ' of ' + slides.length : 'No slides';
        slide.classList.remove('presentation-replay');
        if (!reduced || !reduced.checked) void slide.offsetWidth, slide.classList.add('presentation-replay');
    }
    function escapePresentation(value) { var div = document.createElement('div'); div.textContent = value; return div.innerHTML; }
    presentation.querySelector('[data-previous]').onclick = function () { if (slides.length) { current = (current - 1 + slides.length) % slides.length; render(); } };
    presentation.querySelector('[data-next]').onclick = function () { if (slides.length) { current = (current + 1) % slides.length; render(); } };
    var replay = presentation.querySelector('[data-replay]');
    var fullscreen = presentation.querySelector('[data-fullscreen]');
    if (replay) replay.onclick = render;
    if (fullscreen) fullscreen.onclick = function () { if (presentation.requestFullscreen) presentation.requestFullscreen(); };
    if (reduced) reduced.onchange = render;
    render();
});
</script>
@endsection
