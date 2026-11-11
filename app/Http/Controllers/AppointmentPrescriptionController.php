<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AppointmentPrescription;
use App\Models\User;
use App\Services\AppointmentEmailDispatcher;
use App\Services\AppointmentLifecycleNotifier;
use App\Services\PrescriptionPdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class AppointmentPrescriptionController extends Controller
{
    public function edit(Request $request, Appointment $appointment): View
    {
        $profile = $request->user()->doctorProfile()->firstOrFail();
        abort_unless($appointment->doctor_profile_id === $profile->id, 404);
        abort_unless($appointment->status === Appointment::COMPLETED, 409);
        $prescription = $appointment->prescription()->firstOrCreate([], [
            'doctor_profile_id' => $profile->id, 'patient_user_id' => $appointment->patient_user_id,
            'status' => AppointmentPrescription::DRAFT,
        ]);
        abort_if($prescription->status === AppointmentPrescription::PUBLISHED, 409, 'This prescription is already published and is read-only.');

        return view('prescriptions.edit', compact('appointment', 'prescription'));
    }

    public function save(Request $request, Appointment $appointment)
    {
        $prescription = $this->doctorDraft($request, $appointment);
        $prescription->update($this->validatedPrescription($request));
        return back()->with('status', 'Prescription draft saved. Only you can see it until you publish it.');
    }

    public function publish(Request $request, Appointment $appointment, AppointmentEmailDispatcher $emails, AppointmentLifecycleNotifier $notifier)
    {
        $prescription = $this->doctorDraft($request, $appointment);
        $data = $this->validatedPrescription($request);
        if (blank($data['diagnosis'] ?? null) && empty($data['medicines'])) {
            throw ValidationException::withMessages(['diagnosis' => 'Add a diagnosis or at least one medicine before publishing.']);
        }
        DB::transaction(function () use ($prescription, $data): void {
            $locked = AppointmentPrescription::whereKey($prescription->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== AppointmentPrescription::DRAFT) throw ValidationException::withMessages(['prescription' => 'This prescription is no longer a draft.']);
            $locked->update($data + ['status' => AppointmentPrescription::PUBLISHED, 'published_at' => now()]);
            $locked->appointment->events()->create([
                'event_type' => 'prescription_published', 'from_status' => Appointment::COMPLETED, 'to_status' => Appointment::COMPLETED,
                'details' => ['prescription_id' => $locked->id], 'created_at' => now(),
            ]);
        });
        $emails->dispatch($appointment->fresh(), 'prescription_published', [$appointment->patient]);
        $notifier->notify($appointment->fresh(), 'prescription_published', 'Prescription published', 'Your doctor published a prescription for appointment LS-APT-'.$appointment->id.'.');

        return redirect()->route('doctor.prescriptions.show', $appointment)->with('status', 'Prescription published securely to the patient.');
    }

    public function show(Request $request, Appointment $appointment): View
    {
        $prescription = $appointment->prescription()->firstOrFail();
        $user = $request->user();
        $isPatient = $appointment->patient_user_id === $user->id;
        $isDoctor = $user->doctorProfile()->whereKey($appointment->doctor_profile_id)->exists();
        abort_unless($isPatient || $isDoctor || $user->hasRole(User::ROLE_ADMIN), 404);
        abort_unless($prescription->status === AppointmentPrescription::PUBLISHED || $isDoctor, 404);

        return view('prescriptions.show', compact('appointment', 'prescription'));
    }

    public function download(Request $request, Appointment $appointment, PrescriptionPdf $pdf): Response
    {
        $prescription = $appointment->prescription()->where('status', AppointmentPrescription::PUBLISHED)->firstOrFail();
        $user = $request->user();
        abort_unless($appointment->patient_user_id === $user->id
            || $user->doctorProfile()->whereKey($appointment->doctor_profile_id)->exists()
            || $user->hasRole(User::ROLE_ADMIN), 404);

        return response($pdf->render($appointment, $prescription), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="prescription-LS-APT-'.$appointment->id.'.pdf"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function doctorDraft(Request $request, Appointment $appointment): AppointmentPrescription
    {
        $profile = $request->user()->doctorProfile()->firstOrFail();
        abort_unless($appointment->doctor_profile_id === $profile->id, 404);
        abort_unless($appointment->status === Appointment::COMPLETED, 409);
        $prescription = $appointment->prescription()->firstOrCreate([], [
            'doctor_profile_id' => $profile->id, 'patient_user_id' => $appointment->patient_user_id,
            'status' => AppointmentPrescription::DRAFT,
        ]);
        abort_if($prescription->status !== AppointmentPrescription::DRAFT, 409);
        return $prescription;
    }

    private function validatedPrescription(Request $request): array
    {
        return $request->validate([
            'diagnosis' => ['nullable', 'string', 'max:5000'],
            'medicines' => ['nullable', 'array', 'max:30'],
            'medicines.*.name' => ['required_with:medicines', 'string', 'max:200'],
            'medicines.*.dosage' => ['required_with:medicines', 'string', 'max:200'],
            'medicines.*.frequency' => ['required_with:medicines', 'string', 'max:200'],
            'medicines.*.duration' => ['required_with:medicines', 'string', 'max:200'],
            'instructions' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'follow_up' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
