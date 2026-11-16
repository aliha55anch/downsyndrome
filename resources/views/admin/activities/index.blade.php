@extends('layouts.admin')

@section('title', 'Manage Activities')

@section('content')
<div class="page-title admin-dashboard-heading admin-activities-heading">
    <div><span class="eyebrow">Learning library</span><h1>Manage Activities</h1><p>Create, edit, publish, or remove learning activities.</p></div>
    <a class="btn btn-blue" href="{{ route('admin.activities.create') }}">Create activity <span aria-hidden="true">+</span></a>
</div>

<form method="GET" class="filter-bar" action="{{ route('admin.activities.index') }}">
    <div class="form-row">
        <div><label for="search">Search</label><input type="search" id="search" name="search" value="{{ request('search') }}"></div>
        <div><label for="age_group">Age group</label><select id="age_group" name="age_group"><option value="">All ages</option>@foreach ($ageGroups as $value)<option value="{{ $value }}" @selected(request('age_group') === $value)>{{ $value }}</option>@endforeach</select></div>
        <div><label for="development_area">Area</label><select id="development_area" name="development_area"><option value="">All areas</option>@foreach ($developmentAreas as $value)<option value="{{ $value }}" @selected(request('development_area') === $value)>{{ $value }}</option>@endforeach</select></div>
        <div><label for="status">Status</label><select id="status" name="status"><option value="">All statuses</option>@foreach ($statuses as $value)<option value="{{ $value }}" @selected(request('status') === $value)>{{ ucfirst($value) }}</option>@endforeach</select></div>
        <div class="admin-activities-filter-submit"><button class="btn btn-blue" type="submit">Filter activities</button></div>
    </div>
</form>

<section class="admin-panel admin-activities-panel">
    <div class="panel-heading"><div><span class="eyebrow">Activity library</span><h2>Learning activities</h2><p>Manage content and publication status for families.</p></div><span class="admin-users-count">{{ $activities->total() }} {{ \Illuminate\Support\Str::plural('activity', $activities->total()) }}</span></div>
    <div class="table-wrap admin-activities-table-wrap">
    <table class="admin-activities-table"><thead><tr><th scope="col">Activity</th><th scope="col">Age group</th><th scope="col">Area</th><th scope="col">Status</th><th scope="col">Resources</th><th scope="col">Actions</th></tr></thead><tbody>
    @forelse ($activities as $activity)
        <tr><td class="admin-activity-title">{{ $activity->title }}</td><td>{{ $activity->age_group }}</td><td>{{ $activity->development_area }}</td><td><span class="admin-activity-status status-{{ $activity->status }}">{{ ucfirst($activity->status) }}</span></td><td>{{ collect([$activity->featured_image_path, $activity->pptx_path, $activity->pdf_path, $activity->youtube_url])->filter()->count() }}</td><td><div class="admin-activity-actions"><a class="btn btn-outline btn-small" href="{{ route('admin.activities.edit', $activity) }}">Edit activity</a><form method="POST" action="{{ route('admin.activities.destroy', $activity) }}" onsubmit="return confirm('Delete this activity?');">@csrf @method('DELETE')<button class="btn btn-red btn-small" type="submit">Delete</button></form></div></td></tr>
    @empty
        <tr><td class="admin-activities-empty" colspan="6">No activities match these filters. Try a different search or create a new activity.</td></tr>
    @endforelse
    </tbody></table>
</div>
    {{ $activities->links() }}
</section>
@endsection
