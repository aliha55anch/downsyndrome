@extends('layouts.auth')

@section('title', 'Doctor verification application')

@section('shell_class', 'auth-shell--wide')
@section('card_class', 'auth-card--wide')

@section('story_eyebrow', 'Join the professional network')
@section('story_heading', 'Bring your expertise to families who need it.')
@section('story_text', 'Create a secure professional account and submit your credentials. Our team reviews every application before your profile becomes visible to families.')
@section('story_points')
    <li>
        <span class="auth-point-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
        <span><strong>Verified professional profile</strong><small>Show families your qualifications and experience.</small></span>
    </li>
    <li>
        <span class="auth-point-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
        <span><strong>Private document review</strong><small>Your credentials are visible only to administrators.</small></span>
    </li>
    <li>
        <span class="auth-point-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
        <span><strong>Purpose-built workspace</strong><small>Manage appointments, availability, and your profile.</small></span>
    </li>
@endsection

@section('role_label', 'Professional verification')
@section('form_heading', 'Apply for doctor verification')
@section('form_subheading', 'Create your account and submit your professional details. Verification keeps Little Steps safe and trustworthy for every family.')

@section('switch_text')
    Already registered? <a href="{{ route('doctor.login') }}">Sign in to your doctor account</a> &nbsp;·&nbsp; <a href="{{ route('register') }}">Family account</a>
@endsection

@section('content')
<div class="auth-note" style="margin-top:0; margin-bottom:20px;">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5"/><path d="M12 8h.01"/></svg>
    <span><strong>Before you start:</strong> have your medical license and qualification certificate ready. You can upload PDF, JPG, or PNG files up to 10 MB each.</span>
</div>

<form method="POST" action="{{ route('doctor.register.store') }}" enctype="multipart/form-data" class="auth-form">
    @csrf

    <fieldset>
        <legend>Account information</legend>

        <div class="form-row">
            <div class="auth-field">
                <label for="first_name">First name</label>
                <input id="first_name" name="first_name" value="{{ old('first_name') }}" autocomplete="given-name" placeholder="First name" required>
                @error('first_name')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div class="auth-field">
                <label for="last_name">Last name</label>
                <input id="last_name" name="last_name" value="{{ old('last_name') }}" autocomplete="family-name" placeholder="Last name" required>
                @error('last_name')<p class="field-error">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="form-row">
            <div class="auth-field">
                <label for="email">Email address</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" placeholder="doctor@example.com" required>
                @error('email')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div class="auth-field">
                <label for="phone">Phone number</label>
                <input id="phone" name="phone" value="{{ old('phone') }}" autocomplete="tel" placeholder="+92 300 1234567" required>
                @error('phone')<p class="field-error">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="form-row">
            <div class="auth-field">
                <label for="password">Password</label>
                <div class="password-field">
                    <input type="password" id="password" name="password" placeholder="Create a password" minlength="8" autocomplete="new-password" required>
                    <button type="button" class="password-toggle" data-toggle-password="password" aria-label="Show password" aria-pressed="false">
                        <svg class="eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.5 12s3.5-6.5 9.5-6.5S21.5 12 21.5 12s-3.5 6.5-9.5 6.5S2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="2.7"/></svg>
                        <svg class="eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 3 18 18"/><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/><path d="M9.9 5.2A10.7 10.7 0 0 1 12 5c6.5 0 9.5 7 9.5 7a17.8 17.8 0 0 1-3.1 4.2"/><path d="M6.1 6.1A17.5 17.5 0 0 0 2.5 12s3.5 7 9.5 7c1.6 0 3-.4 4.3-1"/></svg>
                    </button>
                </div>
                <p class="hint">Use at least 8 characters.</p>
                @error('password')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div class="auth-field">
                <label for="password_confirmation">Confirm password</label>
                <div class="password-field">
                    <input type="password" id="password_confirmation" name="password_confirmation" placeholder="Write the password again" minlength="8" autocomplete="new-password" required>
                    <button type="button" class="password-toggle" data-toggle-password="password_confirmation" aria-label="Show confirmation password" aria-pressed="false">
                        <svg class="eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.5 12s3.5-6.5 9.5-6.5S21.5 12 21.5 12s-3.5 6.5-9.5 6.5S2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="2.7"/></svg>
                        <svg class="eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 3 18 18"/><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/><path d="M9.9 5.2A10.7 10.7 0 0 1 12 5c6.5 0 9.5 7 9.5 7a17.8 17.8 0 0 1-3.1 4.2"/><path d="M6.1 6.1A17.5 17.5 0 0 0 2.5 12s3.5 7 9.5 7c1.6 0 3-.4 4.3-1"/></svg>
                    </button>
                </div>
            </div>
        </div>
    </fieldset>

    <fieldset>
        <legend>Professional information</legend>

        <div class="form-row">
            <div class="auth-field">
                <label for="professional_title">Professional title</label>
                <input id="professional_title" name="professional_title" value="{{ old('professional_title') }}" placeholder="e.g. Pediatrician" required>
                @error('professional_title')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div class="auth-field">
                <label for="specialty">Medical specialty</label>
                <input id="specialty" name="specialty" value="{{ old('specialty') }}" placeholder="e.g. Developmental pediatrics" required>
                @error('specialty')<p class="field-error">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="form-row">
            <div class="auth-field">
                <label for="qualification">Professional qualification</label>
                <input id="qualification" name="qualification" value="{{ old('qualification') }}" placeholder="e.g. MBBS, FCPS" required>
                @error('qualification')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div class="auth-field">
                <label for="institution">University or institution</label>
                <input id="institution" name="institution" value="{{ old('institution') }}" placeholder="Institution name" required>
                @error('institution')<p class="field-error">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="form-row">
            <div class="auth-field">
                <label for="years_experience">Years of experience</label>
                <input id="years_experience" type="number" name="years_experience" value="{{ old('years_experience', 0) }}" min="0" max="70" required>
                @error('years_experience')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div class="auth-field">
                <label for="workplace">Current clinic or workplace <span class="muted">(optional)</span></label>
                <input id="workplace" name="workplace" value="{{ old('workplace') }}" placeholder="Clinic or hospital">
                @error('workplace')<p class="field-error">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="form-row">
            <div class="auth-field">
                <label for="city">City</label>
                <input id="city" name="city" value="{{ old('city') }}" placeholder="City" required>
                @error('city')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div class="auth-field">
                <label for="biography">Professional introduction <span class="muted">(optional)</span></label>
                <textarea id="biography" name="biography" rows="3" maxlength="2000" placeholder="Tell families briefly about your approach and experience.">{{ old('biography') }}</textarea>
                @error('biography')<p class="field-error">{{ $message }}</p>@enderror
            </div>
        </div>
    </fieldset>

    <fieldset>
        <legend>License and verification documents</legend>

        <div class="form-row">
            <div class="auth-field">
                <label for="license_number">Medical registration / license number</label>
                <input id="license_number" name="license_number" value="{{ old('license_number') }}" placeholder="License number" required>
                @error('license_number')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div class="auth-field">
                <label for="issuing_authority">Issuing authority or council</label>
                <input id="issuing_authority" name="issuing_authority" value="{{ old('issuing_authority') }}" placeholder="Council or authority" required>
                @error('issuing_authority')<p class="field-error">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="form-row">
            <div class="auth-field">
                <label for="license_expires_at">License expiry date <span class="muted">(optional)</span></label>
                <input id="license_expires_at" type="date" name="license_expires_at" value="{{ old('license_expires_at') }}">
                @error('license_expires_at')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div class="auth-upload-note">PDF, JPG, JPEG, or PNG. Maximum 10 MB per file. Documents are private and visible only to you and administrators.</div>
        </div>

        <div class="form-row">
            <div class="auth-field">
                <label for="license_document">Medical license document</label>
                <input id="license_document" type="file" name="license_document" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" required>
                @error('license_document')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div class="auth-field">
                <label for="qualification_document">Qualification certificate</label>
                <input id="qualification_document" type="file" name="qualification_document" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" required>
                @error('qualification_document')<p class="field-error">{{ $message }}</p>@enderror
            </div>
        </div>
    </fieldset>

    <div class="auth-doctor-note">
        <strong>What happens next?</strong> Your account stays unverified until an administrator reviews your information and approves your documents. Verify your email to open your application dashboard and follow the review.
    </div>

    <div class="auth-submit-row">
        <button class="btn btn-blue auth-submit" type="submit" style="margin-top:0;">Submit verification application</button>
    </div>
</form>
@endsection
