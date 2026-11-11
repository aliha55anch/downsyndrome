<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentSlotHold;
use App\Models\DoctorProfile;
use App\Models\AppointmentEmail;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Services\PaymentService;
use App\Services\SafepayPaymentGateway;
use App\Services\GoogleAppsScriptMailer;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class AppointmentBookingTest extends TestCase
{
    use RefreshDatabase;

    private User $patient;
    private User $doctor;
    private DoctorProfile $profile;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.google_apps_script.web_app_url' => '', 'services.google_apps_script.shared_secret' => '']);
        $this->patient = $this->makeUser('patient', User::ROLE_PARENT);
        $this->doctor = $this->makeUser('doctor', User::ROLE_APPROVED_DOCTOR);
        $this->profile = $this->doctor->doctorProfile()->create([
            'phone' => '+923001234567', 'professional_title' => 'Dr', 'specialty' => 'Pediatrics',
            'qualification' => 'MBBS', 'institution' => 'Test University', 'years_experience' => 5,
            'city' => 'Lahore', 'license_number' => 'APPT-LICENSE', 'issuing_authority' => 'Council',
            'status' => DoctorProfile::STATUS_APPROVED, 'is_public' => true, 'timezone' => 'Asia/Karachi',
            'availability_published_at' => now(),
        ]);
    }

    public function test_free_booking_uses_module_seven_schedule_and_patient_ownership(): void
    {
        $start = CarbonImmutable::now('Asia/Karachi')->addDays(3)->setTime(10, 0);
        $this->profile->availabilityPeriods()->create(['day_of_week' => $start->dayOfWeek, 'starts_at' => '09:00', 'ends_at' => '12:00']);
        $option = $this->profile->consultationOptions()->create(['type' => 'online', 'duration_minutes' => 30, 'fee' => 0, 'is_active' => true]);

        $payload = ['consultation_option_id' => $option->id, 'starts_at' => $start->format('Y-m-d\\TH:i'), 'idempotency_key' => (string) Str::uuid()];
        $this->actingAs($this->patient)->post(route('appointments.store', $this->profile), $payload)->assertRedirect();
        $appointment = Appointment::firstOrFail();
        $this->assertSame(Appointment::CONFIRMED, $appointment->status);
        $this->assertSame(0, $appointment->payments()->count());
        $this->actingAs($this->makeUser('other', User::ROLE_PARENT))->get(route('appointments.show', $appointment))->assertNotFound();
        $this->actingAs($this->doctor)->get(route('doctor.appointments.show', $appointment))->assertOk()->assertSee('Patient Test');
        $admin = $this->makeUser('admin', User::ROLE_ADMIN);
        $this->actingAs($admin)->get(route('admin.appointments.show', $appointment))->assertOk()->assertSee('Appointment #');
        $this->assertDatabaseCount('appointment_email_outbox', 2);
        $this->actingAs($this->patient)->post(route('appointments.store', $this->profile), $payload)->assertRedirect();
        $this->assertDatabaseCount('appointments', 1);
        $this->assertDatabaseCount('appointment_email_outbox', 2);
    }

    public function test_signed_safepay_webhook_confirms_once_and_redirect_does_not_confirm(): void
    {
        config(['services.safepay.webhook_secret' => 'test-webhook-secret']);
        $start = CarbonImmutable::now('Asia/Karachi')->addDays(2)->setTime(11, 0);
        $appointment = Appointment::create([
            'patient_user_id' => $this->patient->id, 'doctor_profile_id' => $this->profile->id,
            'consultation_type' => 'online', 'duration_minutes' => 30, 'starts_at' => $start->utc(), 'ends_at' => $start->addMinutes(30)->utc(),
            'timezone' => 'Asia/Karachi', 'amount_minor' => 250000, 'currency' => 'PKR', 'status' => Appointment::PENDING_PAYMENT,
            'hold_expires_at' => now('UTC')->addMinutes(50), 'slot_lock_key' => 'doctor:'.$this->profile->id.':'.$start->utc()->format('YmdHis'),
            'idempotency_key' => (string) Str::uuid(),
        ]);
        $payment = $appointment->payments()->create([
            'payer_user_id' => $this->patient->id, 'provider' => 'safepay', 'status' => PaymentTransaction::PENDING,
            'amount_minor' => 250000, 'currency' => 'PKR', 'idempotency_key' => (string) Str::uuid(), 'provider_reference' => 'tracker-test-1',
        ]);
        $this->actingAs($this->patient)->get(route('appointments.payment.return', $appointment))
            ->assertOk()->assertSee('window.location.reload()')->assertSee('We’ll take you to your appointment details');
        $this->assertSame(Appointment::PENDING_PAYMENT, $appointment->fresh()->status);
        $this->assertGreaterThan(now('UTC')->timestamp, $appointment->fresh()->holdExpiresAtUtc()->timestamp);

        $payload = ['token' => 'event-1', 'type' => 'payment.succeeded', 'data' => ['tracker' => 'tracker-test-1', 'amount' => 250000, 'currency' => 'PKR', 'success' => true]];
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha512', $body, 'test-webhook-secret');
        $this->call('POST', route('webhooks.safepay'), [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_SFPY_SIGNATURE' => $signature], $body)->assertOk();
        $this->assertSame(Appointment::CONFIRMED, $appointment->fresh()->status);
        $this->assertSame(PaymentTransaction::PAID, $payment->fresh()->status);
        $this->assertDatabaseHas('payment_webhook_events', ['event_id' => 'event-1', 'processing_status' => 'processed']);
        $this->assertDatabaseCount('appointment_email_outbox', 2);
        $this->assertDatabaseHas('appointment_email_outbox', ['appointment_id' => $appointment->id, 'email_type' => 'payment_succeeded', 'recipient_user_id' => $this->patient->id]);
        $this->assertDatabaseHas('appointment_email_outbox', ['appointment_id' => $appointment->id, 'email_type' => 'payment_succeeded', 'recipient_user_id' => $this->doctor->id]);
        $doctorNotice = $this->doctor->notifications()->where('data->type', 'payment_succeeded')->first();
        $this->assertNotNull($doctorNotice);
        $this->assertSame(route('doctor.appointments.show', $appointment), $doctorNotice->data['url']);
        $this->actingAs($this->patient)->get(route('appointments.payment.return', $appointment))
            ->assertRedirect(route('appointments.show', $appointment));
        $this->call('POST', route('webhooks.safepay'), [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_SFPY_SIGNATURE' => $signature], $body)->assertOk();
        $this->assertDatabaseCount('appointment_email_outbox', 2);
    }

    public function test_invalid_webhook_signature_is_rejected(): void
    {
        config(['services.safepay.webhook_secret' => 'test-webhook-secret']);
        $response = $this->call('POST', route('webhooks.safepay'), [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_SFPY_SIGNATURE' => str_repeat('a', 128)], '{"token":"bad","type":"payment.succeeded"}');
        $response->assertStatus(400);
        $this->assertDatabaseCount('payment_webhook_events', 0);
    }

    public function test_verified_safepay_refunds_are_recorded_once_and_full_refund_releases_booking(): void
    {
        config(['services.safepay.webhook_secret' => 'test-webhook-secret']);
        $start = CarbonImmutable::now('Asia/Karachi')->addDays(2)->setTime(11, 0);
        $appointment = Appointment::create([
            'patient_user_id' => $this->patient->id, 'doctor_profile_id' => $this->profile->id, 'consultation_type' => 'online',
            'duration_minutes' => 30, 'starts_at' => $start->utc(), 'ends_at' => $start->addMinutes(30)->utc(), 'timezone' => 'Asia/Karachi',
            'amount_minor' => 250000, 'currency' => 'PKR', 'status' => Appointment::CONFIRMED,
            'slot_lock_key' => 'doctor:'.$this->profile->id.':'.$start->utc()->format('YmdHis'), 'idempotency_key' => (string) Str::uuid(),
        ]);
        $payment = $appointment->payments()->create([
            'payer_user_id' => $this->patient->id, 'provider' => 'safepay', 'status' => PaymentTransaction::PAID,
            'amount_minor' => 250000, 'currency' => 'PKR', 'idempotency_key' => (string) Str::uuid(), 'provider_reference' => 'refund-tracker', 'paid_at' => now(),
        ]);
        $send = function (string $eventId, int $amount) {
            $body = json_encode(['token' => $eventId, 'type' => 'payment.refunded', 'data' => ['tracker' => 'refund-tracker', 'refund_amount' => $amount, 'currency' => 'PKR', 'refunded_at' => now()->toIso8601String()]], JSON_THROW_ON_ERROR);
            return $this->call('POST', route('webhooks.safepay'), [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_SFPY_SIGNATURE' => hash_hmac('sha512', $body, 'test-webhook-secret')], $body);
        };
        $send('refund-partial-1', 100000)->assertOk();
        $send('refund-partial-1', 100000)->assertOk();
        $this->assertSame('partially_refunded', $payment->fresh()->status);
        $this->assertDatabaseCount('payment_refunds', 1);
        $send('refund-final-2', 150000)->assertOk();
        $this->assertSame('refunded', $payment->fresh()->status);
        $this->assertSame(Appointment::CANCELLED, $appointment->fresh()->status);
        $this->assertNull($appointment->fresh()->slot_lock_key);
        $this->assertDatabaseCount('payment_refunds', 2);
    }

    public function test_verified_failed_payment_releases_the_appointment_slot_immediately(): void
    {
        config(['services.safepay.webhook_secret' => 'test-webhook-secret']);
        $start = CarbonImmutable::now('Asia/Karachi')->addDays(2)->setTime(10, 0);
        $appointment = Appointment::create([
            'patient_user_id' => $this->patient->id, 'doctor_profile_id' => $this->profile->id,
            'consultation_type' => 'online', 'duration_minutes' => 30, 'starts_at' => $start->utc(), 'ends_at' => $start->addMinutes(30)->utc(),
            'timezone' => 'Asia/Karachi', 'amount_minor' => 250000, 'currency' => 'PKR', 'status' => Appointment::PENDING_PAYMENT,
            'hold_expires_at' => now('UTC')->addMinutes(50), 'slot_lock_key' => 'doctor:'.$this->profile->id.':'.$start->utc()->format('YmdHis'),
            'idempotency_key' => (string) Str::uuid(),
        ]);
        $payment = $appointment->payments()->create([
            'payer_user_id' => $this->patient->id, 'provider' => 'safepay', 'status' => PaymentTransaction::PENDING,
            'amount_minor' => 250000, 'currency' => 'PKR', 'idempotency_key' => (string) Str::uuid(),
            'provider_reference' => 'failed-tracker',
        ]);
        $payload = ['token' => 'failed-event', 'type' => 'payment.failed', 'data' => ['tracker' => 'failed-tracker']];
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha512', $body, 'test-webhook-secret');

        $this->call('POST', route('webhooks.safepay'), [], [], [], [
            'CONTENT_TYPE' => 'application/json', 'HTTP_X_SFPY_SIGNATURE' => $signature,
        ], $body)->assertOk();

        $this->assertSame(PaymentTransaction::FAILED, $payment->fresh()->status);
        $this->assertSame(Appointment::EXPIRED, $appointment->fresh()->status);
        $this->assertNull($appointment->fresh()->slot_lock_key);
        $this->assertLessThanOrEqual(now('UTC')->timestamp, $appointment->fresh()->holdExpiresAtUtc()->timestamp);
        $this->assertDatabaseHas('appointment_events', ['appointment_id' => $appointment->id, 'event_type' => 'verified_payment_failed_slot_released']);
        $this->assertDatabaseHas('appointment_email_outbox', ['appointment_id' => $appointment->id, 'email_type' => 'payment_failed']);
    }

    public function test_paid_booking_creates_one_pending_payment_and_redirects_to_hosted_checkout(): void
    {
        config(['services.safepay.environment' => 'sandbox', 'services.safepay.production_enabled' => false,
            'services.safepay.api_key' => 'sandbox-key', 'services.safepay.secret_key' => 'sandbox-secret',
            'services.safepay.webhook_secret' => 'sandbox-webhook']);
        Http::fakeSequence()->push(['data' => ['tracker' => ['token' => 'track_created']]])->push(['data' => 'tbt-created'])->push(['data' => 'tbt-retry']);
        $start = CarbonImmutable::now('Asia/Karachi')->addDays(4)->setTime(10, 0);
        $this->profile->availabilityPeriods()->create(['day_of_week' => $start->dayOfWeek, 'starts_at' => '09:00', 'ends_at' => '12:00']);
        $option = $this->profile->consultationOptions()->create(['type' => 'online', 'duration_minutes' => 30, 'fee' => 2500, 'is_active' => true]);
        $response = $this->actingAs($this->patient)->post(route('appointments.store', $this->profile), [
            'consultation_option_id' => $option->id, 'starts_at' => $start->format('Y-m-d\\TH:i'), 'idempotency_key' => (string) Str::uuid(),
        ]);
        $response->assertRedirect();
        $appointment = Appointment::firstOrFail();
        $this->assertSame(Appointment::PENDING_PAYMENT, $appointment->status);
        $this->assertSame(PaymentTransaction::PENDING, $appointment->payments()->firstOrFail()->status);
        $this->assertSame('track_created', $appointment->payments()->first()->provider_reference);
        $payment = $appointment->payments()->firstOrFail();
        $this->assertStringContainsString('/embedded?', $payment->checkout_url);
        parse_str((string) parse_url($payment->checkout_url, PHP_URL_QUERY), $checkoutParameters);
        $this->assertSame('sandbox', $checkoutParameters['environment'] ?? null);
        $this->assertSame('track_created', $checkoutParameters['tracker'] ?? null);
        $this->assertSame('tbt-created', $checkoutParameters['tbt'] ?? null);
        $this->assertSame('custom', $checkoutParameters['source'] ?? null);
        $this->assertDatabaseCount('appointment_email_outbox', 2);
        $this->assertSame(0, $appointment->emailOutbox()->where('email_type', 'payment_succeeded')->count());

        $this->actingAs($this->patient)->get(route('appointments.show', $appointment))
            ->assertOk()->assertSee('Open payment status and retry options');

        $this->actingAs($this->patient)->get(route('appointments.payment.return', $appointment))
            ->assertOk()->assertSee('We’ll take you to your appointment details as soon as Safepay confirms the payment.')
            ->assertDontSee('Retry Safepay checkout');

        $this->actingAs($this->patient)->get(route('appointments.payment.cancel', $appointment))
            ->assertOk()->assertSee('Retry Safepay checkout')->assertSee('Safepay returned without confirming this payment.')
            ->assertDontSee('Your private learning space');

        $retry = $this->actingAs($this->patient)->post(route('appointments.payment.retry', $appointment));
        $retry->assertRedirect();
        parse_str((string) parse_url($retry->headers->get('Location'), PHP_URL_QUERY), $retryParameters);
        $this->assertSame('sandbox', $retryParameters['environment'] ?? null, (string) $retry->headers->get('Location'));
        $this->assertSame('track_created', $retryParameters['tracker'] ?? null);
        $this->assertSame('tbt-retry', $retryParameters['tbt'] ?? null);
        $this->assertSame('track_created', $payment->fresh()->provider_reference);
        $this->assertSame(1, Appointment::count());
        $this->assertSame(1, $appointment->payments()->count());
        Http::assertSentCount(3);
    }

    public function test_safepay_checkout_rejection_is_diagnosable_and_not_reported_as_checkout_started(): void
    {
        config(['services.safepay.environment' => 'sandbox', 'services.safepay.production_enabled' => false,
            'services.safepay.api_key' => 'sandbox-public-key', 'services.safepay.secret_key' => 'sandbox-private-secret',
            'services.safepay.webhook_secret' => 'sandbox-webhook',
            'services.google_apps_script.web_app_url' => '', 'services.google_apps_script.shared_secret' => '']);
        Log::spy();
        Http::fake(['https://sandbox.api.getsafepay.com/*' => Http::response([
            'status' => ['message' => 'failed', 'errors' => [['code' => 'invalid_api_key', 'message' => 'Credential rejected']]],
        ], 401)]);
        $start = CarbonImmutable::now('Asia/Karachi')->addDays(4)->setTime(10, 0);
        $this->profile->availabilityPeriods()->create(['day_of_week' => $start->dayOfWeek, 'starts_at' => '09:00', 'ends_at' => '12:00']);
        $option = $this->profile->consultationOptions()->create(['type' => 'online', 'duration_minutes' => 30, 'fee' => 100, 'is_active' => true]);

        $this->actingAs($this->patient)->post(route('appointments.store', $this->profile), [
            'consultation_option_id' => $option->id, 'starts_at' => $start->format('Y-m-d\\TH:i'), 'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect();

        $appointment = Appointment::firstOrFail();
        $payment = $appointment->payments()->firstOrFail();
        $this->assertSame(PaymentTransaction::FAILED, $payment->status);
        $this->assertSame('checkout_initialization_failed', $payment->failure_code);
        $this->assertSame(Appointment::EXPIRED, $appointment->status);
        $this->assertDatabaseHas('appointment_email_outbox', ['appointment_id' => $appointment->id, 'email_type' => 'payment_failed']);
        $this->assertDatabaseMissing('appointment_email_outbox', ['appointment_id' => $appointment->id, 'email_type' => 'booking_initiated']);
        Log::shouldHaveReceived('warning')->with('Safepay checkout API request failed.', \Mockery::on(fn (array $context) =>
            $context['stage'] === 'create_payment_tracker'
            && $context['http_status'] === 401
            && $context['provider_error_codes'] === ['invalid_api_key']
            && ! array_key_exists('api_key', $context)
            && ! array_key_exists('secret_key', $context)
        ));

        $this->actingAs($this->patient)->get(route('appointments.show', $appointment))
            ->assertOk()->assertSee('no payment was taken')->assertSee('Book another time');
    }

    public function test_resubmitting_a_failed_booking_returns_to_a_fresh_slot_form_instead_of_a_conflict_page(): void
    {
        $start = CarbonImmutable::now('Asia/Karachi')->addDays(4)->setTime(10, 0);
        $this->profile->availabilityPeriods()->create(['day_of_week' => $start->dayOfWeek, 'starts_at' => '09:00', 'ends_at' => '12:00']);
        $option = $this->profile->consultationOptions()->create(['type' => 'online', 'duration_minutes' => 30, 'fee' => 100, 'is_active' => true]);
        $key = (string) Str::uuid();
        $appointment = Appointment::create([
            'patient_user_id' => $this->patient->id, 'doctor_profile_id' => $this->profile->id,
            'consultation_option_id' => $option->id, 'consultation_type' => 'online', 'duration_minutes' => 30,
            'starts_at' => $start->utc(), 'ends_at' => $start->addMinutes(30)->utc(), 'timezone' => 'Asia/Karachi',
            'amount_minor' => 10000, 'currency' => 'PKR', 'status' => Appointment::EXPIRED,
            'idempotency_key' => $key,
        ]);
        $appointment->payments()->create([
            'payer_user_id' => $this->patient->id, 'provider' => 'safepay', 'status' => PaymentTransaction::FAILED,
            'amount_minor' => 10000, 'currency' => 'PKR', 'idempotency_key' => (string) Str::uuid(),
            'failure_code' => 'checkout_initialization_failed',
        ]);

        $this->actingAs($this->patient)->post(route('appointments.store', $this->profile), [
            'consultation_option_id' => $option->id, 'starts_at' => $start->format('Y-m-d\\TH:i'), 'idempotency_key' => $key,
        ])->assertRedirect(route('appointments.create', $this->profile))
            ->assertSessionHasErrors('appointment');
    }

    public function test_missing_payment_setup_does_not_hide_booking_and_returns_a_checkout_error(): void
    {
        config(['services.safepay.environment' => 'sandbox', 'services.safepay.production_enabled' => false,
            'services.safepay.api_key' => null, 'services.safepay.secret_key' => null,
            'services.safepay.webhook_secret' => null]);
        $start = CarbonImmutable::now('Asia/Karachi')->addDays(3)->setTime(10, 0);
        $this->profile->availabilityPeriods()->create(['day_of_week' => $start->dayOfWeek, 'starts_at' => '09:00', 'ends_at' => '12:00']);
        $option = $this->profile->consultationOptions()->create(['type' => 'online', 'duration_minutes' => 30, 'fee' => 2500, 'is_active' => true]);

        $this->get(route('doctors.index'))->assertOk()->assertSee('Doctor Test');
        $this->get(route('doctors.show', $this->profile))->assertOk()->assertDontSee('Book an appointment');
        $this->actingAs($this->patient)->get(route('appointments.create', $this->profile))->assertOk()
            ->assertSee('Choose a consultation and time')->assertSee('Online consultation');
        $this->from(route('appointments.create', $this->profile))->actingAs($this->patient)
            ->post(route('appointments.store', $this->profile), [
                'consultation_option_id' => $option->id,
                'starts_at' => $start->format('Y-m-d\\TH:i'),
                'idempotency_key' => (string) Str::uuid(),
            ])->assertSessionHasErrors('payment');
        $this->assertDatabaseCount('appointments', 0);
        $this->assertDatabaseCount('payment_transactions', 0);
    }

    public function test_public_doctor_profile_separates_profile_from_booking_and_explains_missing_schedule_setup(): void
    {
        $this->profile->forceFill(['availability_published_at' => null])->save();

        $this->get(route('doctors.show', $this->profile))
            ->assertOk()
            ->assertDontSee('Book an appointment')
            ->assertSee('has not published consultation options yet');

        $this->actingAs($this->patient)->get(route('appointments.create', $this->profile))
            ->assertOk()
            ->assertSee('has not configured a consultation option or published appointment times yet');
    }

    public function test_schedule_must_be_published_and_booking_requires_review_then_confirm(): void
    {
        $start = CarbonImmutable::now('Asia/Karachi')->addDays(3)->setTime(10, 0);
        $this->profile->availabilityPeriods()->create(['day_of_week' => $start->dayOfWeek, 'starts_at' => '09:00', 'ends_at' => '12:00']);
        $option = $this->profile->consultationOptions()->create(['type' => 'online', 'duration_minutes' => 30, 'fee' => 0, 'is_active' => true]);
        $this->profile->forceFill(['availability_published_at' => null])->save();

        $this->get(route('doctors.show', $this->profile))->assertOk()->assertSee('Appointment times are not currently published.');
        $this->actingAs($this->patient)->get(route('appointments.create', $this->profile))->assertOk();
        $this->actingAs($this->patient)->post(route('appointments.store', $this->profile), [
            'consultation_option_id' => $option->id, 'starts_at' => $start->format('Y-m-d\\TH:i'), 'idempotency_key' => (string) Str::uuid(),
        ])->assertNotFound();

        $this->profile->forceFill(['availability_published_at' => now()])->save();
        $key = (string) Str::uuid();
        $payload = ['consultation_option_id' => $option->id, 'starts_at' => $start->format('Y-m-d\\TH:i'), 'idempotency_key' => $key];
        $this->actingAs($this->patient)->post(route('appointments.review', $this->profile), $payload)->assertOk()->assertSee('Confirm appointment');
        $this->actingAs($this->patient)->post(route('appointments.store', $this->profile), $payload)->assertRedirect();
        $this->assertSame(1, Appointment::count());

        $doctorPayload = ['consultation_option_id' => $option->id, 'starts_at' => $start->format('Y-m-d\\TH:i'), 'idempotency_key' => (string) Str::uuid()];
        $this->actingAs($this->doctor)->post(route('appointments.review', $this->profile), $doctorPayload)->assertForbidden();
    }

    public function test_clicking_a_slot_holds_it_for_review_and_blocks_other_patients(): void
    {
        $start = CarbonImmutable::now('Asia/Karachi')->addDays(4)->setTime(10, 0);
        $this->profile->availabilityPeriods()->create(['day_of_week' => $start->dayOfWeek, 'starts_at' => '09:00', 'ends_at' => '12:00']);
        $option = $this->profile->consultationOptions()->create(['type' => 'online', 'duration_minutes' => 30, 'fee' => 2500, 'is_active' => true]);
        $key = (string) Str::uuid();
        $payload = ['consultation_option_id' => $option->id, 'starts_at' => $start->format('Y-m-d\\TH:i'), 'idempotency_key' => $key];

        $this->actingAs($this->patient)->post(route('appointments.review', $this->profile), $payload)
            ->assertOk()->assertSee('Your time is held for you')->assertSee('Confirm and continue to payment');
        $hold = AppointmentSlotHold::firstOrFail();
        $this->assertSame(AppointmentSlotHold::HELD, $hold->status);
        $this->assertGreaterThan(now('UTC')->timestamp, $hold->expires_at->timestamp);
        $this->assertDatabaseCount('appointments', 0);

        $otherPatient = $this->makeUser('second-patient', User::ROLE_PARENT);
        $this->actingAs($otherPatient)->post(route('appointments.review', $this->profile), array_merge($payload, ['idempotency_key' => (string) Str::uuid()]))
            ->assertSessionHasErrors('slot');
        $slots = $this->getJson(route('doctors.slots', $this->profile).'?date='.$start->toDateString().'&consultation_option_id='.$option->id)
            ->assertOk()->json('slots');
        $this->assertNotContains($start->format('Y-m-d\\TH:i'), array_column($slots, 'value'));
        $this->assertDatabaseCount('appointment_slot_holds', 1);
    }

    public function test_confirming_a_held_paid_slot_creates_one_pending_safepay_checkout(): void
    {
        config(['services.safepay.environment' => 'sandbox', 'services.safepay.production_enabled' => false,
            'services.safepay.api_key' => 'sandbox-key', 'services.safepay.secret_key' => 'sandbox-secret',
            'services.safepay.webhook_secret' => 'sandbox-webhook']);
        Http::fakeSequence()->push(['data' => ['tracker' => ['token' => 'held-track-created']]])->push(['data' => 'held-tbt-created']);
        $start = CarbonImmutable::now('Asia/Karachi')->addDays(5)->setTime(10, 0);
        $this->profile->availabilityPeriods()->create(['day_of_week' => $start->dayOfWeek, 'starts_at' => '09:00', 'ends_at' => '12:00']);
        $option = $this->profile->consultationOptions()->create(['type' => 'online', 'duration_minutes' => 30, 'fee' => 2500, 'is_active' => true]);
        $payload = ['consultation_option_id' => $option->id, 'starts_at' => $start->format('Y-m-d\\TH:i'), 'idempotency_key' => (string) Str::uuid()];

        $this->actingAs($this->patient)->post(route('appointments.review', $this->profile), $payload)->assertOk();
        $hold = AppointmentSlotHold::firstOrFail();
        $this->actingAs($this->patient)->post(route('appointments.store', $this->profile), $payload)->assertRedirect();

        $appointment = Appointment::firstOrFail();
        $payment = $appointment->payments()->firstOrFail();
        $this->assertSame(AppointmentSlotHold::CONVERTED, $hold->fresh()->status);
        $this->assertSame(Appointment::PENDING_PAYMENT, $appointment->status);
        $this->assertSame(PaymentTransaction::PENDING, $payment->status);
        $this->assertNotNull($payment->checkout_url);
        $this->assertGreaterThan(now('UTC')->timestamp, $hold->expires_at->timestamp);
        $this->actingAs($this->patient)->post(route('appointments.store', $this->profile), $payload)->assertRedirect();
        $this->assertDatabaseCount('appointments', 1);
        $this->assertDatabaseCount('payment_transactions', 1);
    }

    public function test_releasing_a_slot_hold_makes_the_time_available_again(): void
    {
        $start = CarbonImmutable::now('Asia/Karachi')->addDays(4)->setTime(10, 0);
        $this->profile->availabilityPeriods()->create(['day_of_week' => $start->dayOfWeek, 'starts_at' => '09:00', 'ends_at' => '12:00']);
        $option = $this->profile->consultationOptions()->create(['type' => 'online', 'duration_minutes' => 30, 'fee' => 0, 'is_active' => true]);
        $payload = ['consultation_option_id' => $option->id, 'starts_at' => $start->format('Y-m-d\\TH:i'), 'idempotency_key' => (string) Str::uuid()];

        $this->actingAs($this->patient)->post(route('appointments.review', $this->profile), $payload)->assertOk();
        $hold = AppointmentSlotHold::firstOrFail();
        $this->post(route('appointments.slot-holds.release', $hold))->assertRedirect(route('appointments.create', $this->profile));
        $this->assertSame(AppointmentSlotHold::RELEASED, $hold->fresh()->status);

        $otherPatient = $this->makeUser('second-patient', User::ROLE_PARENT);
        $this->actingAs($otherPatient)->post(route('appointments.review', $this->profile), array_merge($payload, ['idempotency_key' => (string) Str::uuid()]))->assertOk();
        $this->assertDatabaseCount('appointment_slot_holds', 2);
    }

    public function test_patient_payment_history_and_paid_receipt_are_owned(): void
    {
        $start = CarbonImmutable::now('Asia/Karachi')->addDays(3)->setTime(10, 0);
        $appointment = Appointment::create([
            'patient_user_id' => $this->patient->id, 'doctor_profile_id' => $this->profile->id, 'consultation_type' => 'online',
            'duration_minutes' => 30, 'starts_at' => $start->utc(), 'ends_at' => $start->addMinutes(30)->utc(), 'timezone' => 'Asia/Karachi',
            'amount_minor' => 250000, 'currency' => 'PKR', 'status' => Appointment::CONFIRMED, 'idempotency_key' => (string) Str::uuid(),
        ]);
        $payment = $appointment->payments()->create([
            'payer_user_id' => $this->patient->id, 'provider' => 'safepay', 'status' => PaymentTransaction::PAID,
            'amount_minor' => 250000, 'currency' => 'PKR', 'idempotency_key' => (string) Str::uuid(), 'paid_at' => now(), 'provider_reference' => 'receipt-test',
        ]);
        $this->actingAs($this->patient)->get(route('payments.history'))->assertOk()->assertSee('receipt-test');
        $this->actingAs($this->patient)->get(route('payments.receipt', $payment))->assertOk()->assertSee('Receipt #')->assertSee('Print / Save as PDF');
        $other = $this->makeUser('other-payer', User::ROLE_PARENT);
        $this->actingAs($other)->get(route('payments.receipt', $payment))->assertNotFound();
    }

    public function test_doctor_can_reschedule_and_cancel_only_their_own_appointment_with_reason(): void
    {
        $start = CarbonImmutable::now('Asia/Karachi')->addDays(7)->setTime(10, 0);
        $this->profile->availabilityPeriods()->create(['day_of_week' => $start->dayOfWeek, 'starts_at' => '09:00', 'ends_at' => '12:00']);
        $this->profile->consultationOptions()->create(['type' => 'online', 'duration_minutes' => 30, 'fee' => 0, 'is_active' => true]);
        $appointment = Appointment::create([
            'patient_user_id' => $this->patient->id, 'doctor_profile_id' => $this->profile->id, 'consultation_type' => 'online',
            'duration_minutes' => 30, 'starts_at' => $start->utc(), 'ends_at' => $start->addMinutes(30)->utc(), 'timezone' => 'Asia/Karachi',
            'amount_minor' => 0, 'currency' => 'PKR', 'status' => Appointment::CONFIRMED,
            'slot_lock_key' => 'doctor:'.$this->profile->id.':'.$start->utc()->format('YmdHis'), 'idempotency_key' => (string) Str::uuid(),
        ]);
        $next = $start->addDays(7);
        $this->actingAs($this->doctor)->get(route('doctor.appointments.show', $appointment))->assertOk()->assertSee('Reschedule appointment');
        $this->actingAs($this->doctor)->post(route('doctor.appointments.reschedule', $appointment), ['starts_at' => $next->format('Y-m-d\\TH:i')])->assertRedirect();
        $this->assertSame(Appointment::RESCHEDULED, $appointment->fresh()->status);
        $this->assertSame($next->utc()->timestamp, $appointment->fresh()->startsAtUtc()->timestamp);
        $this->actingAs($this->doctor)->post(route('doctor.appointments.cancel', $appointment), ['reason' => 'Clinic schedule changed'])->assertRedirect();
        $this->assertSame(Appointment::CANCELLED, $appointment->fresh()->status);
        $this->assertSame('Clinic schedule changed', $appointment->fresh()->cancellation_reason);
        $this->assertDatabaseHas('appointment_events', ['appointment_id' => $appointment->id, 'event_type' => 'cancelled_by_doctor']);
        $otherDoctor = $this->makeUser('other-doctor', User::ROLE_APPROVED_DOCTOR);
        $this->actingAs($otherDoctor)->post(route('doctor.appointments.cancel', $appointment), ['reason' => 'Not my patient'])->assertForbidden();
    }

    public function test_authentication_token_failure_fails_pending_checkout_and_releases_slot_but_late_payment_is_reviewed(): void
    {
        config(['services.safepay.environment' => 'sandbox', 'services.safepay.production_enabled' => false,
            'services.safepay.api_key' => 'sandbox-key', 'services.safepay.secret_key' => 'sandbox-secret',
            'services.safepay.webhook_secret' => 'sandbox-webhook']);
        Http::fakeSequence()
            ->push(['data' => ['tracker' => ['token' => 'tracker-before-token-failure']]])
            ->push(['message' => 'temporary token service error'], 503);
        $start = CarbonImmutable::now('Asia/Karachi')->addDays(4)->setTime(10, 0);
        $this->profile->availabilityPeriods()->create(['day_of_week' => $start->dayOfWeek, 'starts_at' => '09:00', 'ends_at' => '12:00']);
        $option = $this->profile->consultationOptions()->create(['type' => 'online', 'duration_minutes' => 30, 'fee' => 2500, 'is_active' => true]);

        $this->actingAs($this->patient)->post(route('appointments.store', $this->profile), [
            'consultation_option_id' => $option->id,
            'starts_at' => $start->format('Y-m-d\\TH:i'),
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect();

        $appointment = Appointment::firstOrFail();
        $payment = $appointment->payments()->firstOrFail();
        $this->assertSame(PaymentTransaction::FAILED, $payment->status);
        $this->assertSame('tracker-before-token-failure', $payment->provider_reference);
        $this->assertSame('checkout_initialization_failed', $payment->failure_code);
        $this->assertNotNull($payment->failed_at);
        $this->assertSame(Appointment::EXPIRED, $appointment->status);
        $this->assertNull($appointment->slot_lock_key);
        $this->assertLessThanOrEqual(now('UTC')->timestamp, $appointment->holdExpiresAtUtc()->timestamp);
        $this->assertDatabaseHas('appointment_events', [
            'appointment_id' => $appointment->id,
            'event_type' => 'payment_initialization_failed',
            'to_status' => Appointment::EXPIRED,
        ]);

        // A genuine signed success delivered later must retain the existing manual-review behavior.
        $payload = ['token' => 'late-event-1', 'type' => 'payment.succeeded', 'data' => [
            'tracker' => 'tracker-before-token-failure', 'amount' => 250000, 'currency' => 'PKR',
        ]];
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha512', $body, 'sandbox-webhook');
        $this->call('POST', route('webhooks.safepay'), [], [], [], [
            'CONTENT_TYPE' => 'application/json', 'HTTP_X_SFPY_SIGNATURE' => $signature,
        ], $body)->assertOk();

        $this->assertSame(PaymentTransaction::PAID, $payment->fresh()->status);
        $this->assertSame(Appointment::PENDING_CONFIRMATION, $appointment->fresh()->status);
        $this->assertNull($appointment->fresh()->slot_lock_key);
    }

    public function test_checkout_failure_cannot_downgrade_a_payment_already_confirmed_by_webhook(): void
    {
        $start = CarbonImmutable::now('Asia/Karachi')->addDays(2)->setTime(10, 0);
        $appointment = Appointment::create([
            'patient_user_id' => $this->patient->id, 'doctor_profile_id' => $this->profile->id,
            'consultation_type' => 'online', 'duration_minutes' => 30, 'starts_at' => $start->utc(), 'ends_at' => $start->addMinutes(30)->utc(),
            'timezone' => 'Asia/Karachi', 'amount_minor' => 250000, 'currency' => 'PKR', 'status' => Appointment::PENDING_PAYMENT,
            'hold_expires_at' => now('UTC')->addMinutes(50), 'slot_lock_key' => 'doctor:'.$this->profile->id.':'.$start->utc()->format('YmdHis'),
            'idempotency_key' => (string) Str::uuid(),
        ]);
        $payment = $appointment->payments()->create([
            'payer_user_id' => $this->patient->id, 'provider' => 'safepay', 'status' => PaymentTransaction::PENDING,
            'amount_minor' => 250000, 'currency' => 'PKR', 'idempotency_key' => (string) Str::uuid(),
        ]);
        $gateway = \Mockery::mock(SafepayPaymentGateway::class);
        $gateway->shouldReceive('createHostedCheckout')->once()->andReturnUsing(function (...$arguments) use ($payment, $appointment): array {
            $arguments[5]('tracker-race');
            DB::transaction(function () use ($payment, $appointment): void {
                PaymentTransaction::whereKey($payment->id)->lockForUpdate()->update(['status' => PaymentTransaction::PAID, 'paid_at' => now()]);
                Appointment::whereKey($appointment->id)->lockForUpdate()->update(['status' => Appointment::CONFIRMED, 'hold_expires_at' => null]);
            });
            throw new \RuntimeException('Token request failed after webhook confirmation.');
        });
        $this->app->instance(SafepayPaymentGateway::class, $gateway);

        $result = app(PaymentService::class)->startSafepayCheckout($payment, 'https://example.test/return', 'https://example.test/cancel');
        $this->assertSame(PaymentTransaction::PAID, $result->fresh()->status);
        $this->assertSame(Appointment::CONFIRMED, $appointment->fresh()->status);
    }

    public function test_second_patient_cannot_reserve_a_slot_already_held(): void
    {
        $start = CarbonImmutable::now('Asia/Karachi')->addDays(5)->setTime(10, 0);
        $this->profile->availabilityPeriods()->create(['day_of_week' => $start->dayOfWeek, 'starts_at' => '09:00', 'ends_at' => '12:00']);
        $option = $this->profile->consultationOptions()->create(['type' => 'online', 'duration_minutes' => 30, 'fee' => 0, 'is_active' => true]);
        $payload = ['consultation_option_id' => $option->id, 'starts_at' => $start->format('Y-m-d\\TH:i')];
        $this->actingAs($this->patient)->post(route('appointments.store', $this->profile), $payload + ['idempotency_key' => (string) Str::uuid()])->assertRedirect();
        $anotherPatient = $this->makeUser('second', User::ROLE_PARENT);
        $this->actingAs($anotherPatient)->post(route('appointments.store', $this->profile), $payload + ['idempotency_key' => (string) Str::uuid()])->assertSessionHasErrors('slot');
        $this->assertSame(1, Appointment::count());
    }

    public function test_appointment_outbox_sends_a_signed_request_to_google_apps_script(): void
    {
        config([
            'services.google_apps_script.web_app_url' => 'https://script.google.com/macros/s/test-deployment/exec',
            'services.google_apps_script.shared_secret' => 'local-test-signing-secret',
            'mail.from.name' => 'Little Steps',
        ]);
        Http::fake(['https://script.google.com/macros/s/test-deployment/exec' => Http::response(['ok' => true, 'sent' => true], 200)]);
        $start = CarbonImmutable::now('Asia/Karachi')->addDays(3)->setTime(10, 0);
        $appointment = Appointment::create([
            'patient_user_id' => $this->patient->id, 'doctor_profile_id' => $this->profile->id,
            'consultation_type' => 'online', 'duration_minutes' => 30, 'starts_at' => $start->utc(),
            'ends_at' => $start->addMinutes(30)->utc(), 'timezone' => 'Asia/Karachi', 'amount_minor' => 0,
            'currency' => 'PKR', 'status' => Appointment::CONFIRMED, 'idempotency_key' => (string) Str::uuid(),
        ]);
        $outbox = $appointment->emailOutbox()->create([
            'recipient_user_id' => $this->patient->id, 'event_key' => 'appointment:'.$appointment->id.':appointment_confirmed:user:'.$this->patient->id,
            'email_type' => 'appointment_confirmed', 'status' => 'queued', 'snapshot' => ['appointment_reference' => 'LS-APT-'.$appointment->id],
        ]);
        $this->assertTrue(app(GoogleAppsScriptMailer::class)->sendAppointmentMessage($outbox));
        Http::assertSent(fn ($request) => $request->url() === 'https://script.google.com/macros/s/test-deployment/exec'
            && $request['signature'] === hash_hmac('sha256', $request['payload'], 'local-test-signing-secret')
            && json_decode($request['payload'], true)['to'] === $this->patient->email
            && str_contains(json_decode($request['payload'], true)['html'], 'LS-APT-'.$appointment->id));
        $this->assertSame('sent', $outbox->fresh()->status);
    }

    private function makeUser(string $prefix, string $role): User
    {
        $user = User::create(['first_name' => ucfirst($prefix), 'last_name' => 'Test', 'name' => ucfirst($prefix).' Test', 'email' => $prefix.'-'.Str::random(8).'@example.test', 'password' => bcrypt('StrongPassword123')]);
        $user->forceFill(['role' => $role])->save();
        $user->markEmailAsVerified();
        return $user;
    }
}


