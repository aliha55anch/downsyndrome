<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConsultationRequest;
use App\Models\DoctorProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ConsultationRequestController extends Controller
{
    public function index(Request $request): View
    {
        $status = (string) $request->query('status', '');
        $paymentStatus = (string) $request->query('payment_status', '');
        $doctorId = $request->query('doctor_profile_id');

        $requests = ConsultationRequest::query()
            ->with(['doctorProfile.user', 'patient'])
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($paymentStatus !== '', fn ($q) => $q->where('payment_status', $paymentStatus))
            ->when($doctorId, fn ($q) => $q->where('doctor_profile_id', $doctorId))
            ->latest('request_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $doctors = DoctorProfile::query()->with('user')->whereHas('consultationRequests')->orderBy('id', 'desc')->get();

        return view('admin.consultations.index', compact('requests', 'status', 'paymentStatus', 'doctorId', 'doctors'));
    }

    public function show(ConsultationRequest $consultationRequest): View
    {
        return view('admin.consultations.show', [
            'requestItem' => $consultationRequest->load(['doctorProfile.user', 'patient']),
        ]);
    }

    public function update(Request $request, ConsultationRequest $consultationRequest): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in([
                ConsultationRequest::STATUS_PENDING,
                ConsultationRequest::STATUS_APPROVED,
                ConsultationRequest::STATUS_COMPLETED,
                ConsultationRequest::STATUS_CANCELLED,
            ])],
            'payment_status' => ['required', Rule::in([
                ConsultationRequest::PAYMENT_PENDING,
                ConsultationRequest::PAYMENT_PAID,
                ConsultationRequest::PAYMENT_FAILED,
                ConsultationRequest::PAYMENT_NOT_REQUIRED,
            ])],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $consultationRequest->update($data);

        return back()->with('status', 'Consultation request updated.');
    }

    public function updateDoctorFee(Request $request, DoctorProfile $doctorProfile): RedirectResponse
    {
        $data = $request->validate([
            'consultation_fee' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
        ]);

        $doctorProfile->update([
            'consultation_fee' => $data['consultation_fee'] === null || $data['consultation_fee'] === '' ? null : $data['consultation_fee'],
        ]);

        return back()->with('status', 'Consultation fee updated.');
    }
}
