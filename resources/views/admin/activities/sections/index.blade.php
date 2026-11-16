@extends('layouts.admin')

@section('title', 'Manage lesson sections')

@section('content')
@php($inLesson = isset($lesson) && $lesson)
<div class="page-title admin-dashboard-heading admin-lesson-page-heading">
    <div><span class="eyebrow">{{ $inLesson ? $activity->title.' · Lesson '.$lesson->position : $activity->title }}</span><h1>{{ $inLesson ? $lesson->title.' sections' : 'Activity sections' }}</h1><p>Add and order sections individually. Each section stays empty until you choose its content.</p></div>
    @if($inLesson)<a class="btn btn-blue" href="{{ route('admin.activities.lessons.sections.create', [$activity, $lesson]) }}"><span aria-hidden="true">+</span> Add section</a>@else<a class="btn btn-blue" href="{{ route('admin.activities.sections.create', $activity) }}"><span aria-hidden="true">+</span> Add section</a>@endif
</div>

<div class="admin-lesson-toolbar">@if($inLesson)<a class="btn btn-outline" href="{{ route('admin.activities.lessons.index', $activity) }}">Back to lessons</a><a class="btn btn-outline" href="{{ route('admin.activities.lessons.edit', [$activity, $lesson]) }}">Edit lesson details</a>@else<a class="btn btn-outline" href="{{ route('admin.activities.lessons.index', $activity) }}">Manage lessons</a><a class="btn btn-outline" href="{{ route('admin.activities.edit', $activity) }}">Activity information</a>@endif</div>

<section class="admin-panel admin-section-list-panel">
    <div class="panel-heading"><div><span class="eyebrow">Content order</span><h2>{{ ($inLesson ? $lesson->sections : $activity->sections)->count() }} {{ \Illuminate\Support\Str::plural('section', ($inLesson ? $lesson->sections : $activity->sections)->count()) }}</h2><p>{{ $inLesson ? 'Drag sections to change the lesson order.' : 'Sections are managed independently from lessons.' }}</p></div></div>
    <div class="admin-section-list" @if($inLesson) data-sortable-sections data-reorder-url="{{ route('admin.activities.lessons.sections.reorder', [$activity, $lesson]) }}" data-csrf="{{ csrf_token() }}" @endif>
        @forelse(($inLesson ? $lesson->sections : $activity->sections) as $section)
            <article class="admin-section-list-row" @if($inLesson) draggable="true" data-sort-id="{{ $section->id }}" @endif>
                <div class="admin-section-list-main"><span class="admin-lesson-position">Section {{ $section->position }}</span><h3>{{ $section->title ?: 'Untitled section' }}</h3><p>{{ ucfirst(str_replace('_', ' ', $section->content_type)) }}</p></div>
                @if($inLesson)
                    <div class="admin-section-list-actions"><a class="btn btn-outline btn-small" href="{{ route('admin.activities.lessons.sections.create', [$activity, $lesson, 'position' => $section->position]) }}">Add before</a><a class="btn btn-outline btn-small" href="{{ route('admin.activities.lessons.sections.create', [$activity, $lesson, 'position' => $section->position + 1]) }}">Add after</a><a class="btn btn-outline btn-small" href="{{ route('admin.activities.lessons.sections.edit', [$activity, $lesson, $section]) }}">Edit section</a><form method="POST" action="{{ route('admin.activities.lessons.sections.duplicate', [$activity, $lesson, $section]) }}">@csrf<button class="btn btn-outline btn-small" type="submit">Duplicate</button></form><form method="POST" action="{{ route('admin.activities.lessons.sections.destroy', [$activity, $lesson, $section]) }}" onsubmit="return confirm('Delete this section?')">@csrf @method('DELETE')<button class="btn btn-red btn-small" type="submit">Delete</button></form></div>
                @else
                    <div class="admin-section-list-actions"><a class="btn btn-outline btn-small" href="{{ route('admin.activities.sections.edit', [$activity, $section]) }}">Edit section</a><form method="POST" action="{{ route('admin.activities.sections.destroy', [$activity, $section]) }}" onsubmit="return confirm('Delete this section?')">@csrf @method('DELETE')<button class="btn btn-red btn-small" type="submit">Delete</button></form></div>
                @endif
            </article>
        @empty
            <div class="empty-state"><h2>No sections have been added</h2><p>Add a section to begin building the lesson content.</p>@if($inLesson)<a class="btn btn-blue" href="{{ route('admin.activities.lessons.sections.create', [$activity, $lesson]) }}">Add the first section</a>@endif</div>
        @endforelse
    </div>
</section>

@if($inLesson)
<script>
(function () {
    var list = document.querySelector('[data-sortable-sections]');
    if (!list) return;
    var dragged = null;
    list.querySelectorAll('[data-sort-id]').forEach(function (row) {
        row.addEventListener('dragstart', function () { dragged = row; row.classList.add('is-dragging'); });
        row.addEventListener('dragover', function (event) {
            event.preventDefault();
            if (!dragged || dragged === row) return;
            var bounds = row.getBoundingClientRect();
            list.insertBefore(dragged, event.clientY < bounds.top + bounds.height / 2 ? row : row.nextSibling);
        });
        row.addEventListener('dragend', function () {
            row.classList.remove('is-dragging');
            var order = Array.from(list.querySelectorAll('[data-sort-id]')).map(function (item) { return item.dataset.sortId; });
            fetch(list.dataset.reorderUrl, {method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':list.dataset.csrf,'Accept':'application/json'}, body:JSON.stringify({order:order})})
                .then(function (response) { if (!response.ok) throw new Error(); window.location.reload(); })
                .catch(function () { window.location.reload(); });
            dragged = null;
        });
    });
})();
</script>
@endif
@endsection
