@extends('layouts.admin')

@section('title', 'Review doctor application')

@section('content')
<div class="page-title admin-dashboard-heading doctor-review-heading">
    <div>
        <a class="doctor-review-back-link" href="{{ route('admin.doctors.index') }}"><span aria-hidden="true">←</span> Doctor Management</a>
        <span class="eyebrow">Professional application review</span>
        <h1>{{ $profile->user->name }}</h1>
        <p>{{ $profile->user->email }} <span aria-hidden="true">·</span> Submitted {{ $profile->submitted_at?->format('M j, Y') }}</p>
    </div>
    <div class="doctor-review-heading-status"><span class="doctor-status status-{{ $profile->status }}">{{ ucwords(str_replace('_', ' ', $profile->status)) }}</span><span class="doctor-visibility-status {{ $profile->is_public ? 'is-public' : 'is-hidden' }}">{{ $profile->is_public ? 'Public profile' : 'Hidden profile' }}</span></div>
</div>

<div class="doctor-admin-review-grid doctor-review-primary-grid">
    <section class="doctor-panel doctor-review-profile-panel">
        <div class="doctor-review-panel-heading"><div><span class="eyebrow">Applicant profile</span><h2>Account and professional information</h2></div></div>
        <dl class="doctor-detail-grid">
            <div><dt>Phone</dt><dd>{{ $profile->phone ?: 'Not provided' }}</dd></div>
            <div><dt>Title / specialty</dt><dd>{{ $profile->professional_title }} · {{ $profile->specialty }}</dd></div>
            <div><dt>Selected specialties</dt><dd>{{ $profile->specialties->pluck('name')->join(', ') ?: 'None selected' }}</dd></div>
            <div><dt>Qualification</dt><dd>{{ $profile->qualification }}</dd></div>
            <div><dt>Institution</dt><dd>{{ $profile->institution }}</dd></div>
            <div><dt>Experience</dt><dd>{{ $profile->years_experience }} years</dd></div>
            <div><dt>Consultation fee</dt><dd>@if($profile->consultation_fee !== null){{ config('app.currency', 'PKR') }} {{ number_format((float)$profile->consultation_fee, 2) }}@else Not set @endif</dd></div>
            <div><dt>Workplace</dt><dd>{{ $profile->workplace ?: 'Not provided' }}</dd></div>
            <div><dt>City</dt><dd>{{ $profile->city }}</dd></div>
            <div><dt>License</dt><dd>{{ $profile->license_number }} · {{ $profile->issuing_authority }}</dd></div>
            <div><dt>License expiry</dt><dd>{{ $profile->license_expires_at?->format('M j, Y') ?? 'Not provided' }}</dd></div>
            <div><dt>Email verification</dt><dd><span class="doctor-email-verification {{ $profile->user->hasVerifiedEmail() ? 'is-verified' : 'is-unverified' }}">{{ $profile->user->hasVerifiedEmail() ? 'Verified' : 'Not verified' }}</span></dd></div>
        </dl>
        @if($profile->qualifications->isNotEmpty())
            <div class="doctor-review-subsection"><h3>Profile qualifications</h3><ul>@foreach($profile->qualifications as $qualification)<li>{{ $qualification->degree }} — {{ $qualification->institution }}@if($qualification->completion_year), {{ $qualification->completion_year }}@endif</li>@endforeach</ul></div>
        @endif
        @if($profile->biography)
            <div class="doctor-review-subsection"><h3>Introduction</h3><p>{{ $profile->biography }}</p></div>
        @endif
    </section>

    <section class="doctor-panel doctor-review-documents-panel">
        <div class="doctor-review-panel-heading"><div><span class="eyebrow">Identity and credentials</span><h2>Verification documents</h2><p>Required files approved: <strong>{{ $requiredDocumentsApproved ? 'Yes' : 'No' }}</strong></p></div></div>
        <div class="doctor-review-document-list">
            @forelse($profile->documents->sortByDesc('created_at') as $document)
                <article class="doctor-document-row {{ $document->is_current ? '' : 'is-old-version' }}">
                    <div class="doctor-document-info"><div class="doctor-document-title"><strong>{{ ucfirst($document->document_type) }} · version {{ $document->version }}{{ $document->is_current ? '' : ' (replaced)' }}</strong><span class="doctor-status status-{{ $document->status }}">{{ ucfirst($document->status) }}</span></div><p>{{ $document->original_name }} · {{ number_format($document->size_bytes / 1024, 0) }} KB · {{ $document->created_at->format('M j, Y') }}</p>@if($document->rejection_reason)<p class="field-error">{{ $document->rejection_reason }}</p>@endif</div>
                    <div class="doctor-admin-document-actions">
                        <a class="btn btn-outline btn-small" href="{{ route('doctor.documents.download', $document) }}">Download privately</a>
                        @if($document->is_current)
                            <form method="POST" action="{{ route('admin.doctors.documents.review', [$profile, $document]) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="approved"><button class="btn btn-blue btn-small" type="submit" onclick="return confirm('Approve this current document?')">Approve document</button></form>
                            <form method="POST" action="{{ route('admin.doctors.documents.review', [$profile, $document]) }}" class="doctor-reject-document">@csrf @method('PATCH')<input type="hidden" name="status" value="rejected"><label class="sr-only" for="reason-{{ $document->id }}">Reason for replacement</label><input id="reason-{{ $document->id }}" name="reason" placeholder="Reason required to request replacement" required><button class="btn btn-outline btn-small" type="submit" onclick="return confirm('Reject this document and request a replacement?')">Request replacement</button></form>
                        @endif
                    </div>
                </article>
            @empty
                <div class="empty-state"><h3>No verification documents</h3><p>The applicant has not uploaded any documents yet.</p></div>
            @endforelse
        </div>
    </section>
</div>

<div class="doctor-review-secondary-grid">
    <section class="doctor-panel doctor-review-visibility-panel">
        <div class="doctor-review-panel-heading"><div><span class="eyebrow">Public access</span><h2>Profile visibility</h2></div><span class="doctor-visibility-status {{ $profile->is_public ? 'is-public' : 'is-hidden' }}">{{ $profile->is_public ? 'Published' : 'Hidden' }}</span></div>
        <p>Publishing is separate from verification. Only an approved, active doctor can be published. Pending, rejected, and suspended doctors are not shown publicly.</p>
        <div class="doctor-visibility-actions"><form method="POST" action="{{ route('admin.doctors.visibility', $profile) }}">@csrf @method('PATCH')<input type="hidden" name="is_public" value="{{ $profile->is_public ? 0 : 1 }}"><button class="btn {{ $profile->is_public ? 'btn-outline' : 'btn-blue' }}" @disabled(!$profile->is_public && ($profile->status !== 'approved' || $profile->user->role !== 'approved_doctor'))>{{ $profile->is_public ? 'Hide public profile' : 'Publish profile' }}</button></form>@if($profile->is_public && $profile->status === 'approved')<a class="btn btn-outline" href="{{ route('doctors.show', $profile) }}" target="_blank" rel="noopener">Open public profile</a>@endif</div>
    </section>

    <section class="doctor-panel doctor-review-availability-panel">
        <div class="doctor-review-panel-heading"><div><span class="eyebrow">Consultation setup</span><h2>Consultation fee & availability</h2></div></div>
        <p class="doctor-review-timezone">Time zone: <strong>{{ $profile->timezone }}</strong></p>
        <form method="POST" action="{{ route('admin.doctors.consultation-fee', $profile) }}" class="doctor-form doctor-fee-form">
            @csrf
            @method('PATCH')
            <label for="consultation_fee">Consultation fee ({{ config('app.currency', 'PKR') }})</label>
            <input id="consultation_fee" type="number" name="consultation_fee" value="{{ old('consultation_fee', $profile->consultation_fee) }}" min="0" step="0.01" max="99999999.99" placeholder="e.g. 2500.00">
            <button class="btn btn-blue btn-small" type="submit">Update consultation fee</button>
        </form>
        <p><a class="btn btn-outline btn-small" href="{{ route('admin.consultations.index', ['doctor_profile_id' => $profile->id]) }}">View consultation requests for this doctor</a></p>
        <div class="doctor-review-schedule-grid">
            <div><h3>Consultation options</h3><ul>@forelse($profile->consultationOptions as $option)<li>{{ ucfirst(str_replace('_', ' ', $option->type)) }} · {{ $option->duration_minutes }} min · {{ config('app.currency', 'PKR') }} {{ number_format((float)$option->fee, 2) }} · {{ $option->is_active ? 'Active' : 'Inactive' }}</li>@empty<li>Not configured</li>@endforelse</ul></div>
            <div><h3>Weekly hours</h3><ul>@forelse($profile->availabilityPeriods as $period)<li>{{ ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'][$period->day_of_week] }}: {{ \Carbon\CarbonImmutable::parse($period->starts_at)->format('g:i A') }}–{{ \Carbon\CarbonImmutable::parse($period->ends_at)->format('g:i A') }} ({{ $period->breaks->count() }} break(s))</li>@empty<li>Not configured</li>@endforelse</ul></div>
        </div>
        <p class="hint">{{ $profile->unavailablePeriods->where('ends_at', '>=', now())->count() }} future unavailable period(s).</p>
    </section>
</div>

<section class="doctor-panel doctor-review-actions doctor-admin-decision-panel">
    <div class="doctor-review-panel-heading"><div><span class="eyebrow">Review decision</span><h2>Application decision</h2><p>Every status change is recorded with the administrator and timestamp. Approval requires current documents and a verified email.</p></div></div>
    <div class="doctor-admin-decision-grid">
        @if(in_array($profile->status, ['pending', 'under_review'], true))
            <form method="POST" action="{{ route('admin.doctors.status', $profile) }}" class="doctor-decision-card">@csrf @method('PATCH')<input type="hidden" name="status" value="under_review"><strong>Review in progress</strong><p>Keep this application open while you check the submitted details.</p><button class="btn btn-outline" type="submit">Mark under review</button></form>
        @endif
        @if(in_array($profile->status, ['pending', 'under_review', 'rejected', 'suspended'], true))
            <form method="POST" action="{{ route('admin.doctors.status', $profile) }}" class="doctor-decision-card">@csrf @method('PATCH')<input type="hidden" name="status" value="approved"><strong>Approve professional access</strong><p>{{ $requiredDocumentsApproved && $profile->user->hasVerifiedEmail() ? 'Required checks are complete.' : 'Approval is unavailable until required documents and email verification are complete.' }}</p><button class="btn btn-blue" type="submit" onclick="return confirm('Approve this doctor after checking the information and documents?')" @disabled(!$requiredDocumentsApproved || !$profile->user->hasVerifiedEmail())>Approve doctor</button></form>
        @endif
        @if(in_array($profile->status, ['pending', 'under_review'], true))
            <form method="POST" action="{{ route('admin.doctors.status', $profile) }}" class="doctor-decision-card doctor-decision-form" onsubmit="return confirm('Reject this doctor application?')">@csrf @method('PATCH')<input type="hidden" name="status" value="rejected"><strong>Reject application</strong><label for="rejection-reason">Reason for rejection</label><textarea id="rejection-reason" name="reason" required maxlength="2000">{{ old('reason') }}</textarea><button class="btn btn-outline" type="submit">Reject application</button></form>
        @endif
        @if($profile->status === 'approved')
            <form method="POST" action="{{ route('admin.doctors.status', $profile) }}" class="doctor-decision-card doctor-decision-form" onsubmit="return confirm('Suspend verified doctor access?')">@csrf @method('PATCH')<input type="hidden" name="status" value="suspended"><strong>Suspend verified access</strong><label for="suspension-reason">Reason for suspension</label><textarea id="suspension-reason" name="reason" required maxlength="2000"></textarea><button class="btn btn-outline" type="submit">Suspend doctor</button></form>
        @endif
    </div>
</section>

<section class="doctor-panel doctor-review-history-panel">
    <div class="doctor-review-panel-heading"><div><span class="eyebrow">Audit trail</span><h2>Verification history</h2><p>Recorded application and document decisions.</p></div></div>
    @forelse($profile->history as $event)
        <article class="doctor-history-row"><div><strong>{{ ucwords(str_replace('_', ' ', $event->event_type)) }}@if($event->from_status || $event->to_status) · {{ $event->from_status ?: 'new' }} → {{ $event->to_status }}@endif</strong><p>{{ $event->reason ?: 'No additional notes.' }}</p><small>{{ $event->created_at?->format('M j, Y g:i A') }} · {{ $event->actor?->name ?? 'System' }}</small></div></article>
    @empty
        <p class="muted">No actions have been recorded.</p>
    @endforelse
</section>
@endsection
