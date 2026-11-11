<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\DoctorProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $profile = $request->user()->doctorProfile()->with([
            'currentDocuments' => fn ($query) => $query->orderBy('document_type'),
            'history.actor',
        ])->firstOrFail();

        $missingRequirements = [];
        foreach ([
            'phone' => 'phone number',
            'professional_title' => 'professional title',
            'specialty' => 'medical specialty',
            'qualification' => 'professional qualification',
            'institution' => 'university or institution',
            'city' => 'city',
            'license_number' => 'medical registration number',
            'issuing_authority' => 'issuing authority',
        ] as $field => $label) {
            if (blank($profile->{$field})) $missingRequirements[] = $label;
        }
        $currentDocuments = $profile->currentDocuments->keyBy('document_type');
        foreach (['license' => 'medical license document', 'qualification' => 'qualification certificate'] as $type => $label) {
            if (! isset($currentDocuments[$type])) {
                $missingRequirements[] = $label;
            } elseif ($currentDocuments[$type]->status === 'rejected') {
                $missingRequirements[] = 'replacement for rejected '.$label;
            }
        }

        return view('doctor.dashboard', [
            'profile' => $profile,
            'missingRequirements' => $missingRequirements,
            'user' => $request->user(),
            'isApproved' => $request->user()->hasRole(User::ROLE_APPROVED_DOCTOR)
                && $profile->status === DoctorProfile::STATUS_APPROVED,
        ]);
    }
}
