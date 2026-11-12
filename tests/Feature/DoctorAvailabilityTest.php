<?php

namespace Tests\Feature;

use App\Models\DoctorAvailabilityPeriod;
use App\Models\DoctorProfile;
use App\Models\DoctorAppointmentSlot;
use App\Models\DoctorUnavailablePeriod;
use App\Models\MedicalSpecialty;
use App\Models\User;
use App\Services\DoctorAvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DoctorAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private User $doctor;
    private DoctorProfile $profile;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->doctor = User::create([
            'first_name' => 'Amina', 'last_name' => 'Doctor', 'name' => 'Amina Doctor',
            'email' => 'module7@example.test', 'password' => bcrypt('StrongPassword123'),
        ]);
        $this->doctor->forceFill(['role' => User::ROLE_APPROVED_DOCTOR])->save();
        $this->doctor->markEmailAsVerified();
        $this->profile = $this->doctor->doctorProfile()->create([
            'phone' => '+923001234567', 'professional_title' => 'Doctor',
            'specialty' => 'Pediatrics', 'qualification' => 'MBBS', 'institution' => 'Test University',
            'years_experience' => 5, 'city' => 'Lahore', 'license_number' => 'MODULE7-LICENSE',
            'issuing_authority' => 'Council', 'status' => DoctorProfile::STATUS_APPROVED,
            'timezone' => 'Asia/Karachi',
        ]);
    }

    public function test_only_admin_can_publish_and_only_active_approved_profile_is_public(): void
    {
        $this->actingAs($this->doctor)->patch(route('admin.doctors.visibility', $this->profile), ['is_public' => 1])->assertForbidden();
        $admin = $this->makeUser('module7-admin@example.test', User::ROLE_ADMIN);
        $this->actingAs($admin)->patch(route('admin.doctors.visibility', $this->profile), ['is_public' => 1])->assertRedirect();
        $this->get(route('doctors.show', $this->profile))->assertOk()->assertSee('Amina Doctor')->assertSee('Verified doctor');

        $this->profile->update(['status' => DoctorProfile::STATUS_SUSPENDED]);
        $this->get(route('doctors.show', $this->profile))->assertNotFound();
        $this->assertDatabaseHas('doctor_profiles', ['id' => $this->profile->id, 'is_public' => 1]);
    }

    public function test_doctor_can_save_consultation_options_and_weekly_periods_with_breaks(): void
    {
        $this->actingAs($this->doctor)->put(route('doctor.availability.consultations'), ['consultations' => [
            ['type' => 'online', 'duration_minutes' => 30, 'fee' => '2500', 'is_active' => 1],
        ]])->assertRedirect();
        $this->assertDatabaseHas('doctor_consultation_options', ['doctor_profile_id' => $this->profile->id, 'type' => 'online', 'fee' => 2500]);

        $this->put(route('doctor.availability.weekly'), ['periods' => [[
            'day_of_week' => 1, 'starts_at' => '09:00', 'ends_at' => '17:00',
            'breaks' => [['label' => 'Lunch', 'starts_at' => '13:00', 'ends_at' => '14:00']],
        ]]])->assertRedirect();
        $period = DoctorAvailabilityPeriod::where('doctor_profile_id', $this->profile->id)->firstOrFail();
        $this->assertDatabaseHas('doctor_availability_breaks', ['availability_period_id' => $period->id, 'label' => 'Lunch']);

        $this->put(route('doctor.availability.weekly'), ['periods' => [
            ['day_of_week' => 1, 'starts_at' => '09:00', 'ends_at' => '12:00'],
            ['day_of_week' => 1, 'starts_at' => '11:00', 'ends_at' => '15:00'],
        ]])->assertSessionHasErrors();
        $this->assertSame(1, $this->profile->availabilityPeriods()->count());
    }

    public function test_weekly_schedule_is_a_draft_until_doctor_publishes_and_edits_unpublish_it(): void
    {
        $this->actingAs($this->doctor)->put(route('doctor.availability.weekly'), ['periods' => [[
            'day_of_week' => 1, 'starts_at' => '09:00', 'ends_at' => '12:00',
        ]]])->assertRedirect();
        $this->profile->consultationOptions()->create(['type' => 'online', 'duration_minutes' => 30, 'is_active' => true]);
        $this->profile->update(['is_public' => true]);
        $this->assertNull($this->profile->fresh()->availability_published_at);

        $this->actingAs($this->doctor)->post(route('doctor.availability.publish'))->assertRedirect();
        $this->assertNotNull($this->profile->fresh()->availability_published_at);

        $this->actingAs($this->doctor)->put(route('doctor.availability.weekly'), ['periods' => [[
            'day_of_week' => 1, 'starts_at' => '10:00', 'ends_at' => '13:00',
        ]]])->assertRedirect();
        $this->assertNull($this->profile->fresh()->availability_published_at);
    }

    public function test_availability_service_subtracts_breaks_and_date_specific_leave(): void
    {
        $period = $this->profile->availabilityPeriods()->create(['day_of_week' => 1, 'starts_at' => '09:00', 'ends_at' => '17:00']);
        $period->breaks()->create(['label' => 'Lunch', 'starts_at' => '13:00', 'ends_at' => '14:00']);
        $this->profile->unavailablePeriods()->create([
            'type' => 'leave', 'label' => 'Appointment',
            'starts_at' => CarbonImmutable::parse('2026-11-09 10:00', 'Asia/Karachi')->utc(),
            'ends_at' => CarbonImmutable::parse('2026-11-09 11:00', 'Asia/Karachi')->utc(),
            'all_day' => false, 'timezone' => 'Asia/Karachi',
        ]);
        $open = app(DoctorAvailabilityService::class)->intervalsForDate($this->profile, CarbonImmutable::parse('2026-11-09', 'Asia/Karachi'));
        $this->assertSame([
            ['09:00', '10:00'], ['11:00', '13:00'], ['14:00', '17:00'],
        ], $open->map(fn ($interval) => [$interval['start']->format('H:i'), $interval['end']->format('H:i')])->all());
        $this->profile->update(['is_public' => true]);
        $this->profile->consultationOptions()->create(['type' => 'online', 'duration_minutes' => 30, 'is_active' => true]);
        $service = app(DoctorAvailabilityService::class);
        $this->assertTrue($service->canFitDuration($this->profile, CarbonImmutable::parse('2026-11-09 11:30', 'Asia/Karachi'), 30, 'online'));
        $this->assertFalse($service->canFitDuration($this->profile, CarbonImmutable::parse('2026-11-09 12:45', 'Asia/Karachi'), 30, 'online'));
    }

    public function test_doctor_can_save_timezone_aware_leave_and_cannot_remove_another_doctors_period(): void
    {
        $start = CarbonImmutable::now('Asia/Karachi')->addDays(3)->setTime(10, 0);
        $payload = [
            'type' => 'holiday', 'label' => 'Clinic closed',
            'starts_at' => $start->format('Y-m-d\\TH:i'),
            'ends_at' => $start->addHours(5)->format('Y-m-d\\TH:i'),
        ];
        $this->actingAs($this->doctor)->post(route('doctor.availability.unavailable.store'), $payload)->assertRedirect();
        $leave = $this->profile->unavailablePeriods()->firstOrFail();
        $this->assertSame('10:00', $leave->localStartsAt()->format('H:i'));
        $this->post(route('doctor.availability.unavailable.store'), $payload)->assertSessionHasErrors('starts_at');

        $other = $this->makeUser('other-module7@example.test', User::ROLE_APPROVED_DOCTOR);
        $this->actingAs($other)->delete(route('doctor.availability.unavailable.destroy', $leave))->assertNotFound();
        $this->actingAs($this->doctor)->delete(route('doctor.availability.unavailable.destroy', $leave))->assertRedirect();
        $this->assertDatabaseMissing('doctor_unavailable_periods', ['id' => $leave->id]);
    }

    public function test_doctor_can_publish_exact_dated_slots_and_close_unbooked_time(): void
    {
        $this->profile->update(['is_public' => true]);
        $option = $this->profile->consultationOptions()->create(['type' => 'online', 'duration_minutes' => 30, 'fee' => 1000, 'is_active' => true]);
        $start = CarbonImmutable::now('Asia/Karachi')->addDays(4)->setTime(14, 0);
        $payload = [
            'consultation_option_id' => $option->id, 'date' => $start->toDateString(),
            'starts_at' => '14:00', 'ends_at' => '14:30', 'meeting_url' => 'https://meet.example.test/doctor',
        ];
        $this->actingAs($this->doctor)->get(route('doctor.availability.edit'))->assertOk()->assertSee('Dated appointment slots');
        $this->post(route('doctor.availability.slots.store'), $payload)->assertRedirect()->assertSessionHasNoErrors();
        $slot = DoctorAppointmentSlot::firstOrFail();
        $this->assertSame(DoctorAppointmentSlot::OPEN, $slot->status);
        $this->assertTrue($this->profile->fresh()->hasPublishedAvailability());
        $this->post(route('doctor.availability.slots.store'), $payload)->assertSessionHasErrors('starts_at');
        $this->delete(route('doctor.availability.slots.close', $slot))->assertRedirect();
        $this->assertSame(DoctorAppointmentSlot::CLOSED, $slot->fresh()->status);
        $this->assertFalse($this->profile->fresh()->hasPublishedAvailability());
    }

    public function test_specialty_catalog_rejects_duplicate_specialties_and_invalid_profile_ids(): void
    {
        $admin = $this->makeUser('module7-specialty-admin@example.test', User::ROLE_ADMIN);
        $this->actingAs($admin)->post(route('admin.doctors.specialties.store'), ['name' => 'Speech Therapy'])->assertRedirect();
        $specialty = MedicalSpecialty::where('name', 'Speech Therapy')->firstOrFail();
        $this->post(route('admin.doctors.specialties.store'), ['name' => 'Speech Therapy'])->assertSessionHasErrors('name');
        $this->post(route('admin.doctors.specialties.store'), ['name' => 'speech therapy'])->assertSessionHasErrors('name');
        $this->actingAs($this->doctor)->get(route('doctor.availability.edit'))->assertOk()->assertSee('Weekly working periods');
        $this->actingAs($this->doctor)->put(route('doctor.profile.update'), $this->profilePayload() + [
            'specialty_ids_present' => 1, 'specialty_ids' => [99999],
        ])->assertSessionHasErrors('specialty_ids.0');
        $this->put(route('doctor.profile.update'), $this->profilePayload() + [
            'profile_photo' => UploadedFile::fake()->create('unsafe.svg', 1, 'image/svg+xml'),
        ])->assertSessionHasErrors('profile_photo');
        $this->assertDatabaseHas('medical_specialties', ['id' => $specialty->id]);
    }

    private function makeUser(string $email, string $role): User
    {
        $user = User::create(['first_name' => 'Test', 'last_name' => 'Admin', 'name' => 'Test Admin', 'email' => $email, 'password' => bcrypt('StrongPassword123')]);
        $user->forceFill(['role' => $role])->save();
        $user->markEmailAsVerified();
        return $user;
    }

    private function profilePayload(): array
    {
        return [
            'first_name' => 'Amina', 'last_name' => 'Doctor', 'phone' => $this->profile->phone,
            'professional_title' => $this->profile->professional_title, 'specialty' => $this->profile->specialty,
            'qualification' => $this->profile->qualification, 'institution' => $this->profile->institution,
            'years_experience' => $this->profile->years_experience, 'city' => $this->profile->city,
            'license_number' => $this->profile->license_number, 'issuing_authority' => $this->profile->issuing_authority,
            'license_expires_at' => '',
        ];
    }
}
