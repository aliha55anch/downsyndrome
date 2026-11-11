@extends('layouts.admin')
@section('title', 'Doctor specialties')
@section('content')
<div class="page-title"><a href="{{ route('admin.doctors.index') }}">← Doctor Management</a><h1>Doctor specialty options</h1><p>Options are reusable and can be deactivated without removing doctors' history.</p></div>
<section class="doctor-panel"><form method="POST" action="{{ route('admin.doctors.specialties.store') }}">@csrf<label for="name">Add a specialty</label><div class="form-row"><input id="name" name="name" maxlength="120" required><button class="btn btn-blue">Add option</button></div></form></section>
<section class="doctor-panel">@forelse($specialties as $specialty)<form class="specialty-admin-row" method="POST" action="{{ route('admin.doctors.specialties.update', $specialty) }}">@csrf @method('PUT')<input name="name" value="{{ $specialty->name }}" maxlength="120" required><span>{{ $specialty->doctor_profiles_count }} profile(s)</span><input type="hidden" name="is_active" value="0"><label class="inline-check"><input type="checkbox" name="is_active" value="1" @checked($specialty->is_active)> Active</label><button class="btn btn-outline btn-small">Save</button></form>@empty<p class="muted">No specialties have been added.</p>@endforelse</section>
@endsection
