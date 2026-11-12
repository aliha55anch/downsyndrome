@extends('layouts.doctor')
@section('title', 'Verification overview')
@section('heading', 'Verification overview')
@section('doctor-content')
<section class="doctor-welcome-card"><div><span class="eyebrow">Welcome, {{ $user->name }}</span><h2>{{ $profile->professional_title }} · {{ $profile->specialty }}</h2><p>Submitted {{ $profile->submitted_at?->format('M j, Y') ?? 'not submitted' }}@if($profile->reviewed_at) · Last reviewed {{ $profile->reviewed_at->format('M j, Y') }}@endif</p></div><span class="doctor-status status-{{ $profile->status }}">{{ ucwords(str_replace('_', ' ', $profile->status)) }}</span></section>

@if($profile->status === 'rejected')<section class="doctor-panel doctor-callout is-error"><h2>Changes requested</h2><p>{{ $profile->rejection_reason ?: 'Please review the document feedback below and update your application.' }}</p><a class="btn btn-outline" href="{{ route('doctor.profile.edit') }}">Review professional profile</a></section>@endif
@if($profile->status === 'suspended')<section class="doctor-panel doctor-callout is-error"><h2>Verified access is suspended</h2><p>{{ $profile->suspension_reason ?: 'Contact the platform administrator for further information.' }}</p></section>@endif
@if($profile->status === 'pending')<section class="doctor-panel doctor-callout"><h2>Application pending</h2><p>Your account is not verified yet. An administrator must review your professional information and approve your required documents.</p>@if($missingRequirements)<p><strong>Still needed:</strong> {{ implode(', ', $missingRequirements) }}.</p>@endif</section>@endif
@if($profile->status === 'under_review')<section class="doctor-panel doctor-callout"><h2>Application under review</h2><p>An administrator is reviewing your information and documents. We’ll show status updates here.</p></section>@endif
@if($isApproved)<section class="doctor-panel doctor-callout is-success"><h2>Verified doctor</h2><p>Your professional verification is approved and active.</p></section>@endif

<section class="doctor-panel doctor-profile-preview-card"><div><span class="eyebrow">Patient-facing profile</span><h2>See your public profile</h2><p>Preview the information patients see in the doctor directory, within your dashboard.</p></div><a class="btn btn-outline" href="{{ route('doctor.profile.preview') }}">Preview profile</a></section>

<div class="doctor-dashboard-grid">
    <section class="doctor-panel"><div class="panel-heading"><div><span class="eyebrow">Professional information</span><h2>Application details</h2></div><a class="btn btn-outline btn-small" href="{{ route('doctor.profile.edit') }}">Edit profile</a></div>
        <dl class="doctor-detail-grid"><div><dt>Qualification</dt><dd>{{ $profile->qualification }} · {{ $profile->institution }}</dd></div><div><dt>Experience</dt><dd>{{ $profile->years_experience }} years</dd></div><div><dt>Workplace</dt><dd>{{ $profile->workplace ?: 'Not provided' }}</dd></div><div><dt>City</dt><dd>{{ $profile->city }}</dd></div><div><dt>Registration</dt><dd>{{ $profile->license_number }} · {{ $profile->issuing_authority }}</dd></div><div><dt>License expiry</dt><dd>{{ $profile->license_expires_at?->format('M j, Y') ?? 'Not provided' }}</dd></div></dl>
    </section>
    <section class="doctor-panel"><div class="panel-heading"><div><span class="eyebrow">Verification files</span><h2>Your documents</h2></div></div>
        @forelse($profile->currentDocuments as $document)
            <article class="doctor-document-row"><div><strong>{{ ucfirst($document->document_type) }} document</strong><p>{{ $document->original_name }} · version {{ $document->version }}</p><span class="doctor-status status-{{ $document->status }}">{{ ucfirst($document->status) }}</span>@if($document->rejection_reason)<p class="field-error">{{ $document->rejection_reason }}</p>@endif</div><a class="btn btn-outline btn-small" href="{{ route('doctor.documents.download', $document) }}">Download</a></article>
        @empty<p class="muted">No verification documents have been submitted.</p>@endforelse
        <form class="doctor-upload-form" method="POST" action="{{ route('doctor.documents.store') }}" enctype="multipart/form-data">@csrf<label for="document_type">Upload or replace a document</label><select id="document_type" name="document_type" required><option value="license">Medical license</option><option value="qualification">Qualification certificate</option><option value="additional">Additional certificate</option></select><input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" required><small>PDF or JPG/PNG, maximum 10 MB. Previous versions are retained for review history.</small><button class="btn btn-blue btn-small" type="submit">Upload document</button></form>
    </section>
</div>

<section class="doctor-panel" id="verification"><div class="panel-heading"><div><span class="eyebrow">Verification process</span><h2>Profile completion</h2></div></div>@if(count($missingRequirements))<p>Complete or replace these items:</p><ul>@foreach($missingRequirements as $requirement)<li>{{ ucfirst($requirement) }}</li>@endforeach</ul><a class="btn btn-outline btn-small" href="{{ route('doctor.profile.edit') }}">Complete professional profile</a>@else<p class="doctor-complete-note">Your required profile details and verification files are present.</p>@endif</section>

@if($profile->status === 'rejected')<form method="POST" action="{{ route('doctor.application.resubmit') }}" class="doctor-resubmit" onsubmit="return confirm('Resubmit your application for administrator review?')">@csrf<button class="btn btn-blue" type="submit">Resubmit application</button></form>@endif

<section class="doctor-panel"><div class="panel-heading"><div><span class="eyebrow">Audit record</span><h2>Verification history</h2></div></div>@forelse($profile->history as $event)<article class="doctor-history-row"><div><strong>{{ ucwords(str_replace('_', ' ', $event->event_type)) }}</strong><p>{{ $event->reason }}</p><small>{{ $event->created_at?->format('M j, Y g:i A') }} · {{ $event->actor?->name ?? 'System' }}</small></div>@if($event->to_status)<span class="doctor-status status-{{ $event->to_status }}">{{ ucwords(str_replace('_', ' ', $event->to_status)) }}</span>@endif</article>@empty<p class="muted">No review actions recorded yet.</p>@endforelse</section>
@endsection
