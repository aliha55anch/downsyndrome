<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DoctorProfile;
use App\Models\MedicalSpecialty;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DoctorProfileVisibilityController extends Controller
{
    public function specialties(): View
    {
        return view('admin.doctors.specialties', ['specialties' => MedicalSpecialty::withCount('doctorProfiles')->orderBy('name')->get()]);
    }

    public function storeSpecialty(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120']]);
        if (MedicalSpecialty::where('name_key', mb_strtolower(trim($data['name'])))->exists()) {
            throw ValidationException::withMessages(['name' => 'That specialty already exists.']);
        }
        MedicalSpecialty::create($data);
        return back()->with('status', 'Specialty option added.');
    }

    public function updateSpecialty(Request $request, MedicalSpecialty $specialty): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'is_active' => ['required', 'boolean']]);
        if (MedicalSpecialty::where('name_key', mb_strtolower(trim($data['name'])))->where('id', '!=', $specialty->id)->exists()) {
            throw ValidationException::withMessages(['name' => 'That specialty already exists.']);
        }
        $specialty->update($data);
        return back()->with('status', 'Specialty option updated.');
    }

    public function visibility(Request $request, DoctorProfile $doctorProfile): RedirectResponse
    {
        $data = $request->validate(['is_public' => ['required', 'boolean']]);
        if ($data['is_public']) {
            abort_unless($doctorProfile->status === DoctorProfile::STATUS_APPROVED
                && $doctorProfile->user()->where('role', User::ROLE_APPROVED_DOCTOR)->exists(), 422,
                'Only an actively approved doctor can be published.');
        }
        DB::transaction(function () use ($request, $doctorProfile, $data): void {
            $profile = DoctorProfile::whereKey($doctorProfile->id)->lockForUpdate()->firstOrFail();
            $profile->is_public = (bool) $data['is_public'];
            $profile->save();
            $profile->history()->create([
                'actor_id' => $request->user()->id, 'event_type' => $profile->is_public ? 'public_profile_enabled' : 'public_profile_hidden',
                'from_status' => $profile->status, 'to_status' => $profile->status,
                'details' => ['is_public' => $profile->is_public], 'created_at' => now(),
            ]);
        });
        return back()->with('status', $data['is_public'] ? 'Public profile published.' : 'Public profile hidden.');
    }
}
