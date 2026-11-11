@extends('layouts.auth')
@section('title', 'Professional Consultancy')
@section('content')
<section class="page-title"><div class="container"><h1>Professional Consultancy</h1><p>Browse verified professionals and find the right support for your child.</p></div></section>
<main class="container section">
    @include('partials.care-switcher', ['activeCarePage' => 'consultancy'])
    <form class="doctor-directory-filters" method="GET" action="{{ route('doctors.index') }}">
        <div><label for="name">Doctor name</label><input id="name" name="name" value="{{ $name }}" placeholder="Search by name"></div>
        <div><label for="specialty">Specialty</label><select id="specialty" name="specialty"><option value="">All specialties</option>@foreach($specialties as $item)<option value="{{ $item->id }}" @selected((string)$specialty===(string)$item->id)>{{ $item->name }}</option>@endforeach</select></div>
        <div><label for="city">City</label><select id="city" name="city"><option value="">All cities</option>@foreach($cities as $item)<option value="{{ $item }}" @selected($city===$item)>{{ $item }}</option>@endforeach</select></div>
        <button class="btn btn-blue" type="submit">Search</button>
    </form>
    <div class="public-doctor-grid">
        @forelse($doctors as $doctor)
            <article class="public-doctor-card">
                <div class="public-doctor-info">
                    <span class="doctor-verified-badge">Verified</span>
                    <h2>{{ $doctor->professional_title }} {{ $doctor->user->name }}</h2>
                    <p>{{ $doctor->specialties->pluck('name')->join(', ') ?: $doctor->specialty }} · {{ $doctor->city }}</p>
                    <p>{{ $doctor->qualification }} · {{ $doctor->years_experience }} years of experience</p>
                    <p>@foreach($doctor->consultationOptions->where('is_active', true) as $option){{ $option->type === 'online' ? 'Online' : 'In person' }} · {{ $option->fee !== null ? config('app.currency', 'PKR').' '.number_format((float)$option->fee, 2) : 'Fee not specified' }}@if(!$loop->last)<br>@endif @endforeach</p>
                    <div class="btn-row">
                        <a class="btn btn-outline btn-small" href="{{ route('doctors.show', $doctor) }}">View profile</a>
                        @if(auth()->guest() || auth()->user()->hasRole(\App\Models\User::ROLE_PARENT))
                            @if($doctor->consultationOptions->contains('is_active', true))<a class="btn btn-blue btn-small" href="{{ route('appointments.create', $doctor) }}">Book appointment</a>@endif
                        @else
                            <span class="hint">Patient accounts can book appointments.</span>
                        @endif
                    </div>
                </div>
                @if($doctor->profile_photo_path)
                    <img class="public-doctor-photo public-doctor-portrait" src="{{ route('doctors.photo', $doctor) }}" alt="Profile photo of {{ $doctor->user->name }}">
                @else
                    <div class="public-doctor-photo public-doctor-portrait doctor-photo-placeholder" aria-hidden="true">{{ strtoupper(substr($doctor->user->first_name ?: $doctor->user->name,0,1)) }}</div>
                @endif
            </article>
        @empty
            <div class="doctor-panel"><h2>No published profiles found</h2><p>Try another specialty or city. New profiles appear after administrator publication.</p></div>
        @endforelse
    </div>
    {{ $doctors->links() }}
</main>
@endsection
