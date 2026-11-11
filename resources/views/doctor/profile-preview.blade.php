@extends('layouts.doctor')
@section('title', 'Profile preview')
@section('heading', 'Profile preview')
@section('doctor-content')
@if(!$profile->isPubliclyBookable())
    <section class="doctor-panel doctor-callout"><h2>This profile is not public yet</h2><p>This is how the profile will look on the doctor directory. Only approved and published profiles appear to patients.</p></section>
@endif
<section class="doctor-profile-preview">
    @include('doctors._profile-content', ['profile' => $profile, 'isDoctorPreview' => true])
</section>
@endsection
