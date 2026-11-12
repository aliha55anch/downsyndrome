<?php

namespace Tests\Feature;

use App\Models\DoctorProfile;
use App\Models\Activity;
use App\Models\ActivitySection;
use App\Models\ActivityLesson;
use App\Models\ActivityTrackingPreference;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class DoctorVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Storage::fake('local');
    }

    public function test_parent_registration_still_creates_a_parent_and_doctor_application_cannot_assign_approved_role(): void
    {
        $this->post(route('register'), [
            'first_name' => 'Parent', 'last_name' => 'Example', 'email' => 'parent@example.test',
            'password' => 'StrongPassword123', 'password_confirmation' => 'StrongPassword123',
        ])->assertRedirect(route('verification.notice'));
        $this->assertDatabaseHas('users', ['email' => 'parent@example.test', 'role' => User::ROLE_PARENT]);
        $this->post(route('logout'))->assertRedirect('/');

        $this->post(route('doctor.register.store'), $this->doctorPayload('doctor@example.test') + ['role' => User::ROLE_APPROVED_DOCTOR])
            ->assertRedirect(route('verification.notice'));
        $doctor = User::where('email', 'doctor@example.test')->firstOrFail();
        $this->assertSame(User::ROLE_PENDING_DOCTOR, $doctor->role);
        $this->assertSame(DoctorProfile::STATUS_PENDING, $doctor->doctorProfile->status);
        $this->assertCount(2, $doctor->doctorProfile->documents);
        $this->assertDatabaseHas('doctor_verification_events', ['doctor_profile_id' => $doctor->doctorProfile->id, 'event_type' => 'application_submitted']);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $doctor->id, 'notifiable_type' => User::class]);
        $verificationUrl = URL::temporarySignedRoute('verification.verify', now()->addMinutes(10), [
            'id' => $doctor->id,
            'hash' => sha1($doctor->getEmailForVerification()),
        ]);
        $this->get($verificationUrl)->assertRedirect(route('doctor.dashboard'));
        $this->assertNotNull($doctor->fresh()->email_verified_at);
    }

    public function test_admin_can_review_approve_and_audit_a_complete_application_only_after_approving_documents(): void
    {
        $this->post(route('doctor.register.store'), $this->doctorPayload('review@example.test'))->assertRedirect(route('verification.notice'));
        $doctor = User::where('email', 'review@example.test')->firstOrFail();
        $doctor->markEmailAsVerified();
        $admin = $this->makeUser('admin@example.test', User::ROLE_ADMIN);

        $this->actingAs($admin)->patch(route('admin.doctors.status', $doctor->doctorProfile), ['status' => 'approved'])->assertStatus(422);
        $this->assertSame(DoctorProfile::STATUS_PENDING, $doctor->doctorProfile->fresh()->status);

        foreach ($doctor->doctorProfile->documents as $document) {
            $this->patch(route('admin.doctors.documents.review', [$doctor->doctorProfile, $document]), ['status' => 'approved'])->assertRedirect();
        }
        $this->patch(route('admin.doctors.status', $doctor->doctorProfile), ['status' => 'under_review'])->assertRedirect();
        $this->patch(route('admin.doctors.status', $doctor->doctorProfile), ['status' => 'approved'])->assertRedirect();

        $doctor->refresh();
        $this->assertSame(User::ROLE_APPROVED_DOCTOR, $doctor->role);
        $this->assertSame(DoctorProfile::STATUS_APPROVED, $doctor->doctorProfile->status);
        $this->assertSame($admin->id, $doctor->doctorProfile->reviewed_by);
        $this->assertNotNull($doctor->doctorProfile->reviewed_at);
        $this->assertDatabaseHas('doctor_verification_events', ['doctor_profile_id' => $doctor->doctorProfile->id, 'event_type' => 'status_changed', 'to_status' => 'approved', 'actor_id' => $admin->id]);
        $secondAdmin = $this->makeUser('second-admin@example.test', User::ROLE_ADMIN);
        $this->actingAs($secondAdmin)->delete(route('admin.users.destroy', $admin))->assertSessionHasErrors('user');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_rejection_requires_reason_and_doctor_can_resubmit_after_replacing_required_files(): void
    {
        $this->post(route('doctor.register.store'), $this->doctorPayload('reject@example.test'))->assertRedirect(route('verification.notice'));
        $doctor = User::where('email', 'reject@example.test')->firstOrFail();
        $doctor->markEmailAsVerified();
        $admin = $this->makeUser('review-admin@example.test', User::ROLE_ADMIN);
        $profile = $doctor->doctorProfile;
        $this->actingAs($admin)->patch(route('admin.doctors.status', $profile), ['status' => 'rejected'])->assertSessionHasErrors('reason');
        $this->patch(route('admin.doctors.status', $profile), ['status' => 'rejected', 'reason' => 'Please provide a clearer license scan.'])->assertRedirect();
        $this->assertSame('Please provide a clearer license scan.', $profile->fresh()->rejection_reason);

        $this->actingAs($doctor)->post(route('doctor.documents.store'), [
            'document_type' => 'license', 'document' => UploadedFile::fake()->create('replacement.pdf', 8, 'application/pdf'),
        ])->assertRedirect();
        $this->post(route('doctor.application.resubmit'))->assertRedirect();
        $this->assertSame(DoctorProfile::STATUS_PENDING, $profile->fresh()->status);
        $this->assertDatabaseHas('doctor_verification_documents', ['doctor_profile_id' => $profile->id, 'document_type' => 'license', 'version' => 2, 'is_current' => 1]);
        $this->assertDatabaseHas('doctor_verification_events', ['doctor_profile_id' => $profile->id, 'event_type' => 'application_resubmitted']);
    }

    public function test_profile_edits_reopen_verification_and_private_documents_cannot_be_read_by_another_parent(): void
    {
        $this->post(route('doctor.register.store'), $this->doctorPayload('private@example.test'))->assertRedirect(route('verification.notice'));
        $doctor = User::where('email', 'private@example.test')->firstOrFail();
        $doctor->markEmailAsVerified();
        $profile = $doctor->doctorProfile;
        $profile->update(['status' => DoctorProfile::STATUS_APPROVED]);
        $doctor->forceFill(['role' => User::ROLE_APPROVED_DOCTOR])->save();

        $this->actingAs($doctor)->put(route('doctor.profile.update'), $this->profilePayload($doctor, $profile, ['workplace' => 'Updated City Hospital']))->assertRedirect();
        $this->assertSame(DoctorProfile::STATUS_APPROVED, $profile->fresh()->status);
        $this->put(route('doctor.profile.update'), $this->profilePayload($doctor->fresh(), $profile->fresh(), ['specialty' => 'Neurology']))->assertRedirect();
        $this->assertSame(DoctorProfile::STATUS_PENDING, $profile->fresh()->status);
        $this->assertSame(User::ROLE_PENDING_DOCTOR, $doctor->fresh()->role);
        $this->assertSame('pending', $profile->documents()->where('document_type', 'license')->first()->status);

        $otherParent = $this->makeUser('other-parent@example.test', User::ROLE_PARENT);
        $otherParent->markEmailAsVerified();
        $document = $profile->documents()->first();
        $this->actingAs($otherParent)->get(route('doctor.documents.download', $document))->assertNotFound();
        $this->get(route('doctor.dashboard'))->assertForbidden();
    }

    public function test_admin_search_and_doctor_dashboard_show_real_application_state(): void
    {
        $this->post(route('doctor.register.store'), $this->doctorPayload('dashboard@example.test'))->assertRedirect(route('verification.notice'));
        $doctor = User::where('email', 'dashboard@example.test')->firstOrFail();
        $doctor->markEmailAsVerified();
        $this->actingAs($doctor)->get(route('doctor.dashboard'))->assertOk()->assertSee('Application pending')->assertSee('License document');
        $this->get(route('doctor.profile.edit'))->assertOk()->assertSee('Professional profile');

        $admin = $this->makeUser('filter-admin@example.test', User::ROLE_ADMIN);
        $this->actingAs($admin)->get(route('admin.doctors.index', ['search' => 'dashboard@example.test', 'specialty' => 'Pediatrics', 'city' => 'Lahore', 'status' => 'pending']))
            ->assertOk()->assertSee('dashboard@example.test')->assertSee('Review application');
        $this->get(route('admin.doctors.show', $doctor->doctorProfile))->assertOk()->assertSee('Verification documents')->assertSee('Approve doctor');
    }

    public function test_existing_parent_activity_child_progress_and_admin_pages_still_respond(): void
    {
        $this->get(route('register'))->assertOk();
        $this->get(route('activities.index'))->assertOk();
        $parent = $this->makeUser('regression-parent@example.test', User::ROLE_PARENT);
        $this->actingAs($parent)->get(route('dashboard'))->assertRedirect(route('dashboard.profile'));
        $this->get(route('dashboard.children.index'))->assertOk();
        $this->get(route('dashboard.progress'))->assertOk();
        $child = $parent->children()->create(['name' => 'Regression Child', 'date_of_birth' => '2022-01-15']);
        $activity = Activity::create([
            'title' => 'Regression activity', 'short_description' => 'Progress test',
            'age_group' => '2 to 3 years', 'development_area' => 'Communication and language',
            'status' => Activity::STATUS_PUBLISHED, 'published_at' => now(),
        ]);
        $section = $activity->sections()->create([
            'title' => 'View the content', 'content_type' => 'video', 'content' => ['url' => 'https://example.test/video'],
            'position' => 1,
        ]);
        ActivityTrackingPreference::create([
            'user_id' => $parent->id, 'child_id' => $child->id, 'activity_id' => $activity->id, 'is_tracking' => true,
        ]);
        $this->postJson(route('dashboard.progress.section.track', [$activity, $section]), [
            'child_id' => $child->id, 'method' => 'content_viewed',
        ])->assertOk()->assertJson(['ok' => true, 'status' => 'completed', 'method' => 'content_viewed']);
        $this->assertDatabaseHas('activity_progress', [
            'user_id' => $parent->id, 'child_id' => $child->id, 'activity_id' => $activity->id,
            'status' => 'completed', 'progress_percent' => 100,
        ]);
        $lesson = $activity->lessons()->create(['title' => 'Regression lesson', 'description' => 'Lesson rendering check.', 'position' => 1]);
        $lessonSection = $lesson->sections()->create([
            'activity_id' => $activity->id, 'title' => 'Lesson content', 'content_type' => 'steps',
            'content' => ['steps' => ['Read together']], 'position' => 1,
        ]);
        $this->get(route('activities.show', $activity))->assertOk()->assertSee('Regression lesson')->assertSee('Lesson content');
        $this->post(route('dashboard.progress.lesson.complete', [$activity, $lesson]), [
            'child_id' => $child->id, 'lesson_id' => $lesson->id,
        ])->assertRedirect();
        $this->assertDatabaseHas('activity_lesson_progress', [
            'user_id' => $parent->id, 'child_id' => $child->id,
            'activity_id' => $activity->id, 'activity_lesson_id' => $lesson->id, 'status' => 'completed',
        ]);

        $admin = $this->makeUser('regression-admin@example.test', User::ROLE_ADMIN);
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertSee('Doctor Management');
        $this->get(route('admin.activities.sections.index', $activity))->assertOk();
        $this->get(route('admin.activities.lessons.index', $activity))->assertOk();
        $this->get(route('admin.activities.index'))->assertOk();
        $this->get(route('admin.users.index'))->assertOk();
        $parent = User::where('email', 'regression-parent@example.test')->firstOrFail();
        $this->patch(route('admin.users.role', $parent), ['role' => User::ROLE_APPROVED_DOCTOR])->assertSessionHasErrors('role');
    }

    public function test_duplicate_email_and_unsupported_document_are_rejected_without_extra_accounts(): void
    {
        $this->post(route('doctor.register.store'), $this->doctorPayload('duplicate@example.test'))->assertRedirect(route('verification.notice'));
        $this->post(route('logout'));
        $this->post(route('doctor.register.store'), $this->doctorPayload('duplicate@example.test'))->assertSessionHasErrors('email');
        $this->post(route('logout'));

        $payload = $this->doctorPayload('unsupported@example.test');
        $payload['license_document'] = UploadedFile::fake()->create('payload.exe', 8, 'application/x-msdownload');
        $this->post(route('doctor.register.store'), $payload)->assertSessionHasErrors('license_document');
        $this->assertDatabaseMissing('users', ['email' => 'unsupported@example.test']);

        $payload = $this->doctorPayload('mismatch@example.test');
        $payload['license_document'] = UploadedFile::fake()->create('payload.pdf', 8, 'text/plain');
        $this->post(route('doctor.register.store'), $payload)->assertSessionHasErrors('license_document');
        $payload = $this->doctorPayload('oversized@example.test');
        $payload['license_document'] = UploadedFile::fake()->create('oversized.pdf', 10241, 'application/pdf');
        $this->post(route('doctor.register.store'), $payload)->assertSessionHasErrors('license_document');
        $this->assertDatabaseMissing('users', ['email' => 'mismatch@example.test']);
        $this->assertDatabaseMissing('users', ['email' => 'oversized@example.test']);
        $this->assertSame(1, User::where('email', 'duplicate@example.test')->count());
    }

    public function test_approved_doctor_can_be_suspended_and_reinstated_only_by_admin(): void
    {
        $this->post(route('doctor.register.store'), $this->doctorPayload('suspend@example.test'))->assertRedirect(route('verification.notice'));
        $doctor = User::where('email', 'suspend@example.test')->firstOrFail();
        $doctor->markEmailAsVerified();
        $admin = $this->makeUser('suspend-admin@example.test', User::ROLE_ADMIN);
        $profile = $doctor->doctorProfile;

        $this->actingAs($admin);
        foreach ($profile->documents as $document) {
            $this->patch(route('admin.doctors.documents.review', [$profile, $document]), ['status' => 'approved'])->assertRedirect();
        }
        $this->patch(route('admin.doctors.status', $profile), ['status' => 'approved'])->assertRedirect();
        $this->patch(route('admin.doctors.status', $profile), ['status' => 'suspended', 'reason' => 'License review is required.'])->assertRedirect();
        $this->assertSame(DoctorProfile::STATUS_SUSPENDED, $profile->fresh()->status);
        $this->assertSame(User::ROLE_PENDING_DOCTOR, $doctor->fresh()->role);

        $this->patch(route('admin.doctors.status', $profile), ['status' => 'approved'])->assertRedirect();
        $this->assertSame(DoctorProfile::STATUS_APPROVED, $profile->fresh()->status);
        $this->assertDatabaseHas('doctor_verification_events', ['doctor_profile_id' => $profile->id, 'event_type' => 'doctor_reinstated', 'actor_id' => $admin->id]);
    }

    private function doctorPayload(string $email): array
    {
        return [
            'first_name' => 'Amina', 'last_name' => 'Doctor', 'email' => $email,
            'password' => 'StrongPassword123', 'password_confirmation' => 'StrongPassword123',
            'phone' => '+92 300 1234567', 'professional_title' => 'Pediatrician', 'specialty' => 'Pediatrics',
            'qualification' => 'MBBS, FCPS', 'institution' => 'Example Medical University',
            'years_experience' => 8, 'workplace' => 'City Hospital', 'city' => 'Lahore',
            'biography' => 'Professional profile for review.', 'license_number' => 'LIC-'.substr(sha1($email), 0, 12),
            'issuing_authority' => 'National Medical Council', 'license_expires_at' => now()->addYear()->toDateString(),
            'license_document' => UploadedFile::fake()->create('license.pdf', 8, 'application/pdf'),
            'qualification_document' => UploadedFile::fake()->create('qualification.pdf', 8, 'application/pdf'),
        ];
    }

    private function profilePayload(User $user, DoctorProfile $profile, array $overrides = []): array
    {
        return array_merge([
            'first_name' => $user->first_name, 'last_name' => $user->last_name, 'phone' => $profile->phone,
            'professional_title' => $profile->professional_title, 'specialty' => $profile->specialty,
            'qualification' => $profile->qualification, 'institution' => $profile->institution,
            'years_experience' => $profile->years_experience, 'workplace' => $profile->workplace,
            'city' => $profile->city, 'biography' => $profile->biography,
            'license_number' => $profile->license_number, 'issuing_authority' => $profile->issuing_authority,
            'license_expires_at' => $profile->license_expires_at?->toDateString(),
        ], $overrides);
    }

    private function makeUser(string $email, string $role): User
    {
        $user = User::create([
            'first_name' => 'Test', 'last_name' => 'Account', 'name' => 'Test Account',
            'email' => $email, 'password' => bcrypt('StrongPassword123'),
        ]);
        $user->forceFill(['role' => $role])->save();
        $user->markEmailAsVerified();

        return $user;
    }
}
