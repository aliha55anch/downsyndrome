@extends('layouts.admin')
@section('title','Create activity')
@section('content')
<div class="page-title"><h1>Create a new activity</h1><p>Start with the basic information. Lessons and sections are added manually after saving.</p></div>
<form method="POST" action="{{ route('admin.activities.store') }}" enctype="multipart/form-data" class="form-box" data-activity-form>@csrf
    <label for="title">Activity title</label><input id="title" name="title" value="{{ old('title') }}" required>
    <label for="activity_icon">Custom activity icon or image <span class="hint">JPG, PNG, or WebP, maximum 500KB</span></label><input type="file" id="activity_icon" name="activity_icon" accept=".jpg,.jpeg,.png,.webp" data-image-preview="#activity-icon-preview"><img id="activity-icon-preview" class="admin-icon-preview" alt="" hidden>
    <div class="form-row"><div><label for="age_group">Age group label</label><select id="age_group" name="age_group" required>@foreach($ageGroups as $value)<option value="{{ $value }}" @selected(old('age_group')===$value)>{{ $value }}</option>@endforeach</select></div><div><label for="min_age">Minimum age <span class="hint">Years</span></label><input type="number" id="min_age" name="min_age" min="0" max="100" required value="{{ old('min_age') }}"></div><div><label for="max_age">Maximum age <span class="hint">Years</span></label><input type="number" id="max_age" name="max_age" min="0" max="100" required value="{{ old('max_age') }}"></div><div><label for="development_area">Development area</label><select id="development_area" name="development_area" required>@foreach($developmentAreas as $value)<option value="{{ $value }}" @selected(old('development_area')===$value)>{{ $value }}</option>@endforeach</select></div></div>
    <label for="short_description">Short description</label><textarea id="short_description" name="short_description" maxlength="500" required>{{ old('short_description') }}</textarea>
    <input type="hidden" name="status" value="draft">
    <div class="btn-row"><button class="btn btn-blue" type="submit">Save and continue</button><a class="btn btn-outline" href="{{ route('admin.activities.index') }}">Cancel</a></div>
</form>
<script>(function(){var file=document.querySelector('[data-image-preview]');if(file)file.addEventListener('change',function(){var image=document.querySelector(file.dataset.imagePreview),selected=file.files[0];if(selected&&image){image.src=URL.createObjectURL(selected);image.hidden=false;}});})();</script>
@endsection
