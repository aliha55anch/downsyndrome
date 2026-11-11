<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\DoctorProfile;
use App\Models\User;
use App\Models\MedicalSpecialty;
use App\Notifications\DoctorVerificationNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $profile = $request->user()->doctorProfile()->with(['specialties', 'qualifications'])->firstOrFail();
        return view('doctor.profile', [
            'user' => $request->user(), 'profile' => $profile,
            'specialtyOptions' => MedicalSpecialty::where('is_active', true)->orderBy('name')->get(),
            'timezones' => timezone_identifiers_list(),
        ]);
    }

    public function preview(Request $request): View
    {
        $profile = $request->user()->doctorProfile()->with([
            'user', 'specialties', 'qualifications', 'consultationOptions', 'availabilityPeriods.breaks',
        ])->firstOrFail();

        return view('doctor.profile-preview', compact('profile'));
    }

    public function update(Request $request): RedirectResponse
    {
        $profile = $request->user()->doctorProfile()->firstOrFail();
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[+0-9() .-]{7,30}$/'],
            'professional_title' => ['required', 'string', 'max:120'],
            'specialty' => ['required', 'string', 'max:120'],
            'qualification' => ['required', 'string', 'max:180'],
            'institution' => ['required', 'string', 'max:180'],
            'years_experience' => ['required', 'integer', 'min:0', 'max:70'],
            'workplace' => ['nullable', 'string', 'max:180'],
            'city' => ['required', 'string', 'max:120'],
            'biography' => ['nullable', 'string', 'max:2000'],
            'license_number' => ['required', 'string', 'max:120', 'unique:doctor_profiles,license_number,'.$profile->id],
            'issuing_authority' => ['required', 'string', 'max:180'],
            'license_expires_at' => ['nullable', 'date', 'after:today'],
            'workplace_address' => ['nullable', 'string', 'max:255'],
            'timezone' => ['sometimes', 'nullable', 'timezone'],
            'languages_text' => ['nullable', 'string', 'max:500'],
            'specialty_ids' => ['nullable', 'array', 'max:8'],
            'specialty_ids.*' => ['integer', 'distinct', 'exists:medical_specialties,id,is_active,1'],
            'specialty_ids_present' => ['sometimes', 'boolean'],
            'qualifications' => ['nullable', 'array', 'max:10'],
            'qualifications.*.degree' => ['required_with:qualifications', 'string', 'max:180'],
            'qualifications.*.institution' => ['required_with:qualifications', 'string', 'max:180'],
            'qualifications.*.completion_year' => ['nullable', 'integer', 'min:1950', 'max:'.(now()->year + 1)],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:max_width=6000,max_height=6000'],
            'remove_profile_photo' => ['nullable', 'boolean'],
        ]);
        if (array_key_exists('languages_text', $data)) {
            $languages = collect(explode(',', (string) $data['languages_text']))->map(fn (string $language) => trim($language))->filter()->unique(fn ($language) => mb_strtolower($language))->values();
            if ($languages->count() > 12 || $languages->contains(fn ($language) => mb_strlen($language) > 60)) {
                throw \Illuminate\Validation\ValidationException::withMessages(['languages_text' => 'Enter up to 12 languages, each no longer than 60 characters.']);
            }
            $data['languages'] = $languages->all();
            unset($data['languages_text']);
        }
        $user = $request->user();
        $statusChanged = false;
        $criticalChanged = false;
        $from = $profile->status;

        $newPhotoPath = $request->file('profile_photo')?->store('doctor-photos', 'local');
        $oldPhotoPath = $profile->profile_photo_path;
        $specialtiesChanged = false;
        if ($data['specialty_ids_present'] ?? false) {
            $currentSpecialtyIds = $profile->specialties()->pluck('medical_specialties.id')->map(fn ($id) => (int) $id)->sort()->values()->all();
            $submittedSpecialtyIds = collect($data['specialty_ids'] ?? [])->map(fn ($id) => (int) $id)->sort()->values()->all();
            $specialtiesChanged = $currentSpecialtyIds !== $submittedSpecialtyIds;
        }
        DB::transaction(function () use ($data, $user, $profile, &$criticalChanged, &$statusChanged, &$from, $newPhotoPath, $specialtiesChanged): void {
            $profile = DoctorProfile::whereKey($profile->id)->lockForUpdate()->firstOrFail();
            $criticalFields = ['professional_title', 'specialty', 'qualification', 'institution', 'license_number', 'issuing_authority', 'license_expires_at'];
            $criticalChanged = $specialtiesChanged || collect($criticalFields)->contains(function ($field) use ($profile, $data): bool {
                $currentValue = $field === 'license_expires_at'
                    ? $profile->license_expires_at?->toDateString()
                    : $profile->{$field};

                return (string) $currentValue !== (string) ($data[$field] ?? '');
            });
            $from = $profile->status;
            $statusChanged = $criticalChanged && in_array($profile->status, [DoctorProfile::STATUS_APPROVED, DoctorProfile::STATUS_UNDER_REVIEW], true);
            $user->forceFill([
                'first_name' => $data['first_name'], 'last_name' => $data['last_name'],
                'name' => $data['first_name'].' '.$data['last_name'],
            ])->save();
            $profile->fill(collect($data)->except(['first_name', 'last_name', 'specialty_ids', 'qualifications', 'profile_photo', 'remove_profile_photo'])->all());
            if ($newPhotoPath) {
                $profile->profile_photo_path = $newPhotoPath;
            } elseif (($data['remove_profile_photo'] ?? false) && $profile->profile_photo_path) {
                $profile->profile_photo_path = null;
            }
            if ($statusChanged) {
                $profile->status = DoctorProfile::STATUS_PENDING;
                $profile->is_public = false;
                $profile->reviewed_by = null;
                $profile->reviewed_at = null;
                $profile->rejection_reason = null;
                $profile->suspension_reason = null;
            }
            $profile->save();
            if ($data['specialty_ids_present'] ?? false) {
                $profile->specialties()->sync($data['specialty_ids'] ?? []);
            }
            if (array_key_exists('qualifications', $data)) {
                $profile->qualifications()->delete();
                foreach (array_values($data['qualifications'] ?? []) as $index => $qualification) {
                    $profile->qualifications()->create($qualification + ['sort_order' => $index]);
                }
            }

            if ($criticalChanged) {
                $profile->currentDocuments()->update(['status' => 'pending', 'reviewed_by' => null, 'reviewed_at' => null, 'rejection_reason' => null]);
            }
            if ($statusChanged) {
                $user->forceFill(['role' => User::ROLE_PENDING_DOCTOR])->save();
            }
            $profile->history()->create([
                'actor_id' => $user->id,
                'event_type' => $statusChanged ? 'profile_changed_re_review' : 'profile_updated',
                'from_status' => $from,
                'to_status' => $profile->status,
                'reason' => $statusChanged ? 'A critical professional detail changed; verification is required again.' : null,
                'details' => ['critical_fields_changed' => $criticalChanged],
                'created_at' => now(),
            ]);
        });

        if (($newPhotoPath || ($data['remove_profile_photo'] ?? false)) && $oldPhotoPath && $oldPhotoPath !== $profile->fresh()->profile_photo_path) {
            Storage::disk('local')->delete($oldPhotoPath);
        }

        if ($statusChanged) {
            $user->notify(new DoctorVerificationNotification('Verification review required', 'A professional detail changed, so your application is pending review again.', route('doctor.dashboard')));
        }

        return back()->with('status', $statusChanged
            ? 'Your profile was saved and the changed verification details were sent for review.'
            : 'Your professional profile was saved.');
    }

    public function photo(Request $request)
    {
        $profile = $request->user()->doctorProfile()->firstOrFail();
        abort_unless($profile->profile_photo_path && Storage::disk('local')->exists($profile->profile_photo_path), 404);
        return response()->file(Storage::disk('local')->path($profile->profile_photo_path), [
            'Cache-Control' => 'private, max-age=300', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
