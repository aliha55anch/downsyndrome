<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Activity;
use App\Models\ActivityEnrollment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class ModuleTenDashboardAndNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $role, string $email): User
    {
        $user = User::create([
            'first_name' => 'Test', 'last_name' => 'User', 'name' => 'Test User',
            'email' => $email, 'password' => Hash::make('password-example'),
        ]);
        $user->forceFill(['role' => $role, 'email_verified_at' => now()])->save();
        return $user;
    }

    public function test_patient_dashboard_has_compact_navigation_and_notification_bell(): void
    {
        $patient = $this->account(User::ROLE_PARENT, 'patient-dashboard@example.test');
        $this->actingAs($patient)->withSession(['profile_deferred' => true])->get(route('dashboard'))
            ->assertOk()->assertSee('Upcoming appointments')->assertSee('Activity Progress')
            ->assertSee('notification-bell')->assertSee('Appointment history')->assertSee('Back to main website')->assertSee(url('/'))
            ->assertDontSee('Professional support')->assertDontSee('My Activities');
    }

    public function test_public_session_endpoint_returns_only_the_current_patients_notification_preview(): void
    {
        $patient = $this->account(User::ROLE_PARENT, 'patient-session-bell@example.test');
        $patient->notifications()->create([
            'id' => (string) Str::uuid(), 'type' => 'admin_broadcast',
            'data' => ['title' => 'Appointment update', 'message' => 'Please check your booking.', 'url' => '/appointments'],
        ]);

        $this->actingAs($patient)->getJson('/api/session')->assertOk()
            ->assertJsonPath('unread_notifications_count', 1)
            ->assertJsonPath('recent_notifications.0.title', 'Appointment update')
            ->assertJsonPath('recent_notifications.0.url', '/appointments');
    }

    public function test_approved_doctor_login_opens_appointments(): void
    {
        $this->account(User::ROLE_APPROVED_DOCTOR, 'doctor-login@example.test');
        $this->post(route('login'), ['email' => 'doctor-login@example.test', 'password' => 'password-example'])
            ->assertRedirect(route('doctor.appointments.index'));
    }

    public function test_activity_progress_shows_only_activities_the_child_is_enrolled_in(): void
    {
        $patient = $this->account(User::ROLE_PARENT, 'patient-progress@example.test');
        $child = $patient->children()->create(['name' => 'Alex', 'date_of_birth' => '2021-01-01']);
        $enrolled = Activity::create(['title' => 'Enrolled learning', 'short_description' => 'Joined activity', 'age_group' => '3 to 5 years', 'development_area' => 'Communication and language', 'status' => 'published', 'published_at' => now()]);
        Activity::create(['title' => 'Not joined learning', 'short_description' => 'Not joined', 'age_group' => '3 to 5 years', 'development_area' => 'Motor skills', 'status' => 'published', 'published_at' => now()]);
        ActivityEnrollment::create(['user_id' => $patient->id, 'child_id' => $child->id, 'activity_id' => $enrolled->id, 'status' => 'in_progress']);

        $this->actingAs($patient)->get(route('dashboard.progress'))
            ->assertOk()->assertSee('Enrolled learning')->assertDontSee('Not joined learning')
            ->assertDontSee('Choose activities to track')->assertDontSee('Track this activity');
    }

    public function test_admin_sends_one_audited_in_app_notification_to_the_selected_audience(): void
    {
        $admin = $this->account(User::ROLE_ADMIN, 'admin-notifications@example.test');
        $patient = $this->account(User::ROLE_PARENT, 'patient-notifications@example.test');
        $doctor = $this->account(User::ROLE_APPROVED_DOCTOR, 'approved-notifications@example.test');
        $pendingDoctor = $this->account(User::ROLE_PENDING_DOCTOR, 'pending-notifications@example.test');

        $this->actingAs($admin)->get(route('admin.notifications.create'))->assertOk()->assertSee('Send a notification');
        $send = [
            'send_key' => (string) Str::uuid(),
            'audience' => 'patients', 'title' => 'Clinic update', 'message' => 'The clinic schedule changed.',
            'internal_url' => '/appointments',
        ];
        $this->post(route('admin.notifications.store'), $send)->assertRedirect(route('admin.notifications.create'));
        $this->post(route('admin.notifications.store'), $send)->assertRedirect(route('admin.notifications.create'))->assertSessionHas('status', 'This notification form was already submitted. No duplicate notifications were sent.');

        $this->assertCount(1, $patient->fresh()->notifications);
        $this->assertCount(0, $doctor->fresh()->notifications);
        $this->assertCount(0, $pendingDoctor->fresh()->notifications);
        $this->assertDatabaseHas('admin_notification_batches', ['admin_user_id' => $admin->id, 'audience' => 'patients', 'recipient_count' => 1]);

        $this->actingAs($patient)->withSession(['profile_deferred' => true])->get(route('dashboard'))->assertOk()->assertSee('Clinic update')->assertSee('The clinic schedule changed.');
        $notification = $patient->fresh()->notifications()->firstOrFail();
        $this->actingAs($patient)->patch(route('notifications.read-all'))->assertRedirect();
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_admin_notification_rejects_external_destination_and_non_admin_senders(): void
    {
        $admin = $this->account(User::ROLE_ADMIN, 'admin-invalid-notifications@example.test');
        $patient = $this->account(User::ROLE_PARENT, 'patient-invalid-notifications@example.test');
        $this->actingAs($admin)->from(route('admin.notifications.create'))->post(route('admin.notifications.store'), [
            'send_key' => (string) Str::uuid(), 'audience' => 'patients', 'title' => 'Reminder', 'message' => 'Please review your appointment.',
            'internal_url' => 'https://evil.example/redirect',
        ])->assertSessionHasErrors('internal_url');
        $this->actingAs($patient)->post(route('admin.notifications.store'), [
            'send_key' => (string) Str::uuid(), 'audience' => 'patients', 'title' => 'Reminder', 'message' => 'Please review your appointment.',
        ])->assertForbidden();
    }
}
