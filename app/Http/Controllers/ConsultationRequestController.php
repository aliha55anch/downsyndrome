<?php

namespace App\Http\Controllers;

use App\Models\ConsultationRequest;
use App\Models\DoctorProfile;
use App\Models\PaymentTransaction;
use App\Services\ConsultationRequestEmailDispatcher;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ConsultationRequestController extends Controller
{
    public function create(Request $request, DoctorProfile $doctorProfile): View
    {
        abort_unless($doctorProfile->isPubliclyBookable(), 404);
        $user = $request->user();
        abort_unless($user && $user->hasRole(\App\Models\User::ROLE_PARENT), 403);

        $profile = $doctorProfile->load(['user', 'specialties', 'consultationOptions']);
        $active = $profile->consultationOptions->where('is_active', true);
        $baseFee = $profile->consultation_fee;
        $generalFee = $baseFee;
        if ($generalFee === null && $active->count() === 1) $generalFee = $active->first()->fee;
        $fees = ['general' => $generalFee === null ? 0 : (float) $generalFee];
        foreach (['online', 'in_person'] as $type) {
            $optionFee = $active->firstWhere('type', $type)?->fee;
            $fees[$type] = $baseFee !== null ? (float) $baseFee : ($optionFee === null ? 0 : (float) $optionFee);
        }

        return view('consultations.create', [
            'profile' => $profile,
            'fees' => $fees,
            'fee' => $fees['general'],
        ]);
    }

    public function store(Request $request, DoctorProfile $doctorProfile, PaymentService $payments, ConsultationRequestEmailDispatcher $emails): RedirectResponse
    {
        abort_unless($doctorProfile->isPubliclyBookable(), 404);
        $user = $request->user();
        abort_unless($user && $user->hasRole(\App\Models\User::ROLE_PARENT), 403);

        $data = $request->validate([
            'consultation_type' => ['required', Rule::in(['online', 'in_person', 'general'])],
            'payment_choice' => ['required', Rule::in($request->input('consultation_type') === 'in_person' ? ['pay_now', 'pay_later'] : ['pay_now'])],
            'request_date' => ['required', 'date', 'after_or_equal:today'],
            'patient_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $active = $doctorProfile->consultationOptions()->where('is_active', true)->get();
        $fee = $doctorProfile->consultation_fee;
        if ($fee === null && $data['consultation_type'] !== 'general') {
            $fee = $active->firstWhere('type', $data['consultation_type'])?->fee;
        }
        if ($fee === null && $active->count() === 1) $fee = $active->first()->fee;
        if ($fee === null) {
            $fee = 0;
        }

        if ($data['consultation_type'] === 'online' && (float) $fee <= 0) {
            return back()->withInput()->withErrors([
                'payment_choice' => 'Online consultations require a published fee so payment can be collected securely. Please choose an in-person request or contact the doctor.',
            ]);
        }

        $fee = (float) $fee;
        $requiresPayment = $fee > 0;
        $payNow = $data['payment_choice'] === 'pay_now' && $requiresPayment;

        $consultationRequest = ConsultationRequest::create([
            'doctor_profile_id' => $doctorProfile->id,
            'patient_user_id' => $user->id,
            'consultation_type' => $data['consultation_type'],
            'consultation_fee' => $fee,
            'payment_method' => $payNow ? 'card' : ($data['payment_choice'] === 'pay_later' ? 'pay_later' : 'not_required'),
            'payment_status' => $requiresPayment ? ConsultationRequest::PAYMENT_PENDING : ConsultationRequest::PAYMENT_NOT_REQUIRED,
            'request_date' => $data['request_date'],
            'status' => ConsultationRequest::STATUS_PENDING,
            'patient_notes' => $data['patient_notes'] ?? null,
        ]);
        $emails->dispatch($consultationRequest, 'consultation_request_received');

        if ($payNow) {
            $payment = $consultationRequest->payments()->create([
                'payer_user_id' => $user->id,
                'provider' => 'safepay',
                'status' => PaymentTransaction::PENDING,
                'amount_minor' => (int) round($fee * 100),
                'currency' => strtoupper((string) config('app.currency', 'PKR')),
                'idempotency_key' => (string) Str::uuid(),
                'metadata' => ['purpose' => 'consultation_request'],
            ]);

            $payment = $payments->startSafepayCheckout(
                $payment,
                route('consultations.payment.return', $consultationRequest, absolute: true),
                route('consultations.payment.cancel', $consultationRequest, absolute: true),
            );

            if ($payment->status === PaymentTransaction::PENDING && $payment->checkout_url) {
                return redirect()->away($payment->checkout_url);
            }

            $consultationRequest->update(['payment_status' => ConsultationRequest::PAYMENT_FAILED]);

            return redirect()->route('consultations.mine')->withErrors([
                'payment' => 'We could not start secure card checkout. No payment was taken. You can try submitting the request again or contact support.',
            ]);
        }

        return redirect()->route('consultations.mine')->with('status', 'Consultation request submitted successfully. Request #'.$consultationRequest->id.' is pending review.');
    }

    public function paymentReturn(Request $request, ConsultationRequest $consultationRequest): RedirectResponse
    {
        abort_unless($consultationRequest->patient_user_id === $request->user()->id, 404);

        $message = $consultationRequest->payment_status === ConsultationRequest::PAYMENT_PAID
            ? 'Your consultation payment was verified.'
            : 'Your consultation request is saved. We are waiting for Safepay to verify the payment.';

        return redirect()->route('consultations.mine')->with('status', $message);
    }

    public function mine(Request $request): View
    {
        $user = $request->user();
        abort_unless($user && $user->hasRole(\App\Models\User::ROLE_PARENT), 403);

        $requests = ConsultationRequest::query()
            ->with(['doctorProfile.user', 'doctorProfile.specialties'])
            ->where('patient_user_id', $user->id)
            ->latest('request_date')
            ->latest('id')
            ->paginate(15);

        return view('consultations.mine', compact('requests'));
    }
}
