@extends('layouts.public')

@section('title', 'Home')
@section('body_class', 'care-page home-page')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/care.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/home.css') }}">
@endpush

@section('content')
<section class="care-hero home-hero">
    <div class="care-hero__grain" aria-hidden="true"></div>
    <div class="container care-hero__inner">
        <div class="care-hero__copy">
            <span class="care-eyebrow">Support for Down syndrome families</span>
            <h1>Every child. Every step. <span>Together.</span></h1>
            <p>Find practical activities, clear information, and professional support for your family—all in one welcoming place.</p>
            <div class="care-hero__actions">
                <a class="care-btn care-btn--primary" href="{{ route('activities.index') }}">Explore activities</a>
                <a class="care-btn care-btn--ghost" href="{{ route('doctors.index') }}">Find a professional</a>
            </div>
            <div class="home-trust-note"><span aria-hidden="true">✓</span> Small steps, at your child’s own pace</div>
        </div>
        <aside class="care-hero__art" aria-label="A family learning together">
            <figure class="home-hero-photo">
                <img src="{{ asset('assets/images/hero.jpeg') }}" alt="A happy family spending time together" loading="eager">
                <figcaption><strong>Every child is unique</strong><span>Support that meets your family where you are.</span></figcaption>
            </figure>
            <div class="care-float care-float--bottom"><span class="care-float__icon is-amber" aria-hidden="true">♡</span><span><strong>Family-first</strong><small>Guidance with care</small></span></div>
        </aside>
    </div>
</section>

<section class="home-highlights" aria-label="What Little Steps offers">
    <div class="container home-highlights__grid">
        <div><strong>Practical ideas</strong><span>Activities for everyday learning</span></div>
        <div><strong>Trusted support</strong><span>Connect with professionals</span></div>
        <div><strong>At your pace</strong><span>Every family’s journey is different</span></div>
    </div>
</section>

<section class="home-section home-finder">
    <div class="container home-finder__grid">
        <div class="home-section-intro">
            <span class="care-kicker">A helpful place to begin</span>
            <h2>Find an activity for your child</h2>
            <p>Choose an age and an area to see ideas you can explore together at home.</p>
        </div>
        <div class="home-form-card">
            <form id="next-form">
                <div class="home-fields">
                    <div class="care-field"><label for="age-select">Child’s age</label><select id="age-select" aria-label="Choose your child's age"></select></div>
                    <div class="care-field"><label for="area-select">Area to explore</label><select id="area-select" aria-label="Choose a development area"></select></div>
                </div>
                <button class="care-btn care-btn--primary" type="submit">Show matching activities <span aria-hidden="true">→</span></button>
            </form>
        </div>
    </div>
</section>

<section class="home-section home-steps">
    <div class="container">
        <div class="home-section-heading"><span class="care-kicker">Simple &amp; clear</span><h2>How Little Steps works</h2><p>Start with one small idea and build a routine that works for your family.</p></div>
        <div class="home-step-grid">
            <article class="home-step-card"><span>01</span><h3>Choose an age group</h3><p>Browse activities by age and the skills your child is exploring.</p></article>
            <article class="home-step-card"><span>02</span><h3>Practice together</h3><p>Try simple, guided activities at home and adapt them to your child.</p></article>
            <article class="home-step-card"><span>03</span><h3>Keep track</h3><p>Create a family account to save activities and notice progress over time.</p></article>
        </div>
    </div>
</section>

<section class="home-section home-age-groups">
    <div class="container">
        <div class="home-section-heading"><span class="care-kicker">Start where you are</span><h2>Explore by age group</h2><p>Each child develops at their own pace. Use these groups as a simple way to find ideas.</p></div>
        <div class="home-age-grid" id="age-list"></div>
    </div>
</section>

<section class="home-section home-featured">
    <div class="container">
        <div class="home-section-heading"><span class="care-kicker">Try something today</span><h2>Activities to explore</h2><p>Everyday moments can create opportunities to play, communicate, and learn.</p></div>
        <div class="home-activity-grid" id="featured"></div>
        <div class="home-centered"><a class="care-btn care-btn--outline" href="{{ route('activities.index') }}">Browse all activities</a></div>
    </div>
</section>

<section class="home-next-step"><div class="container"><div class="home-next-step__card"><div><span class="care-kicker">Support when you need it</span><h2>Looking for professional guidance?</h2><p>Explore professionals who can help your family find the next step that feels right.</p></div><a class="care-btn care-btn--primary" href="{{ route('doctors.index') }}">Find a professional <span aria-hidden="true">→</span></a></div><p class="home-disclaimer">Little Steps provides general information, not medical advice. For questions about your child’s health or development, speak with a qualified professional.</p></div></section>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/data.js') }}"></script>
    <script src="{{ asset('assets/js/main.js') }}"></script>
    <script src="{{ asset('assets/js/index.js') }}"></script>
@endpush
