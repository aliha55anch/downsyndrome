<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\DoctorProfile;
use App\Models\User;
use App\Notifications\DoctorVerificationNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DoctorRegistrationController extends Controller
{
    public function create(): View { return view('auth.doctor-register'); }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'string', 'min:8'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[+0-9() .-]{7,30}$/'],
            'professional_title' => ['required', 'string', 'max:120'],
            'specialty' => ['required', 'string', 'max:120'],
            'qualification' => ['required', 'string', 'max:180'],
            'institution' => ['required', 'string', 'max:180'],
            'years_experience' => ['required', 'integer', 'min:0', 'max:70'],
            'workplace' => ['nullable', 'string', 'max:180'],
            'city' => ['required', 'string', 'max:120'],
            'biography' => ['nullable', 'string', 'max:2000'],
            'license_number' => ['required', 'string', 'max:120', 'unique:doctor_profiles,license_number'],
            'issuing_authority' => ['required', 'string', 'max:180'],
            'license_expires_at' => ['nullable', 'date', 'after:today'],
            'license_document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'mimetypes:application/pdf,image/jpeg,image/png', 'max:10240'],
            'qualification_document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'mimetypes:application/pdf,image/jpeg,image/png', 'max:10240'],
        ]);

        $stored = [];
        try {
            DB::transaction(function () use ($request, $validated, &$stored): void {
                $user = User::create([
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'],
                    'name' => $validated['first_name'].' '.$validated['last_name'],
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']),
                ]);
                $user->forceFill(['role' => User::ROLE_PENDING_DOCTOR])->save();

                $profile = $user->doctorProfile()->create([
                    ...collect($validated)->only([
                        'phone', 'professional_title', 'specialty', 'qualification', 'institution',
                        'years_experience', 'workplace', 'city', 'biography', 'license_number',
                        'issuing_authority', 'license_expires_at',
                    ])->all(),
                    'status' => DoctorProfile::STATUS_PENDING,
                    'submitted_at' => now(),
                ]);

                foreach (['license' => 'license_document', 'qualification' => 'qualification_document'] as $type => $field) {
                    $file = $request->file($field);
                    $path = $file->store('doctor-verification/'.$profile->id, 'local');
                    if (! $path) {
                        throw new \RuntimeException('The document could not be stored.');
                    }
                    $stored[] = $path;
                    $profile->documents()->create([
                        'document_type' => $type,
                        'original_name' => basename($file->getClientOriginalName()),
                        'storage_path' => $path,
                        'mime_type' => $file->getMimeType(),
                        'size_bytes' => $file->getSize(),
                        'version' => 1,
                        'status' => 'pending',
                        'is_current' => true,
                    ]);
                }

                $profile->history()->create([
                    'actor_id' => $user->id,
                    'event_type' => 'application_submitted',
                    'to_status' => DoctorProfile::STATUS_PENDING,
                    'reason' => 'Doctor verification application submitted.',
                    'created_at' => now(),
                ]);
            });
        } catch (\Throwable $exception) {
            foreach ($stored as $path) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }

        $user = User::where('email', $validated['email'])->firstOrFail();
        $user->notify(new DoctorVerificationNotification(
            'Application submitted',
            'Your doctor verification application is pending review. Verify your email to open your dashboard.',
            route('doctor.dashboard'),
        ));
        $user->sendEmailVerificationNotification();
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('verification.notice')->with('status', 'Your doctor application was submitted. Verify your email to follow its review status.');
    }
}
