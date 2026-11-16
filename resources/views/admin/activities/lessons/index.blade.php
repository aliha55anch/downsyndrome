@extends('layouts.admin')

@section('title', 'Manage lessons')

@section('content')
<div class="page-title admin-dashboard-heading admin-lesson-page-heading">
    <div><span class="eyebrow">Activity editor</span><h1>Manage lessons</h1><p>{{ $activity->title }} · Create and order lessons manually. Each lesson starts empty until you add sections.</p></div>
    <a class="btn btn-blue" href="{{ route('admin.activities.lessons.create', $activity) }}"><span aria-hidden="true">+</span> Add lesson</a>
</div>

<div class="admin-lesson-toolbar"><a class="btn btn-outline" href="{{ route('admin.activities.edit', $activity) }}">Activity information</a><a class="btn btn-outline" href="{{ route('admin.activities.preview', $activity) }}" target="_blank" rel="noopener">Preview activity</a></div>

<section class="editor-publish-controls admin-lesson-publish-controls">
    <div><span class="eyebrow">Activity status: {{ ucfirst($activity->status) }}</span><p class="hint">{{ $activity->isPublished() ? 'Families can view this activity.' : 'This activity is visible to admins only until published.' }}</p></div>
    <form method="POST" action="{{ route('admin.activities.publication', $activity) }}">@csrf<input type="hidden" name="status" value="{{ $activity->isPublished() ? 'draft' : 'published' }}"><button class="btn {{ $activity->isPublished() ? 'btn-outline' : 'btn-blue' }}" type="submit">{{ $activity->isPublished() ? 'Unpublish activity' : 'Publish activity' }}</button></form>
</section>

<section class="admin-panel admin-lesson-list-panel">
    <div class="panel-heading"><div><span class="eyebrow">Lesson structure</span><h2>{{ $activity->lessons->count() }} {{ \Illuminate\Support\Str::plural('lesson', $activity->lessons->count()) }}</h2><p>Drag a lesson to change its order. Open a lesson to add and arrange sections.</p></div></div>
    <div class="admin-lesson-list" data-sortable-lessons data-reorder-url="{{ route('admin.activities.lessons.reorder', $activity) }}" data-csrf="{{ csrf_token() }}">
        @forelse($activity->lessons as $lesson)
            <article class="lesson-admin-card admin-lesson-card" draggable="true" data-sort-id="{{ $lesson->id }}">
                <div class="admin-lesson-card-main">
                    <div class="admin-lesson-card-meta"><span class="admin-lesson-position">Lesson {{ $lesson->position }}</span><span>{{ $lesson->sections->count() }} {{ \Illuminate\Support\Str::plural('section', $lesson->sections->count()) }}</span><span class="admin-lesson-drag-hint">Drag to reorder</span></div>
                    <h3>{{ $lesson->title }}</h3>
                    <p>{{ $lesson->description ?: 'No description added.' }}</p>
                </div>
                <div class="admin-lesson-card-actions"><a class="btn btn-outline btn-small" href="{{ route('admin.activities.lessons.edit', [$activity, $lesson]) }}">Open lesson editor <span aria-hidden="true">→</span></a><form method="POST" action="{{ route('admin.activities.lessons.destroy', [$activity, $lesson]) }}" onsubmit="return confirm('Delete this lesson and its sections?')">@csrf @method('DELETE')<button class="btn btn-red btn-small" type="submit">Delete</button></form></div>
            </article>
        @empty
            <div class="empty-state"><h2>No lessons have been added yet.</h2><p>Start with an empty activity and add lessons when you are ready to set the structure.</p><a class="btn btn-blue" href="{{ route('admin.activities.lessons.create', $activity) }}">Add your first lesson</a></div>
        @endforelse
    </div>
</section>

<script>
(function () {
    var list = document.querySelector('[data-sortable-lessons]');
    if (!list) return;
    var dragged = null;
    list.querySelectorAll('[data-sort-id]').forEach(function (row) {
        row.addEventListener('dragstart', function () { dragged = row; row.classList.add('is-dragging'); });
        row.addEventListener('dragend', function () {
            row.classList.remove('is-dragging');
            if (!dragged) return;
            var order = Array.from(list.querySelectorAll('[data-sort-id]')).map(function (item) { return item.dataset.sortId; });
            fetch(list.dataset.reorderUrl, {method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':list.dataset.csrf,'Accept':'application/json'}, body:JSON.stringify({order:order})})
                .then(function (response) { if (!response.ok) throw new Error(); window.location.reload(); })
                .catch(function () { window.location.reload(); });
            dragged = null;
        });
        row.addEventListener('dragover', function (event) {
            event.preventDefault();
            if (!dragged || dragged === row) return;
            var bounds = row.getBoundingClientRect();
            list.insertBefore(dragged, event.clientY < bounds.top + bounds.height / 2 ? row : row.nextSibling);
        });
    });
})();
</script>
@endsection
