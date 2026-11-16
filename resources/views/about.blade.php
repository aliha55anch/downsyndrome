@extends('layouts.public')
@extends('layouts.public')

@section('title', 'About Little Steps')
@section('body_class', 'care-page about-page')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/care.css') }}">
@endpush

@section('content')
<section class="care-hero about-hero">
    <div class="care-hero__grain" aria-hidden="true"></div>
    <div class="container care-hero__inner">
        <div class="care-hero__copy">
            <span class="care-eyebrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s-7-4.35-7-10a4 4 0 0 1 7-2.65A4 4 0 0 1 19 11c0 5.65-7 10-7 10Z"/></svg>About Little Steps</span>
            <h1>Support for your child&rsquo;s <span>next step.</span></h1>
            <p>Little Steps brings practical learning activities, clear information, and professional support together in one welcoming place for families.</p>
            <ul class="care-hero__points">
                <li><span class="care-hero__point-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span><span><strong>Made for everyday family life</strong><small>Simple ideas you can explore at your own pace.</small></span></li>
                <li><span class="care-hero__point-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 4.5 6v5.4c0 4.7 3.2 8.1 7.5 9.6 4.3-1.5 7.5-4.9 7.5-9.6V6L12 3Z"/><path d="m8.7 12.2 2.1 2.1 4.5-4.5"/></svg></span><span><strong>Support with care</strong><small>Find professionals and guidance when you need them.</small></span></li>
            </ul>
            <div class="care-hero__actions"><a class="care-btn care-btn--primary" href="{{ route('activities.index') }}">Explore activities</a><a class="care-btn care-btn--ghost" href="{{ route('doctors.index') }}">Find a professional</a></div>
        </div>
        <aside class="care-hero__art" aria-label="A family learning together">
            <figure class="about-hero-photo"><img src="{{ asset('assets/images/area-learning.jpeg') }}" alt="A child enjoying a book outdoors" loading="eager"><figcaption><span>Every child, every step</span>Learning grows through connection and practice.</figcaption></figure>
            <div class="care-float care-float--top"><span class="care-float__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v15H6.5A2.5 2.5 0 0 0 4 20.5v-15Z"/><path d="M4 20.5A2.5 2.5 0 0 1 6.5 18H20"/></svg></span><span><strong>Learn at your pace</strong><small>Small steps count</small></span></div>
            <div class="care-float care-float--bottom"><span class="care-float__icon is-amber"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M8 12h8M12 8v8"/></svg></span><span><strong>Family-first</strong><small>Support for the whole family</small></span></div>
        </aside>
    </div>
</section>

<section class="care-body about-intro">
    <div class="container">
        <div class="about-section-heading"><span class="care-kicker">Our purpose</span><h2>A welcoming place to learn, connect, and find support.</h2><p>Every child is an individual with their own interests, strengths, and pace. Little Steps helps families find practical ways to encourage learning and connect with support.</p></div>
        <div class="about-pillars">
            <article class="about-pillar"><span class="about-pillar__number">01</span><h3>Explore activities</h3><p>Find simple activities for communication, movement, daily routines, and play.</p><a href="{{ route('activities.index') }}">Browse activities <span aria-hidden="true">→</span></a></article>
            <article class="about-pillar"><span class="about-pillar__number">02</span><h3>Track small steps</h3><p>Save activities and keep a family-friendly view of learning progress over time.</p><a href="{{ route('register') }}">Create a family account <span aria-hidden="true">→</span></a></article>
            <article class="about-pillar"><span class="about-pillar__number">03</span><h3>Find professional support</h3><p>Browse verified professionals and choose support that fits your family.</p><a href="{{ route('doctors.index') }}">Explore consultancy <span aria-hidden="true">→</span></a></article>
        </div>
    </div>
</section>

<section class="about-understanding">
    <div class="container about-understanding__grid">
        <div><span class="care-kicker">Clear information</span><h2>About Down syndrome</h2><p>Down syndrome is a genetic condition caused by an extra copy of chromosome 21. It can affect how a person develops, but every person is different. Children learn and grow in their own ways and may benefit from tailored support.</p><p>The extra chromosome happens by chance. It is nobody&rsquo;s fault and is not caused by anything a parent did or did not do.</p><div class="about-medical-note"><strong>For health questions</strong><span>This page offers general information, not medical advice. A qualified health professional can give guidance for your child.</span></div></div>
        <div class="about-facts-card"><h3>Every child is an individual</h3><ul><li>Children have their own personalities, interests, and strengths.</li><li>Some may need extra time, practice, or different ways to learn.</li><li>Families can ask professionals about health checks and support.</li><li>Play, everyday routines, and connection can all create opportunities to learn.</li></ul></div>
    </div>
</section>

<section class="care-body about-myths">
    <div class="container">
        <div class="about-section-heading"><span class="care-kicker">Facts &amp; understanding</span><h2>Common questions, clear answers</h2><p>There is no single way to experience Down syndrome. These short answers are a starting point for learning more.</p></div>
        <div class="about-faq-list">
            <details><summary>Can children with Down syndrome learn new things?</summary><p>Yes. Children can learn throughout life. Some may benefit from more time, repetition, and support that suits their needs.</p></details>
            <details><summary>Are all children with Down syndrome the same?</summary><p>No. Each child has their own personality, abilities, interests, and pace of development.</p></details>
            <details><summary>Does Down syndrome always mean a child is unwell?</summary><p>No. Some health conditions are more common, so families can ask their child&rsquo;s doctor about recommended checkups and care.</p></details>
            <details><summary>Can children attend inclusive schools?</summary><p>Many children attend inclusive schools. The right setting and supports depend on each child and family.</p></details>
        </div>
    </div>
</section>

<section class="about-next-step"><div class="container"><div class="about-next-step__card"><div><span class="care-kicker">A next step for your family</span><h2>Start with what feels helpful today.</h2><p>Explore an activity, look for professional support, or send our team a question.</p></div><div class="care-hero__actions"><a class="care-btn care-btn--primary" href="{{ route('activities.index') }}">Find activities</a><a class="care-btn care-btn--outline" href="{{ route('contact.create') }}">Contact us</a></div></div></div></section>
@endsection
