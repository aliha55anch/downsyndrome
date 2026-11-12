@extends('layouts.auth')
@section('title', $profile->professional_title.' '.$profile->user->name)
@section('content')
<main class="container section public-doctor-profile">
    <a href="{{ route('doctors.index') }}">← All doctors</a>
    @include('doctors._profile-content', ['profile' => $profile])
</main>
@endsection
