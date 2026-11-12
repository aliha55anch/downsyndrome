<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\DoctorProfile;
use App\Models\DoctorAppointmentSlot;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DoctorPublicBookingFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.google_apps_script.web_app_url' => '', 'services.google_apps_script.shared_secret' => '']);
    }

    public function test_public_directory_contains_only_live_approved_and_published_profiles(): void
    {
        $approved = $this->doctor('verified real clinician', User::ROLE_APPROVED_DOCTOR, DoctorProfile::STATUS_APPROVED, true)->doctorProfile;
        $approved->consultationOptions()->create(['type' => 'online', 'duration_minutes' => 30, 'fee' => 0, 'is_active' => true]);
        $pending = $this->doctor('pending applicant', User::ROLE_PENDING_DOCTOR, DoctorProfile::STATUS_PENDING, false)->doctorProfile;
        $unpublished = $this->doctor('unpublished clinician', User::ROLE_APPROVED_DOCTOR, DoctorProfile::STATUS_APPROVED, false)->doctorProfile;

        $this->get(route('doctors.index'))->assertOk()
            ->assertSee('Verified Real Clinician')->assertDontSee('Pending Applicant')->assertDontSee('Unpublished Clinician')
            ->assertDontSee('Dr. Ayesha Khan');
        $this->get(route('doctors.show', $approved))->assertOk()
            ->assertSee('Verified Real Clinician')->assertSee('MBBS, FCPS')->assertSee('Book appointment');
        $this->get(route('doctors.index'))->assertSee('Book appointment');
        $this->get(route('doctors.show', $pending))->assertNotFound();
        $this->get(route('doctors.show', $unpublished))->assertNotFound();

        $admin = $this->user('admin', User::ROLE_ADMIN);
        $this->actingAs($admin)->patch(route('admin.doctors.visibility', $approved), ['is_public' => false])->assertRedirect();
        $this->get(route('doctors.index'))->assertDontSee('Verified Real Clinician');

        $approved->update(['is_public' => true, 'status' => DoctorProfile::STATUS_SUSPENDED]);
        $approved->user()->firstOrFail()->forceFill(['role' => User::ROLE_PENDING_DOCTOR])->save();
        $this->get(route('doctors.index'))->assertDontSee('Verified Real Clinician');
        $this->get(route('doctors.show', $approved))->assertNotFound();

        $approved->update(['status' => DoctorProfile::STATUS_REJECTED]);
        $this->get(route('doctors.index'))->assertDontSee('Verified Real Clinician');
    }

    public function test_patient_can_browse_real_profile_load_schedule_slots_and_book_once(): void
    {
        $patient = $this->user('patient', User::ROLE_PARENT);
        $doctor = $this->doctor('real booking doctor', User::ROLE_APPROVED_DOCTOR, DoctorProfile::STATUS_APPROVED, true);
        $profile = $doctor->doctorProfile;
        $option = $profile->consultationOptions()->create([
            'type' => 'online', 'duration_minutes' => 30, 'fee' => 0, 'is_active' => true,
        ]);
        $start = CarbonImmutable::now('Asia/Karachi')->addDays(5)->setTime(10, 0);
        $profile->availabilityPeriods()->create([
            'day_of_week' => $start->dayOfWeek, 'starts_at' => '09:00', 'ends_at' => '12:00',
        ]);

        $this->get(route('doctors.index'))->assertOk()->assertSee('Real Booking Doctor');
        $this->get(route('doctors.show', $profile))->assertOk()->assertSee('Book appointment');
        $this->actingAs($patient)->get(route('appointments.create', $profile))->assertOk()
            ->assertSee('Choose a consultation and time')->assertSee('Online consultation')
            ->assertSee('name="starts_at" id="selected-start-at"', false)
            ->assertSee('selectedStart.value = submitter.dataset.startsAt;', false);
        $this->get(route('doctors.slots', $profile).'?date='.$start->toDateString().'&consultation_option_id='.$option->id)
            ->assertOk()->assertJsonFragment(['value' => $start->format('Y-m-d\\TH:i')]);

        $payload = [
            'consultation_option_id' => $option->id,
            'starts_at' => $start->format('Y-m-d\\TH:i'),
            'idempotency_key' => (string) Str::uuid(),
        ];
        $this->actingAs($patient)->post(route('appointments.review', $profile), $payload)->assertOk()->assertSee('Confirm appointment');
        $this->actingAs($patient)->post(route('appointments.store', $profile), $payload)->assertRedirect();
        $appointment = Appointment::firstOrFail();
        $this->assertSame($patient->id, $appointment->patient_user_id);
        $this->assertSame(Appointment::CONFIRMED, $appointment->status);
        $this->actingAs($patient)->get(route('appointments.show', $appointment))->assertOk()
            ->assertSee('Real Booking Doctor')->assertSee('confirmed');

        $this->from(route('appointments.create', $profile))->actingAs($patient)
            ->post(route('appointments.store', $profile), array_merge($payload, ['idempotency_key' => (string) Str::uuid()]))
            ->assertSessionHasErrors('slot');
        $otherPatient = $this->user('other patient', User::ROLE_PARENT);
        $this->actingAs($otherPatient)->get(route('appointments.show', $appointment))->assertNotFound();
    }

    public function test_doctor_can_browse_website_but_cannot_open_or_submit_patient_booking(): void
    {
        $doctor = $this->doctor('visitor doctor', User::ROLE_APPROVED_DOCTOR, DoctorProfile::STATUS_APPROVED, true);
        $profile = $doctor->doctorProfile;
        $profile->consultationOptions()->create(['type' => 'online', 'duration_minutes' => 30, 'fee' => 0, 'is_active' => true]);

        $this->actingAs($doctor)->get(route('doctor.dashboard'))->assertOk()->assertDontSee('View Website')->assertSee('Preview profile');
        $this->actingAs($doctor)->get(route('doctors.index'))->assertOk()->assertSee('Visitor Doctor');
        $this->actingAs($doctor)->get(route('doctors.show', $profile))->assertOk()->assertDontSee('Book appointment');
        $this->actingAs($doctor)->get(route('appointments.create', $profile))->assertOk()
            ->assertSee('Appointment booking is available only to patient accounts.')
            ->assertSee('Back to Doctor Dashboard');

        $response = $this->actingAs($doctor)->post(route('appointments.store', $profile), [
            'consultation_option_id' => $profile->consultationOptions()->first()->id,
            'starts_at' => CarbonImmutable::now('Asia/Karachi')->addDays(2)->setTime(10, 0)->format('Y-m-d\\TH:i'),
            'idempotency_key' => (string) Str::uuid(),
        ]);
        $response->assertForbidden();
        $this->actingAs($doctor)->post(route('dashboard.children.store'), [])->assertForbidden();
        $this->actingAs($doctor)->put(route('dashboard.profile.update'), [])->assertForbidden();
        $this->assertDatabaseCount('appointments', 0);

        $this->actingAs($doctor)->get(route('doctor.dashboard'))->assertOk();
    }

    public function test_care_switcher_only_appears_after_opening_professional_consultancy(): void
    {
        $doctor = $this->doctor('directory clinician', User::ROLE_APPROVED_DOCTOR, DoctorProfile::STATUS_APPROVED, true);
        $doctor->doctorProfile->consultationOptions()->create(['type' => 'online', 'duration_minutes' => 30, 'fee' => 0, 'is_active' => true]);
        $home = file_get_contents(public_path('index.html'));
        $this->assertIsString($home);
        $this->assertStringNotContainsString('care-switcher', $home);

        $this->get(route('doctors.index'))->assertOk()
            ->assertSee('Appointments')->assertSee('Professional Consultancy')->assertSee('Book appointment');

        $patient = $this->user('care switcher patient', User::ROLE_PARENT);
        $this->actingAs($patient)->get(route('appointments.index'))->assertOk()->assertDontSee('care-switcher');
    }

    public function test_patient_books_doctor_created_dated_slot_and_receives_its_link(): void
    {
        $patient = $this->user('slot patient', User::ROLE_PARENT);
        $doctor = $this->doctor('slot doctor', User::ROLE_APPROVED_DOCTOR, DoctorProfile::STATUS_APPROVED, true);
        $profile = $doctor->doctorProfile;
        $option = $profile->consultationOptions()->create(['type' => 'online', 'duration_minutes' => 30, 'fee' => 0, 'is_active' => true]);
        $start = CarbonImmutable::now('Asia/Karachi')->addDays(4)->setTime(11, 0);
        $slot = DoctorAppointmentSlot::create([
            'doctor_profile_id' => $profile->id, 'consultation_option_id' => $option->id,
            'starts_at' => $start->utc(), 'ends_at' => $start->addMinutes(30)->utc(), 'timezone' => 'Asia/Karachi',
            'meeting_url' => 'https://meet.example.test/doctor-slot', 'status' => DoctorAppointmentSlot::OPEN,
        ]);
        $this->actingAs($patient)->get(route('doctors.slots', $profile).'?date='.$start->toDateString().'&consultation_option_id='.$option->id)
            ->assertOk()->assertJsonFragment(['slot_id' => $slot->id]);
        $key = (string) Str::uuid();
        $payload = ['consultation_option_id' => $option->id, 'starts_at' => $start->format('Y-m-d\\TH:i'), 'idempotency_key' => $key, 'doctor_appointment_slot_id' => $slot->id];
        $this->actingAs($patient)->post(route('appointments.review', $profile), $payload)->assertOk();
        $this->actingAs($patient)->post(route('appointments.store', $profile), $payload)->assertRedirect();
        $appointment = Appointment::firstOrFail();
        $this->assertSame($slot->id, $appointment->doctor_appointment_slot_id);
        $this->assertSame('https://meet.example.test/doctor-slot', $appointment->meeting_link);
        $this->assertSame(DoctorAppointmentSlot::BOOKED, $slot->fresh()->status);
        $other = $this->user('other slot patient', User::ROLE_PARENT);
        $this->actingAs($other)->post(route('appointments.review', $profile), array_merge($payload, ['idempotency_key' => (string) Str::uuid()]))->assertSessionHasErrors('slot');
    }

    public function test_old_static_doctor_pages_no_longer_contain_demo_profiles(): void
    {
        $directory = file_get_contents(public_path('doctors.html'));
        $detail = file_get_contents(public_path('doctor_detail.html'));
        $admin = file_get_contents(public_path('admin_doctors.html'));

        $this->assertStringContainsString('/doctors', $directory);
        $this->assertStringNotContainsString('Dr. Ayesha Khan', $directory);
        $this->assertStringNotContainsString('Dr. Ayesha Khan', $detail);
        $this->assertStringNotContainsString('Dr. Ayesha Khan', $admin);
        $this->assertStringNotContainsString('defaultDoctors', file_get_contents(public_path('assets/js/data.js')));
    }

    private function doctor(string $prefix, string $role, string $status, bool $published): User
    {
        $user = $this->user($prefix, $role);
        $user->doctorProfile()->create([
            'phone' => '+923001234567', 'professional_title' => 'Dr.', 'specialty' => 'Pediatrics',
            'qualification' => 'MBBS, FCPS', 'institution' => 'Test University', 'years_experience' => 7,
            'city' => 'Lahore', 'license_number' => 'TEST-'.Str::upper(Str::random(12)),
            'issuing_authority' => 'Council', 'status' => $status, 'is_public' => $published,
            'timezone' => 'Asia/Karachi', 'availability_published_at' => $published ? now() : null,
        ]);
        return $user->fresh();
    }

    private function user(string $prefix, string $role): User
    {
        $name = ucwords($prefix);
        $user = User::create([
            'first_name' => Str::before($name, ' '), 'last_name' => 'Test', 'name' => $name,
            'email' => Str::slug($prefix).'-'.Str::random(8).'@example.test', 'password' => bcrypt('StrongPassword123'),
        ]);
        $user->forceFill(['role' => $role])->save();
        $user->markEmailAsVerified();
        return $user->fresh();
    }
}
