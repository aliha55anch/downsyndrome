@extends('layouts.admin')
@section('title', $lesson->exists ? 'Edit lesson' : 'Add lesson')
@section('content')
<div class="page-title"><h1>{{ $lesson->exists ? 'Edit lesson' : 'Add lesson' }}</h1><p>{{ $activity->title }}. A lesson starts empty. Add content only through Add Section.</p></div>
<form method="POST" action="{{ $lesson->exists ? route('admin.activities.lessons.update', [$activity, $lesson]) : route('admin.activities.lessons.store', $activity) }}" class="form-box">
    @csrf
    @if ($lesson->exists) @method('PUT') @endif
    @unless ($lesson->exists)<input type="hidden" name="submission_key" value="{{ old('submission_key', $lesson->submission_key) }}">@endunless
    <label for="lesson-title">Lesson title</label>
    <input id="lesson-title" name="title" value="{{ old('title', $lesson->title) }}" required>
    <input type="hidden" name="position" value="{{ old('position', $lesson->position ?: 1) }}">
    <p class="hint">Saving lesson details does not create or reset any content sections.</p>
    <button class="btn btn-blue">{{ $lesson->exists ? 'Save changes' : 'Create lesson' }}</button>
    <a class="btn btn-outline" href="{{ route('admin.activities.lessons.index', $activity) }}">Back to lessons</a>
</form>
@if ($lesson->exists)
    @include('admin.activities.lessons._sections', ['activity' => $activity, 'lesson' => $lesson->load('sections')])
@else
    <section class="form-box empty-state"><h2>Sections become available after saving</h2><p>Save this empty lesson first, then add sections one at a time.</p></section>
@endif
@endsection
