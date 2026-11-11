<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DoctorProfile;
use App\Models\DoctorVerificationDocument;
use App\Models\User;
use App\Notifications\DoctorVerificationNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DoctorManagementController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');
        $specialty = trim((string) $request->query('specialty', ''));
        $city = trim((string) $request->query('city', ''));
        if (! in_array($status, DoctorProfile::STATUSES, true)) $status = '';

        $profiles = DoctorProfile::query()->with(['user', 'specialties'])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($search !== '', fn ($query) => $query->whereHas('user', fn ($users) => $users->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
            ->when($specialty !== '', fn ($query) => $query->where(function ($matches) use ($specialty): void {
                $matches->where('specialty', 'like', "%{$specialty}%")
                    ->orWhereHas('specialties', fn ($selected) => $selected->where('medical_specialties.name', 'like', "%{$specialty}%"));
            }))
            ->when($city !== '', fn ($query) => $query->where('city', 'like', "%{$city}%"))
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 WHEN status = 'under_review' THEN 1 ELSE 2 END")
            ->latest('submitted_at')->paginate(20)->withQueryString();

        return view('admin.doctors.index', compact('profiles', 'search', 'status', 'specialty', 'city'));
    }

    public function show(DoctorProfile $doctorProfile): View
    {
        return view('admin.doctors.show', [
            'profile' => $doctorProfile->load(['user', 'documents.reviewer', 'reviewer', 'history.actor', 'specialties', 'qualifications', 'consultationOptions', 'availabilityPeriods.breaks', 'unavailablePeriods']),
            'requiredDocumentsApproved' => $this->requiredDocumentsApproved($doctorProfile),
        ]);
    }

    public function updateStatus(Request $request, DoctorProfile $doctorProfile): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:under_review,approved,rejected,suspended'],
            'reason' => ['nullable', 'string', 'max:2000', 'required_if:status,rejected,suspended'],
        ]);
        $target = $data['status'];
        $wasReinstated = false;
        $profile = DB::transaction(function () use ($request, $doctorProfile, $data, $target, &$wasReinstated): DoctorProfile {
            $profile = DoctorProfile::whereKey($doctorProfile->id)->lockForUpdate()->firstOrFail();
            $from = $profile->status;

            if ($target === DoctorProfile::STATUS_UNDER_REVIEW) {
                abort_unless(in_array($from, [DoctorProfile::STATUS_PENDING, DoctorProfile::STATUS_UNDER_REVIEW], true), 409, 'Only pending applications can be moved into review.');
                abort_unless($this->hasRequiredCurrentDocuments($profile), 422, 'The application is missing a current license or qualification document.');
            }
            if ($target === DoctorProfile::STATUS_APPROVED) {
                abort_unless(in_array($from, [DoctorProfile::STATUS_PENDING, DoctorProfile::STATUS_UNDER_REVIEW, DoctorProfile::STATUS_REJECTED, DoctorProfile::STATUS_SUSPENDED], true), 409);
                abort_unless($this->requiredDocumentsApproved($profile), 422, 'Approve the current license and qualification documents before approving this doctor.');
                abort_if($profile->license_expires_at?->isPast(), 422, 'This professional license has expired.');
                abort_unless($profile->user()->first()?->hasVerifiedEmail(), 422, 'The doctor must verify their email before approval.');
                $wasReinstated = $from === DoctorProfile::STATUS_SUSPENDED;
            }
            if ($target === DoctorProfile::STATUS_REJECTED) {
                abort_unless(in_array($from, [DoctorProfile::STATUS_PENDING, DoctorProfile::STATUS_UNDER_REVIEW], true), 409, 'Approved doctors must be suspended instead of rejected.');
            }
            if ($target === DoctorProfile::STATUS_SUSPENDED) {
                abort_unless($from === DoctorProfile::STATUS_APPROVED, 409, 'Only approved doctors can be suspended.');
            }

            $profile->status = $target;
            if ($target !== DoctorProfile::STATUS_APPROVED) {
                $profile->is_public = false;
            }
            $profile->reviewed_by = $request->user()->id;
            $profile->reviewed_at = now();
            $profile->rejection_reason = $target === DoctorProfile::STATUS_REJECTED ? $data['reason'] : null;
            $profile->suspension_reason = $target === DoctorProfile::STATUS_SUSPENDED ? $data['reason'] : null;
            $profile->save();
            $profile->user()->firstOrFail()->forceFill([
                'role' => $target === DoctorProfile::STATUS_APPROVED ? User::ROLE_APPROVED_DOCTOR : User::ROLE_PENDING_DOCTOR,
            ])->save();
            $profile->history()->create([
                'actor_id' => $request->user()->id,
                'event_type' => $target === DoctorProfile::STATUS_APPROVED && $from === DoctorProfile::STATUS_SUSPENDED ? 'doctor_reinstated' : 'status_changed',
                'from_status' => $from,
                'to_status' => $target,
                'reason' => $data['reason'] ?? null,
                'created_at' => now(),
            ]);

            return $profile->load('user');
        });

        $messages = [
            'under_review' => 'Your doctor application is now being reviewed.',
            'approved' => 'Your doctor verification was approved. Your verified profile is now active.',
            'rejected' => 'Your doctor application was rejected. Review the reason and replace any documents that need attention.',
            'suspended' => 'Your approved doctor profile has been suspended. Contact the platform administrator for help.',
        ];
        $profile->user->notify(new DoctorVerificationNotification(
            $wasReinstated ? 'Doctor access reinstated' : 'Doctor verification: '.ucwords(str_replace('_', ' ', $target)),
            $wasReinstated ? 'Your suspension has been lifted and your verified doctor profile is active again.' : $messages[$target],
            route('doctor.dashboard'),
        ));

        return back()->with('status', 'Doctor application status updated to '.str_replace('_', ' ', $target).'.');
    }

    public function reviewDocument(Request $request, DoctorProfile $doctorProfile, DoctorVerificationDocument $document): RedirectResponse
    {
        abort_unless($document->doctor_profile_id === $doctorProfile->id && $document->is_current, 404);
        $data = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'reason' => ['nullable', 'string', 'max:2000', 'required_if:status,rejected'],
        ]);

        if ($data['status'] === 'approved') {
            abort_unless(Storage::disk('local')->exists($document->storage_path), 422, 'This document file is missing from private storage.');
        }

        DB::transaction(function () use ($request, $document, $doctorProfile, $data): void {
            $lockedProfile = DoctorProfile::whereKey($doctorProfile->id)->lockForUpdate()->firstOrFail();
            $from = $lockedProfile->status;
            $document->update([
                'status' => $data['status'],
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'rejection_reason' => $data['status'] === 'rejected' ? $data['reason'] : null,
            ]);
            $lockedProfile->history()->create([
                'actor_id' => $request->user()->id,
                'event_type' => 'document_'.$data['status'],
                'from_status' => $lockedProfile->status,
                'to_status' => $lockedProfile->status,
                'reason' => $data['reason'] ?? null,
                'details' => ['document_id' => $document->id, 'document_type' => $document->document_type, 'version' => $document->version],
                'created_at' => now(),
            ]);

            if ($data['status'] === 'rejected' && $from === DoctorProfile::STATUS_APPROVED) {
                $lockedProfile->update([
                    'status' => DoctorProfile::STATUS_SUSPENDED,
                    'reviewed_by' => $request->user()->id,
                    'reviewed_at' => now(),
                    'suspension_reason' => $data['reason'],
                ]);
                $lockedProfile->user()->firstOrFail()->forceFill(['role' => User::ROLE_PENDING_DOCTOR])->save();
                $lockedProfile->history()->create([
                    'actor_id' => $request->user()->id,
                    'event_type' => 'doctor_suspended_document_review',
                    'from_status' => $from,
                    'to_status' => DoctorProfile::STATUS_SUSPENDED,
                    'reason' => $data['reason'],
                    'created_at' => now(),
                ]);
            }
        });

        $doctorProfile->refresh()->user->notify(new DoctorVerificationNotification(
            $data['status'] === 'approved' ? 'Document approved' : 'Document replacement required',
            $data['status'] === 'approved' ? 'One of your submitted verification documents has been approved.' : 'One verification document was not accepted. Open your dashboard for the reviewer feedback and upload a replacement.',
            route('doctor.dashboard'),
        ));

        return back()->with('status', 'The verification document was marked '.$data['status'].'.');
    }

    private function hasRequiredCurrentDocuments(DoctorProfile $profile): bool
    {
        return $profile->documents()->where('is_current', true)->whereIn('document_type', ['license', 'qualification'])
            ->whereIn('status', ['pending', 'approved'])->distinct()->count('document_type') === 2;
    }

    private function requiredDocumentsApproved(DoctorProfile $profile): bool
    {
        $documents = $profile->documents()->where('is_current', true)->whereIn('document_type', ['license', 'qualification'])
            ->where('status', 'approved')->get()->keyBy('document_type');

        foreach (['license', 'qualification'] as $type) {
            if (! isset($documents[$type]) || ! Storage::disk('local')->exists($documents[$type]->storage_path)) {
                return false;
            }
        }

        return true;
    }
}
