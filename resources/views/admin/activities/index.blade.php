@extends('layouts.admin')

@section('title', 'Manage Activities')

@section('content')
<div class="page-title">
    <h1>Manage Activities</h1>
    <p>Create, edit, publish, or remove learning activities.</p>
</div>

<div class="btn-row"><a class="btn btn-blue" href="{{ route('admin.activities.create') }}">Create activity</a></div>

<form method="GET" class="filter-bar" action="{{ route('admin.activities.index') }}">
    <div class="form-row">
        <div><label for="search">Search</label><input type="search" id="search" name="search" value="{{ request('search') }}"></div>
        <div><label for="age_group">Age group</label><select id="age_group" name="age_group"><option value="">All ages</option>@foreach ($ageGroups as $value)<option value="{{ $value }}" @selected(request('age_group') === $value)>{{ $value }}</option>@endforeach</select></div>
        <div><label for="development_area">Area</label><select id="development_area" name="development_area"><option value="">All areas</option>@foreach ($developmentAreas as $value)<option value="{{ $value }}" @selected(request('development_area') === $value)>{{ $value }}</option>@endforeach</select></div>
        <div><label for="status">Status</label><select id="status" name="status"><option value="">All statuses</option>@foreach ($statuses as $value)<option value="{{ $value }}" @selected(request('status') === $value)>{{ ucfirst($value) }}</option>@endforeach</select></div>
        <div style="align-self:end;"><button class="btn btn-blue" type="submit">Filter</button></div>
    </div>
</form>

<div class="table-wrap">
    <table><thead><tr><th>Title</th><th>Age</th><th>Area</th><th>Status</th><th>Resources</th><th>Actions</th></tr></thead><tbody>
    @forelse ($activities as $activity)
        <tr><td>{{ $activity->title }}</td><td>{{ $activity->age_group }}</td><td>{{ $activity->development_area }}</td><td>{{ ucfirst($activity->status) }}</td><td>{{ collect([$activity->featured_image_path, $activity->pptx_path, $activity->pdf_path, $activity->youtube_url])->filter()->count() }}</td><td><a class="btn btn-outline btn-small" href="{{ route('admin.activities.edit', $activity) }}">Edit</a><form method="POST" action="{{ route('admin.activities.destroy', $activity) }}" style="display:inline;" onsubmit="return confirm('Delete this activity?');">@csrf @method('DELETE')<button class="btn btn-red btn-small" type="submit">Delete</button></form></td></tr>
    @empty
        <tr><td colspan="6">No activities found.</td></tr>
    @endforelse
    </tbody></table>
</div>
{{ $activities->links() }}
@endsection
