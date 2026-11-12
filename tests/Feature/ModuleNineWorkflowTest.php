<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\DoctorProfile;
use App\Models\PaymentTransaction;
use App\Models\PaymentRefund;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ModuleNineWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $patient;
    private User $doctor;
    private DoctorProfile $profile;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.google_apps_script.web_app_url' => '', 'services.google_apps_script.shared_secret' => '']);
        $this->patient = $this->user('patient', User::ROLE_PARENT);
        $this->doctor = $this->user('doctor', User::ROLE_APPROVED_DOCTOR);
        $this->profile = $this->doctor->doctorProfile()->create([
            'phone' => '+923001234567', 'professional_title' => 'Dr', 'specialty' => 'Pediatrics',
            'qualification' => 'MBBS', 'institution' => 'Test University', 'years_experience' => 5,
            'city' => 'Lahore', 'license_number' => 'MODULE9-LICENSE', 'issuing_authority' => 'Council',
            'status' => DoctorProfile::STATUS_APPROVED, 'is_public' => true, 'timezone' => 'Asia/Karachi',
        ]);
    }

    public function test_doctor_can_add_https_link_and_only_the_booked_patient_can_view_it(): void
    {
        $appointment = $this->appointment(Appointment::CONFIRMED);
        $this->actingAs($this->doctor)->put(route('doctor.appointments.meeting-link', $appointment), [
            'meeting_link' => 'https://meet.google.com/abc-defg-hij',
        ])->assertSessionHasNoErrors();

        $this->assertSame('https://meet.google.com/abc-defg-hij', $appointment->fresh()->meeting_link);
        $this->assertDatabaseHas('appointment_events', ['appointment_id' => $appointment->id, 'event_type' => 'meeting_link_added']);
        $this->assertDatabaseHas('appointment_email_outbox', ['appointment_id' => $appointment->id, 'recipient_user_id' => $this->patient->id, 'email_type' => 'meeting_link_added']);
        $this->actingAs($this->patient)->get(route('appointments.show', $appointment))->assertOk()->assertSee('https://meet.google.com/abc-defg-hij');
        $otherPatient = $this->user('other', User::ROLE_PARENT);
        $this->actingAs($otherPatient)->get(route('appointments.show', $appointment))->assertNotFound();
    }

    public function test_meeting_link_must_be_https_and_appointment_confirmed(): void
    {
        $appointment = $this->appointment(Appointment::CONFIRMED);
        $this->actingAs($this->doctor)->from(route('doctor.appointments.show', $appointment))
            ->put(route('doctor.appointments.meeting-link', $appointment), ['meeting_link' => 'http://meet.google.com/abc'])
            ->assertSessionHasErrors('meeting_link');

        $pending = $this->appointment(Appointment::PENDING_PAYMENT);
        $this->actingAs($this->doctor)->put(route('doctor.appointments.meeting-link', $pending), ['meeting_link' => 'https://meet.google.com/abc'])
            ->assertStatus(409);

        $unverified = $this->appointment(Appointment::CONFIRMED);
        $this->payment($unverified, PaymentTransaction::PENDING, 2500);
        $this->actingAs($this->doctor)->put(route('doctor.appointments.meeting-link', $unverified), ['meeting_link' => 'https://meet.google.com/abc'])
            ->assertStatus(409);
    }

    public function test_doctor_cannot_mark_visit_before_its_start_time(): void
    {
        $future = $this->appointment(Appointment::CONFIRMED, CarbonImmutable::now('UTC')->addDay());
        $this->actingAs($this->doctor)->patch(route('doctor.appointments.update', $future), ['status' => Appointment::COMPLETED])->assertStatus(409);
        $this->assertSame(Appointment::CONFIRMED, $future->fresh()->status);

        $past = $this->appointment(Appointment::CONFIRMED, CarbonImmutable::now('UTC')->subHour());
        $this->actingAs($this->doctor)->patch(route('doctor.appointments.update', $past), ['status' => Appointment::COMPLETED])->assertRedirect();
        $this->assertSame(Appointment::COMPLETED, $past->fresh()->status);
    }

    public function test_revenue_counts_verified_payments_and_subtracts_verified_refunds(): void
    {
        $completedPaid = $this->appointment(Appointment::COMPLETED, CarbonImmutable::now('UTC')->subDays(2));
        $refundablePayment = $this->payment($completedPaid, 'partially_refunded', 2500);
        PaymentRefund::create(['payment_transaction_id' => $refundablePayment->id, 'provider_event_id' => 'test-revenue-refund', 'provider_reference' => 'tracker', 'amount_minor' => 500, 'currency' => 'PKR', 'status' => 'succeeded', 'refunded_at' => now()]);
        $this->payment($completedPaid, PaymentTransaction::PAID, 1500);
        $completedPending = $this->appointment(Appointment::COMPLETED, CarbonImmutable::now('UTC')->subDay());
        $this->payment($completedPending, PaymentTransaction::PENDING, 9900);
        $cancelledPaid = $this->appointment(Appointment::CANCELLED, CarbonImmutable::now('UTC')->subDays(3));
        $this->payment($cancelledPaid, PaymentTransaction::PAID, 5000);
        $this->appointment(Appointment::PENDING_PAYMENT);
        $this->appointment(Appointment::EXPIRED, CarbonImmutable::now('UTC')->subDays(4));

        $this->actingAs($this->doctor)->get(route('doctor.appointments.index'))->assertOk()->assertSee('Upcoming')->assertSee('Pending')->assertSee('Completed')->assertSee('Cancelled')->assertSee('data-appointment-tab="upcoming"', false);
        $this->actingAs($this->doctor)->get(route('doctor.revenue'))->assertOk()->assertSee('Gross 90.00')->assertSee('Refunds 5.00')->assertSee('Net after refunds 85.00')->assertSee('Download CSV');
        $this->actingAs($this->doctor)->get(route('doctor.revenue', ['month' => now($this->profile->timezone)->month, 'year' => now($this->profile->timezone)->year]))
            ->assertOk()->assertSee('Gross 90.00')->assertSee('Apply filters');
        $csv = $this->actingAs($this->doctor)->get(route('doctor.revenue.csv'));
        $this->assertSame('text/csv; charset=UTF-8', $csv->headers->get('content-type'));
        $this->assertStringContainsString('2500', $csv->streamedContent());
        $this->assertStringContainsString('5000', $csv->streamedContent());
        $this->assertStringNotContainsString('9900', $csv->streamedContent());
    }

    public function test_parent_history_and_consulted_doctor_list_are_limited_to_their_completed_records(): void
    {
        $done = $this->appointment(Appointment::COMPLETED, CarbonImmutable::now('UTC')->subDay());
        $this->appointment(Appointment::CONFIRMED, CarbonImmutable::now('UTC')->addDay());
        $otherPatient = $this->user('another', User::ROLE_PARENT);
        $foreign = $this->appointment(Appointment::COMPLETED, CarbonImmutable::now('UTC')->subDays(2), $otherPatient);

        $this->actingAs($this->patient)->get(route('appointments.history'))->assertOk()->assertSee('Doctor Test')->assertDontSee('Another Test');
        $this->actingAs($this->patient)->get(route('appointments.doctors-consulted'))->assertOk()->assertSee('Doctor Test')->assertDontSee('Another Test');
        $this->assertSame(Appointment::COMPLETED, $done->status);
    }

    public function test_doctor_public_site_session_links_to_the_doctor_dashboard(): void
    {
        $this->actingAs($this->doctor)->getJson('/api/session')
            ->assertOk()->assertJsonPath('role', User::ROLE_APPROVED_DOCTOR)
            ->assertJsonPath('dashboard_url', route('doctor.dashboard'));
        $home = $this->actingAs($this->doctor)->get('/')->assertOk();
        $this->assertSame(realpath(public_path('index.html')), realpath($home->baseResponse->getFile()->getPathname()));
    }

    private function appointment(string $status, ?CarbonImmutable $start = null, ?User $patient = null): Appointment
    {
        $start ??= CarbonImmutable::now('UTC')->addDays(3);
        $patient ??= $this->patient;
        return Appointment::create([
            'patient_user_id' => $patient->id, 'doctor_profile_id' => $this->profile->id,
            'consultation_type' => 'online', 'duration_minutes' => 30,
            'starts_at' => $start, 'ends_at' => $start->addMinutes(30), 'timezone' => 'Asia/Karachi',
            'amount_minor' => 2500, 'currency' => 'PKR', 'status' => $status,
            'idempotency_key' => (string) Str::uuid(),
        ]);
    }

    private function payment(Appointment $appointment, string $status, int $amount): PaymentTransaction
    {
        return $appointment->payments()->create([
            'payer_user_id' => $appointment->patient_user_id, 'provider' => 'safepay', 'status' => $status,
            'amount_minor' => $amount, 'currency' => 'PKR', 'idempotency_key' => (string) Str::uuid(),
            'paid_at' => $status === PaymentTransaction::PAID ? now() : null,
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
