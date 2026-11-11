@extends('layouts.auth')

@section('title', 'Contact Us')

@section('content')
<section class="page-title">
    <div class="container">
        <h1>Contact Us</h1>
        <p>Have a question or an idea? We would love to hear from you.</p>
    </div>
</section>

<div class="form-box form-narrow">
    <form method="POST" action="{{ route('contact.store') }}">
        @csrf
        <label for="name">Your name</label>
        <input type="text" id="name" name="name" value="{{ old('name') }}" autocomplete="name" required>

        <label for="email">Your email</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="email" required>

        <label for="subject">Subject</label>
        <input type="text" id="subject" name="subject" value="{{ old('subject') }}" required>

        <label for="message">Your message</label>
        <textarea id="message" name="message" maxlength="5000" required>{{ old('message') }}</textarea>

        <button class="btn btn-blue" type="submit">Send message</button>
    </form>
</div>

<div class="disclaimer">
    Please do not send private medical details here. This form is not for emergencies. If your child needs urgent medical help, call a doctor or go to a hospital.
</div>

<p class="center muted">Our team usually replies within two working days.</p>
@endsection
