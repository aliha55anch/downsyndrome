@extends('layouts.doctor')
@section('title', 'Professional profile')
@section('heading', 'Professional profile')

@push('styles')
<style>
.doctor-profile-edit { width: 100%; max-width: 1100px; display: grid; gap: 1.1rem; }
.doctor-profile-edit [hidden] { display: none !important; }
.doctor-profile-edit .profile-edit-intro { padding: clamp(18px, 2.4vw, 26px); }
.doctor-profile-edit .profile-edit-intro p { max-width: 78ch; margin: 0; color: #536b73; line-height: 1.65; }
.doctor-profile-edit .profile-form { display: grid; gap: 1.1rem; min-width: 0; }
.doctor-profile-edit .form-section { min-width: 0; margin: 0; padding: clamp(19px, 2.5vw, 28px); }
.doctor-profile-edit .form-section > h2 { margin: 0 0 .35rem; font-size: 1.2rem; color: #1d3d49; }
.doctor-profile-edit .section-desc { margin: 0 0 1.15rem; color: var(--ui-muted, #64748b); font-size: .95rem; line-height: 1.55; }
.doctor-profile-edit .form-grid { display: grid; align-items: start; gap: 1rem 1.15rem; }
.doctor-profile-edit .form-grid.cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
.doctor-profile-edit .field { display: flex; flex-direction: column; min-width: 0; }
.doctor-profile-edit .field > label { margin: 0 0 .45rem; font-size: .92rem; line-height: 1.4; }
.doctor-profile-edit .field > input, .doctor-profile-edit .field > select, .doctor-profile-edit .field > textarea { min-width: 0; margin: 0; }
.doctor-profile-edit .field > input:disabled { background: #f4f7f8; color: #657b83; cursor: not-allowed; }
.doctor-profile-edit .field .hint { margin: .4rem 0 0; line-height: 1.45; }
.doctor-profile-edit .field-span-2 { grid-column: 1 / -1; }
.doctor-profile-edit .doctor-photo-editor { display: grid; grid-template-columns: 96px minmax(0, 1fr); gap: 1rem 1.25rem; align-items: center; padding: 1rem 1.1rem; margin: 0 0 1.2rem; background: #f8fafc; border: 1px solid var(--ui-line, #e5e7eb); border-radius: 12px; }
.doctor-profile-edit .photo-preview-wrap { width: 96px; height: 96px; border-radius: 12px; border: 1px solid #cbd9df; background: #fff; overflow: hidden; }
.doctor-profile-edit .photo-preview-wrap img { width: 100%; height: 100%; object-fit: cover; display: block; }
.doctor-profile-edit .photo-preview-placeholder { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #94a3b8; background: #f1f5f9; font-size: .8rem; font-weight: 600; }
.doctor-profile-edit .photo-controls { display: grid; gap: .35rem; min-width: 0; }
.doctor-profile-edit .photo-controls > label { margin: 0; }
.doctor-profile-edit .photo-controls input[type="file"] { width: 100%; min-height: 0; margin: 0; padding: .65rem; }
.doctor-profile-edit .photo-controls .hint { margin: 0; }
.doctor-profile-edit .remove-photo-option { display: inline-flex; align-items: center; gap: .5rem; margin-top: .6rem; font-weight: 500; color: #475569; }
.doctor-profile-edit .remove-photo-option input[type="checkbox"] { width: 18px; height: 18px; min-height: 18px; margin: 0; padding: 0; }
.doctor-profile-edit .inline-check { display: inline-flex; align-items: center; gap: .55rem; margin: 0; font-weight: 600; color: #314b55; }
.doctor-profile-edit .inline-check input[type="checkbox"] { flex: 0 0 auto; width: 18px; min-height: 18px; height: 18px; margin: 0; padding: 0; }
.doctor-profile-edit .specialty-options { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: .45rem .75rem; padding: .85rem 1rem; border: 1px solid #cbd9df; border-radius: 10px; background: #fff; }
.doctor-profile-edit .specialty-options.is-invalid-block { border-color: #dc2626; }
.doctor-profile-edit .field-error { margin: .35rem 0 0; color: #b91c1c; font-size: .9rem; }
.doctor-profile-edit input.is-invalid, .doctor-profile-edit select.is-invalid, .doctor-profile-edit textarea.is-invalid { border-color: #dc2626; }
.doctor-profile-edit .site-feedback ul { padding-left: 1.1rem; }
.doctor-profile-edit .specialty-options .inline-check { margin-top: 0; font-weight: 500; }
.doctor-profile-edit .qualification-block { display: grid; gap: .75rem; margin-top: .25rem; }
.doctor-profile-edit .qualification-row { display: grid; grid-template-columns: minmax(0,1.2fr) minmax(0,1.2fr) minmax(110px,.7fr); gap: .75rem; }
.doctor-profile-edit .qualification-row input { min-width: 0; margin: 0; }
.doctor-profile-edit .form-actions { display: flex; justify-content: flex-end; gap: .75rem; padding: 1rem 1.25rem; background: #fff; border: 1px solid var(--ui-line, #e5e7eb); border-radius: 16px; box-shadow: var(--ui-shadow, 0 10px 22px rgba(33,55,64,.08)); }
.doctor-profile-edit .form-actions .btn { min-height: 46px; }
@media (max-width: 800px) {
  .doctor-profile-edit .form-grid.cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .doctor-profile-edit .qualification-row { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .doctor-profile-edit .qualification-row input:last-child { grid-column: 1 / -1; }
}
@media (max-width: 560px) {
  .doctor-profile-edit .form-grid.cols-2, .doctor-profile-edit .qualification-row { grid-template-columns: minmax(0, 1fr); }
  .doctor-profile-edit .field-span-2, .doctor-profile-edit .qualification-row input:last-child { grid-column: auto; }
  .doctor-profile-edit .doctor-photo-editor { grid-template-columns: 1fr; }
  .doctor-profile-edit .form-actions { justify-content: stretch; }
  .doctor-profile-edit .form-actions .btn { width: 100%; }
}
</style>
@endpush

@section('doctor-content')
<div class="doctor-profile-edit">
    <section class="doctor-panel profile-edit-intro">
        <p>Keep your professional and consultancy details current. Information here appears on your public profile and consultation requests. Changes to license or core verification details may require review.</p>
        @if ($errors->any())
            <div class="site-feedback site-feedback--error" role="alert" style="margin-top:1rem;">
                <strong>Please fix the following:</strong>
                <ul style="margin:.4rem 0 0 1.1rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </section>

    <form method="POST" action="{{ route('doctor.profile.update') }}" class="profile-form" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <section class="doctor-panel form-section">
            <h2>Account information</h2>
            <p class="section-desc">Basic contact details for your account.</p>
            <div class="form-grid cols-2">
                <div class="field">
                    <label for="first_name">First name</label>
                    <input id="first_name" name="first_name" value="{{ old('first_name', $user->first_name) }}" required @class(['is-invalid' => $errors->has('first_name')])>
                    @error('first_name')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div class="field">
                    <label for="last_name">Last name</label>
                    <input id="last_name" name="last_name" value="{{ old('last_name', $user->last_name) }}" required @class(['is-invalid' => $errors->has('last_name')])>
                    @error('last_name')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div class="field">
                    <label for="email_display">Email address</label>
                    <input id="email_display" value="{{ $user->email }}" disabled>
                </div>
                <div class="field">
                    <label for="phone">Phone</label>
                    <input id="phone" name="phone" value="{{ old('phone', $profile->phone) }}" required @class(['is-invalid' => $errors->has('phone')])>
                    @error('phone')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="doctor-panel form-section">
            <h2>Public profile &amp; consultancy</h2>
            <p class="section-desc">These details help families find you and understand your consultation offering.</p>

            <div class="doctor-photo-editor">
                <div class="photo-preview-wrap">
                    @if($profile->profile_photo_path)
                        <img id="photo-preview" src="{{ route('doctor.profile.photo') }}" alt="Current profile photo">
                        <div id="photo-placeholder" class="photo-preview-placeholder" hidden>No photo</div>
                    @else
                        <img id="photo-preview" alt="Photo preview" hidden>
                        <div id="photo-placeholder" class="photo-preview-placeholder">No photo</div>
                    @endif
                </div>
                <div class="photo-controls">
                    <label for="profile_photo">Profile photo</label>
                    <input id="profile_photo" type="file" name="profile_photo" accept="image/jpeg,image/png,image/webp" @class(['is-invalid' => $errors->has('profile_photo')])>
                    <p class="hint">JPG, PNG, or WebP. Maximum size 4 MB.</p>
                    @error('profile_photo')<p class="field-error">{{ $message }}</p>@enderror
                    @if($profile->profile_photo_path)
                        <label class="remove-photo-option" for="remove_profile_photo">
                            <input id="remove_profile_photo" type="checkbox" name="remove_profile_photo" value="1">
                            <span>Remove current photo</span>
                        </label>
                    @else
                        <label class="remove-photo-option" for="remove_profile_photo" id="remove-photo-wrap" hidden>
                            <input id="remove_profile_photo" type="checkbox" name="remove_profile_photo" value="1">
                            <span>Remove current photo</span>
                        </label>
                    @endif
                </div>
            </div>

            <div class="form-grid cols-2">
                <div class="field">
                    <label for="professional_title">Professional title</label>
                    <input id="professional_title" name="professional_title" value="{{ old('professional_title', $profile->professional_title) }}" required @class(['is-invalid' => $errors->has('professional_title')])>
                    @error('professional_title')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div class="field">
                    <label for="years_experience">Years of experience</label>
                    <input id="years_experience" type="number" name="years_experience" value="{{ old('years_experience', $profile->years_experience) }}" min="0" max="70" required @class(['is-invalid' => $errors->has('years_experience')])>
                    @error('years_experience')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div class="field">
                    <label for="consultation_fee">Consultation fee ({{ config('app.currency', 'PKR') }})</label>
                    <input id="consultation_fee" type="number" name="consultation_fee" value="{{ old('consultation_fee', $profile->consultation_fee) }}" min="0" step="0.01" max="99999999.99" placeholder="e.g. 2500.00" @class(['is-invalid' => $errors->has('consultation_fee')])>
                    <p class="hint">Shown on your public card/profile and used for consultation requests. Leave blank if not set.</p>
                    @error('consultation_fee')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div class="field">
                    <label for="city">City</label>
                    <input id="city" name="city" value="{{ old('city', $profile->city) }}" required @class(['is-invalid' => $errors->has('city')])>
                    @error('city')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div class="field">
                    <label for="workplace">Clinic or workplace</label>
                    <input id="workplace" name="workplace" value="{{ old('workplace', $profile->workplace) }}" @class(['is-invalid' => $errors->has('workplace')])>
                    @error('workplace')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div class="field">
                    <label for="workplace_address">Workplace address</label>
                    <input id="workplace_address" name="workplace_address" value="{{ old('workplace_address', $profile->workplace_address) }}" maxlength="255" @class(['is-invalid' => $errors->has('workplace_address')])>
                    @error('workplace_address')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div class="field">
                    <label for="timezone">Time zone</label>
                    <select id="timezone" name="timezone" @class(['is-invalid' => $errors->has('timezone')])>
                        @foreach($timezones as $zone)
                            <option value="{{ $zone }}" @selected(old('timezone', $profile->timezone ?: config('app.timezone')) === $zone)>{{ $zone }}</option>
                        @endforeach
                    </select>
                    @error('timezone')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div class="field">
                    <label for="languages">Languages (comma separated)</label>
                    <input id="languages" name="languages_text" value="{{ old('languages_text', implode(', ', $profile->languages ?? [])) }}" placeholder="English, Urdu" @class(['is-invalid' => $errors->has('languages_text')])>
                    <p class="hint">Shown on your public profile when published.</p>
                    @error('languages_text')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <input type="hidden" name="specialty" id="specialty" value="{{ old('specialty', $profile->specialty) }}">
            <input type="hidden" name="specialty_ids_present" value="1">
            <div class="field field-span-2" style="margin-top:.5rem;">
                <label id="specialties-label">Specialties <span class="hint">Select the specialties shown on your public profile.</span></label>
                <div class="specialty-options @if($errors->has('specialty') || $errors->has('specialty_ids') || $errors->has('specialty_ids.*')) is-invalid-block @endif" role="group" aria-labelledby="specialties-label" aria-describedby="specialty-error">
                    @forelse($specialtyOptions as $option)
                        <label class="inline-check"><input type="checkbox" name="specialty_ids[]" value="{{ $option->id }}" @checked(in_array($option->id, old('specialty_ids', $profile->specialties->modelKeys())))> {{ $option->name }}</label>
                    @empty
                        <p class="muted">No active specialties are configured yet. Ask an administrator to add options.</p>
                    @endforelse
                </div>
                @error('specialty')<p class="field-error" id="specialty-error">{{ $message }} Select or confirm a specialty in this section.</p>@enderror
                @error('specialty_ids')<p class="field-error">{{ $message }}</p>@enderror
                @error('specialty_ids.*')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-grid cols-2" style="margin-top:.5rem;">
                <div class="field">
                    <label for="qualification">Primary qualification (verification record)</label>
                    <input id="qualification" name="qualification" value="{{ old('qualification', $profile->qualification) }}" required @class(['is-invalid' => $errors->has('qualification')])>
                    @error('qualification')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div class="field">
                    <label for="institution">Primary institution</label>
                    <input id="institution" name="institution" value="{{ old('institution', $profile->institution) }}" required @class(['is-invalid' => $errors->has('institution')])>
                    @error('institution')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="field field-span-2" style="margin-top:.5rem;">
                <label>Additional qualifications for your profile</label>
                <div id="qualifications-list" class="qualification-block">
                    @foreach(old('qualifications', $profile->qualifications->map(fn($q) => ['degree'=>$q->degree,'institution'=>$q->institution,'completion_year'=>$q->completion_year])->all()) as $i => $qualification)
                        <div class="qualification-row">
                            <input aria-label="Degree or qualification" name="qualifications[{{ $i }}][degree]" placeholder="Degree or qualification" value="{{ $qualification['degree'] }}" @class(['is-invalid' => $errors->has("qualifications.$i.degree")])>
                            <input aria-label="Institution" name="qualifications[{{ $i }}][institution]" placeholder="Institution" value="{{ $qualification['institution'] }}" @class(['is-invalid' => $errors->has("qualifications.$i.institution")])>
                            <input aria-label="Completion year" name="qualifications[{{ $i }}][completion_year]" placeholder="Year" type="number" min="1950" max="{{ now()->year + 1 }}" value="{{ $qualification['completion_year'] }}" @class(['is-invalid' => $errors->has("qualifications.$i.completion_year")])>
                        </div>
                    @endforeach
                </div>
                @error('qualifications')<p class="field-error">{{ $message }}</p>@enderror
                @error('qualifications.*.degree')<p class="field-error">{{ $message }}</p>@enderror
                @error('qualifications.*.institution')<p class="field-error">{{ $message }}</p>@enderror
                @error('qualifications.*.completion_year')<p class="field-error">{{ $message }}</p>@enderror
                <div style="margin-top:.5rem;"><button class="btn btn-outline btn-small" id="add-qualification" type="button">Add qualification</button></div>
            </div>

            <div class="field field-span-2" style="margin-top:.5rem;">
                <label for="biography">Professional biography</label>
                <textarea id="biography" name="biography" rows="5" maxlength="2000" @class(['is-invalid' => $errors->has('biography')])>{{ old('biography', $profile->biography) }}</textarea>
                @error('biography')<p class="field-error">{{ $message }}</p>@enderror
            </div>
        </section>

        <section class="doctor-panel form-section">
            <h2>License information</h2>
            <p class="section-desc">Verification details used for your professional approval status.</p>
            <div class="form-grid cols-2">
                <div class="field">
                    <label for="license_number">License number</label>
                    <input id="license_number" name="license_number" value="{{ old('license_number', $profile->license_number) }}" required @class(['is-invalid' => $errors->has('license_number')])>
                    @error('license_number')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div class="field">
                    <label for="issuing_authority">Issuing authority</label>
                    <input id="issuing_authority" name="issuing_authority" value="{{ old('issuing_authority', $profile->issuing_authority) }}" required @class(['is-invalid' => $errors->has('issuing_authority')])>
                    @error('issuing_authority')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div class="field">
                    <label for="license_expires_at">Expiry date (optional)</label>
                    <input id="license_expires_at" type="date" name="license_expires_at" value="{{ old('license_expires_at', $profile->license_expires_at?->format('Y-m-d')) }}" @class(['is-invalid' => $errors->has('license_expires_at')])>
                    @error('license_expires_at')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <div class="form-actions">
            <button class="btn btn-blue" type="submit">Save professional profile</button>
        </div>
    </form>
</div>

<script>
document.getElementById('profile_photo').addEventListener('change', function(e){
  const f=e.target.files[0], img=document.getElementById('photo-preview'), placeholder=document.getElementById('photo-placeholder'), remove=document.getElementById('remove_profile_photo'), removeWrap=document.getElementById('remove-photo-wrap');
  if(f){
    img.src=URL.createObjectURL(f);
    img.hidden=false;
    img.style.display='block';
    if(placeholder) placeholder.hidden=true;
    if(remove) remove.checked=false;
    if(removeWrap) removeWrap.hidden=false;
  } else {
    img.hidden=true;
    img.removeAttribute('src');
    if(placeholder) placeholder.hidden=false;
  }
});
const specialtyValue = document.getElementById('specialty');
const specialtyOptions = document.querySelectorAll('.specialty-options input[name="specialty_ids[]"]');
function syncSpecialty(){
  const selected = document.querySelector('.specialty-options input[name="specialty_ids[]"]:checked');
  if (selected) specialtyValue.value = selected.parentElement.textContent.trim();
}
specialtyOptions.forEach(function(option){ option.addEventListener('change', syncSpecialty); });
syncSpecialty();
document.getElementById('add-qualification').addEventListener('click',function(){const list=document.getElementById('qualifications-list'),i=list.querySelectorAll('.qualification-row').length,row=document.createElement('div');row.className='qualification-row';row.innerHTML='<input aria-label="Degree or qualification" name="qualifications['+i+'][degree]" placeholder="Degree or qualification"><input aria-label="Institution" name="qualifications['+i+'][institution]" placeholder="Institution"><input aria-label="Completion year" name="qualifications['+i+'][completion_year]" placeholder="Year" type="number" min="1950" max="{{ now()->year + 1 }}">';list.appendChild(row)});
</script>
@endsection
