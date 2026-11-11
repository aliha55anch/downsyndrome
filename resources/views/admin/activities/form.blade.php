@extends('layouts.admin')

@section('title', 'Edit Activity')

@section('content')
<div class="page-title"><h1>Edit activity</h1><p>Update the basic activity information, then continue managing lessons below.</p></div>

<form method="POST" action="{{ route('admin.activities.update', $activity) }}" enctype="multipart/form-data" class="form-box" data-activity-form>
    @csrf
    @method('PUT')
    <label for="title">Activity title</label>
    <input id="title" name="title" value="{{ old('title', $activity->title) }}" required>

    <label for="activity_icon">Custom activity icon or image <span class="hint">JPG, PNG, or WebP, maximum 500KB</span></label>
    <input type="file" id="activity_icon" name="activity_icon" accept=".jpg,.jpeg,.png,.webp" data-image-preview="#activity-icon-preview">
    <img id="activity-icon-preview" class="admin-icon-preview" src="{{ $activity->icon_path ? route('admin.activities.icon', $activity) : '' }}" alt="Current activity icon" @if(!$activity->icon_path) hidden @endif>
    @if($activity->icon_path)<label class="inline-check"><input type="checkbox" name="remove_activity_icon" value="1"> Remove current icon</label>@endif

    <div class="form-row">
        <div><label for="age_group">Age group label</label><select id="age_group" name="age_group" required>@foreach ($ageGroups as $value)<option value="{{ $value }}" @selected(old('age_group', $activity->age_group) === $value)>{{ $value }}</option>@endforeach</select></div>
        <div><label for="min_age">Minimum age <span class="hint">Years</span></label><input type="number" id="min_age" name="min_age" min="0" max="100" value="{{ old('min_age', $activity->min_age) }}" required></div>
        <div><label for="max_age">Maximum age <span class="hint">Years</span></label><input type="number" id="max_age" name="max_age" min="0" max="100" value="{{ old('max_age', $activity->max_age) }}" required></div>
        <div><label for="development_area">Development area</label><select id="development_area" name="development_area" required>@foreach ($developmentAreas as $value)<option value="{{ $value }}" @selected(old('development_area', $activity->development_area) === $value)>{{ $value }}</option>@endforeach</select></div>
    </div>

    <label for="short_description">Short description</label>
    <input id="short_description" name="short_description" value="{{ old('short_description', $activity->short_description) }}" maxlength="500" required>

    <div class="btn-row"><button class="btn btn-blue" type="submit">Save activity</button><a class="btn btn-outline" href="{{ route('admin.activities.index') }}">Cancel</a></div>
</form>

<section class="form-box editor-publish-controls"><div><span class="eyebrow">Activity status: {{ ucfirst($activity->status) }}</span><p class="hint">{{ $activity->isPublished() ? 'Families can view this activity.' : 'This activity is visible to admins only until published.' }}</p></div><form method="POST" action="{{ route('admin.activities.publication', $activity) }}">@csrf<input type="hidden" name="status" value="{{ $activity->isPublished() ? 'draft' : 'published' }}"><button class="btn {{ $activity->isPublished() ? 'btn-outline' : 'btn-blue' }}" type="submit">{{ $activity->isPublished() ? 'Unpublish activity' : 'Publish activity' }}</button></form></section>

<section class="form-box activity-editor-lessons">
    <div class="panel-heading"><div><span class="eyebrow">Activity editor</span><h2>Lessons</h2></div><a class="btn btn-blue btn-small" href="{{ route('admin.activities.lessons.create', $activity) }}">+ Add lesson</a></div>
    <p class="hint">Open a lesson to view and manage its individual sections.</p>
    @forelse($activity->lessons as $lesson)
        <article class="lesson-admin-card"><div><span class="eyebrow">Lesson {{ $lesson->position }}</span><h3>{{ $lesson->title }}</h3><small>{{ $lesson->sections->count() }} saved section(s)</small></div><div class="btn-row"><a class="btn btn-blue btn-small" href="{{ route('admin.activities.lessons.edit', [$activity, $lesson]) }}">Open lesson</a></div></article>
    @empty
        <div class="empty-state"><h3>No lessons have been added.</h3><p>This activity contains no default lessons or sections.</p><a class="btn btn-blue" href="{{ route('admin.activities.lessons.create', $activity) }}">Add the first lesson</a></div>
    @endforelse
</section>

<script>(function(){var form=document.querySelector('[data-activity-form]');if(!form)return;form.querySelectorAll('[data-image-preview]').forEach(function(file){file.addEventListener('change',function(){var image=document.querySelector(file.dataset.imagePreview),selected=file.files[0];if(selected&&image){image.src=URL.createObjectURL(selected);image.hidden=false;}});});})();</script>
@endsection
