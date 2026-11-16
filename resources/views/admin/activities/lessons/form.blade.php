@extends('layouts.admin')

@section('title', $lesson->exists ? 'Edit lesson' : 'Add lesson')

@section('content')
<div class="page-title admin-dashboard-heading admin-lesson-page-heading">
    <div><span class="eyebrow">{{ $activity->title }}</span><h1>{{ $lesson->exists ? 'Edit lesson' : 'Add lesson' }}</h1><p>A lesson starts empty. Add and arrange its content sections after saving.</p></div>
    <a class="btn btn-outline" href="{{ route('admin.activities.lessons.index', $activity) }}">Back to lessons</a>
</div>

<form method="POST" action="{{ $lesson->exists ? route('admin.activities.lessons.update', [$activity, $lesson]) : route('admin.activities.lessons.store', $activity) }}" class="form-box admin-lesson-form">
    @csrf
    @if ($lesson->exists) @method('PUT') @endif
    @unless ($lesson->exists)<input type="hidden" name="submission_key" value="{{ old('submission_key', $lesson->submission_key) }}">@endunless
    <div class="panel-heading"><div><span class="eyebrow">Lesson details</span><h2>Basic information</h2><p>Set the title families will see for this lesson.</p></div></div>
    <div class="admin-lesson-form-fields">
        <div><label for="lesson-title">Lesson title</label><input id="lesson-title" name="title" value="{{ old('title', $lesson->title) }}" maxlength="255" required></div>
        <input type="hidden" name="position" value="{{ old('position', $lesson->position ?: 1) }}">
        <p class="hint">Saving lesson details does not create or reset content sections.</p>
    </div>
    <div class="admin-lesson-form-actions"><button class="btn btn-blue" type="submit">{{ $lesson->exists ? 'Save changes' : 'Create lesson' }}</button><a class="btn btn-outline" href="{{ route('admin.activities.lessons.index', $activity) }}">Cancel</a></div>
</form>

@if ($lesson->exists)
    @include('admin.activities.lessons._sections', ['activity' => $activity, 'lesson' => $lesson->load('sections')])
@else
    <section class="admin-panel admin-lesson-after-save"><span class="eyebrow">Next step</span><h2>Sections become available after saving</h2><p>Save this lesson first, then add and organize its content sections.</p></section>
@endif
@endsection
