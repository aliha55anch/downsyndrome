@extends('layouts.public')
@section('title', $profile->professional_title.' '.$profile->user->name)
@section('body_class', 'consultancy-page doctor-profile-page')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/consultancy.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/doctor-profile.css') }}">
@endpush

@section('content')
<section class="doctor-public-profile">
    <div class="container doctor-profile-container">
        <a class="doctor-profile-back" href="{{ route('doctors.index') }}"><span aria-hidden="true">←</span> All doctors</a>
    @include('doctors._profile-content', ['profile' => $profile])
    </div>
</section>
@endsection
