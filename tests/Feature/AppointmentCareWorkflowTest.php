<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentCancellationRequest;
use App\Models\AppointmentPrescription;
use App\Models\AppointmentRefundCase;
use App\Models\DoctorProfile;
use App\Models\PaymentTransaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AppointmentCareWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $patient;
    private User $doctor;
    private User $admin;
    private DoctorProfile $profile;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.google_apps_script.web_app_url' => '', 'services.google_apps_script.shared_secret' => '']);
        $this->patient = $this->user('patient', User::ROLE_PARENT);
        $this->doctor = $this->user('doctor', User::ROLE_APPROVED_DOCTOR);
        $this->admin = $this->user('admin', User::ROLE_ADMIN);
        $this->profile = $this->doctor->doctorProfile()->create([
            'phone' => '+923001234567', 'professional_title' => 'Dr', 'specialty' => 'Pediatrics',
            'qualification' => 'MBBS', 'institution' => 'Test University', 'years_experience' => 5,
            'city' => 'Lahore', 'license_number' => 'CARE-LICENSE', 'issuing_authority' => 'Council',
            'status' => DoctorProfile::STATUS_APPROVED, 'is_public' => true, 'timezone' => 'Asia/Karachi',
        ]);
    }

    public function test_patient_cancellation_waits_for_doctor_and_approved_paid_request_creates_refund_case(): void
    {
        $appointment = $this->appointment(Appointment::CONFIRMED, CarbonImmutable::now('UTC')->addDays(5));
        $payment = $appointment->payments()->create([
            'payer_user_id' => $this->patient->id, 'provider' => 'safepay', 'status' => PaymentTransaction::PAID,
            'amount_minor' => 250000, 'currency' => 'PKR', 'idempotency_key' => (string) Str::uuid(), 'paid_at' => now(), 'provider_reference' => 'care-payment-tracker',
        ]);

        $this->actingAs($this->patient)->post(route('appointments.cancel', $appointment), ['reason' => 'Family schedule changed'])->assertRedirect();
        $appointment->refresh();
        $this->assertSame(Appointment::PENDING_CANCELLATION, $appointment->status);
        $this->assertSame(AppointmentCancellationRequest::PENDING, $appointment->cancellationRequest->status);
        $this->assertSame('doctor:'.$this->profile->id.':'.$appointment->startsAtUtc()->format('YmdHis'), $appointment->slot_lock_key);
        $this->assertDatabaseCount('appointment_refund_cases', 0);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $this->doctor->id]);
        $this->actingAs($this->patient)->get(route('notifications.index'))->assertOk()->assertSee('Cancellation request received');
        $this->actingAs($this->doctor)->get(route('doctor.notifications.index'))->assertOk()->assertSee('Cancellation request received');

        $this->actingAs($this->doctor)->patch(route('doctor.appointments.cancellation-review', $appointment), [
            'decision' => 'approve', 'response' => 'Approved; refund queued for processing.',
        ])->assertRedirect();
        $appointment->refresh();
        $this->assertSame(Appointment::CANCELLED, $appointment->status);
        $this->assertNull($appointment->slot_lock_key);
        $this->assertSame(AppointmentCancellationRequest::APPROVED, $appointment->cancellationRequest->fresh()->status);
        $this->assertDatabaseHas('appointment_refund_cases', [
            'appointment_id' => $appointment->id, 'payment_transaction_id' => $payment->id,
            'amount_minor' => 250000, 'status' => AppointmentRefundCase::PENDING_REVIEW,
        ]);
        $this->actingAs($this->admin)->get(route('admin.refunds.index'))->assertOk()->assertSee('LS-APT-'.$appointment->id);
        $this->actingAs($this->admin)->get(route('admin.notifications.index'))->assertOk()->assertSee('Cancellation approved');

        config(['services.safepay.webhook_secret' => 'care-webhook-secret']);
        $refundCase = AppointmentRefundCase::firstOrFail();
        $this->actingAs($this->admin)->patch(route('admin.refunds.update', $refundCase), [
            'status' => 'processing', 'provider_reference' => 'refund-ref-care', 'admin_note' => 'Submitted in Safepay sandbox.',
        ])->assertRedirect();
        $payload = ['token' => 'care-refund-event', 'type' => 'payment.refunded', 'data' => ['tracker' => $payment->provider_reference, 'refund_amount' => 250000, 'currency' => 'PKR']];
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha512', $body, 'care-webhook-secret');
        $this->call('POST', route('webhooks.safepay'), [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_SFPY_SIGNATURE' => $signature], $body)->assertOk();
        $this->assertSame(AppointmentRefundCase::SUCCEEDED, $refundCase->fresh()->status);
    }

    public function test_doctor_can_decline_patient_cancellation_and_appointment_stays_confirmed(): void
    {
        $appointment = $this->appointment(Appointment::CONFIRMED, CarbonImmutable::now('UTC')->addDays(4));
        $this->actingAs($this->patient)->post(route('appointments.cancel', $appointment), ['reason' => 'Need to change plans'])->assertRedirect();
        $this->actingAs($this->doctor)->patch(route('doctor.appointments.cancellation-review', $appointment), ['decision' => 'reject', 'response' => 'Please keep your current appointment.'])->assertRedirect();
        $this->assertSame(Appointment::CONFIRMED, $appointment->fresh()->status);
        $this->assertSame(AppointmentCancellationRequest::REJECTED, $appointment->cancellationRequest->fresh()->status);
    }

    public function test_completed_visit_prescription_is_private_until_published_and_downloads_pdf(): void
    {
        $appointment = $this->appointment(Appointment::COMPLETED, CarbonImmutable::now('UTC')->subHour());
        $this->actingAs($this->doctor)->get(route('doctor.appointments.prescription.edit', $appointment))->assertOk()->assertSee('Digital prescription');
        $payload = [
            'diagnosis' => 'Seasonal allergies',
            'medicines' => [['name' => 'Example medicine', 'dosage' => '5 mg', 'frequency' => 'Once daily', 'duration' => '5 days']],
            'instructions' => 'Take with water', 'notes' => 'Monitor symptoms', 'follow_up' => 'Review in one week',
        ];
        $this->actingAs($this->doctor)->post(route('doctor.appointments.prescription.save', $appointment), $payload)->assertRedirect();
        $prescription = $appointment->prescription()->firstOrFail();
        $this->actingAs($this->patient)->get(route('prescriptions.show', $appointment))->assertNotFound();

        $this->actingAs($this->doctor)->post(route('doctor.appointments.prescription.publish', $appointment), $payload)->assertRedirect();
        $this->assertSame(AppointmentPrescription::PUBLISHED, $prescription->fresh()->status);
        $this->actingAs($this->patient)->get(route('prescriptions.show', $appointment))->assertOk()->assertSee('Seasonal allergies')->assertSee('Example medicine');
        $pdf = $this->actingAs($this->patient)->get(route('prescriptions.download', $appointment))->assertOk();
        $this->assertStringStartsWith('%PDF-1.4', $pdf->getContent());

        $stranger = $this->user('stranger', User::ROLE_PARENT);
        $this->actingAs($stranger)->get(route('prescriptions.download', $appointment))->assertNotFound();
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $this->patient->id]);
    }

    private function appointment(string $status, CarbonImmutable $startsAt): Appointment
    {
        return Appointment::create([
            'patient_user_id' => $this->patient->id, 'doctor_profile_id' => $this->profile->id,
            'consultation_type' => 'online', 'duration_minutes' => 30, 'starts_at' => $startsAt,
            'ends_at' => $startsAt->addMinutes(30), 'timezone' => 'Asia/Karachi', 'amount_minor' => 250000,
            'currency' => 'PKR', 'status' => $status,
            'slot_lock_key' => in_array($status, [Appointment::CONFIRMED, Appointment::RESCHEDULED], true)
                ? 'doctor:'.$this->profile->id.':'.$startsAt->format('YmdHis') : null,
            'idempotency_key' => (string) Str::uuid(),
        ]);
    }

    private function user(string $prefix, string $role): User
    {
        $user = User::create(['first_name' => ucfirst($prefix), 'last_name' => 'Test', 'name' => ucfirst($prefix).' Test', 'email' => $prefix.'-'.Str::random(8).'@example.test', 'password' => bcrypt('StrongPassword123')]);
        $user->forceFill(['role' => $role])->save();
        $user->markEmailAsVerified();
        return $user;
    }
}
