<?php

namespace App\Http\Controllers;

use App\Models\DoctorProfile;
use App\Models\MedicalSpecialty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PublicDoctorController extends Controller
{
    public function index(Request $request): View
    {
        $specialty = $request->query('specialty');
        $city = $request->query('city');
        $name = trim((string) $request->query('name', ''));
        $doctors = DoctorProfile::query()->publiclyBookable()->with(['user', 'specialties', 'qualifications', 'consultationOptions'])
            ->when($specialty, fn ($query) => $query->whereHas('specialties', fn ($q) => $q->where('medical_specialties.id', $specialty)))
            ->when($city, fn ($query) => $query->where('city', 'like', '%'.addcslashes($city, '%_').'%'))
            ->when($name !== '', fn ($query) => $query->whereHas('user', fn ($q) => $q->where('name', 'like', '%'.addcslashes($name, '%_').'%')))
            ->orderBy('professional_title')->paginate(12)->withQueryString();
        $specialties = MedicalSpecialty::where('is_active', true)->orderBy('name')->get();
        $cities = DoctorProfile::query()->publiclyBookable()
            ->distinct()->orderBy('city')->pluck('city');
        return view('doctors.index', compact('doctors', 'specialties', 'cities', 'specialty', 'city', 'name'));
    }

    public function show(DoctorProfile $doctorProfile): View
    {
        abort_unless($doctorProfile->isPubliclyBookable(), 404);
        return view('doctors.show', ['profile' => $doctorProfile->load(['user', 'specialties', 'qualifications', 'consultationOptions', 'availabilityPeriods.breaks'])]);
    }

    public function photo(DoctorProfile $doctorProfile)
    {
        abort_unless($doctorProfile->isPubliclyBookable() && $doctorProfile->profile_photo_path, 404);
        abort_unless(Storage::disk('local')->exists($doctorProfile->profile_photo_path), 404);
        return response()->file(Storage::disk('local')->path($doctorProfile->profile_photo_path), [
            'Cache-Control' => 'no-store', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
