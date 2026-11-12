<?php

use App\Models\Activity;
use App\Models\ActivityEnrollment;
use App\Models\Appointment;
use App\Models\ContactMessage;
use App\Models\DoctorAppointmentSlot;
use App\Models\DoctorProfile;
use App\Models\PaymentTransaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$password = 'SmokePass123!';
$emails = ['smoke.admin@example.test', 'smoke.parent@example.test', 'smoke.doctor@example.test', 'smoke.pending@example.test'];

Appointment::whereHas('patient', fn ($q) => $q->whereIn('email', $emails))->delete();
foreach ($emails as $email) {
    $user = User::where('email', $email)->first();
    if ($user) {
        $user->doctorProfile?->appointmentSlots()->delete();
        $user->doctorProfile?->consultationOptions()->delete();
        $user->doctorProfile?->availabilityPeriods()->delete();
        $user->doctorProfile?->documents()->delete();
        $user->doctorProfile?->delete();
        $user->parentProfile?->delete();
        $user->children()->delete();
        $user->delete();
    }
}
Activity::where('title', 'Smoke Learning Activity')->delete();
ContactMessage::where('email', 'smoke.parent@example.test')->delete();

$mkUser = function (string $role, string $email, string $first, string $last) use ($password): User {
    $user = User::create([
        'first_name' => $first,
        'last_name' => $last,
        'name' => $first.' '.$last,
        'email' => $email,
        'password' => Hash::make($password),
    ]);
    $user->forceFill(['role' => $role, 'email_verified_at' => now()])->save();

    return $user->fresh();
};

$admin = $mkUser(User::ROLE_ADMIN, 'smoke.admin@example.test', 'Smoke', 'Admin');

$parent = $mkUser(User::ROLE_PARENT, 'smoke.parent@example.test', 'Smoke', 'Parent');
$parent->parentProfile()->create([
    'date_of_birth' => '1990-05-10',
    'contact' => '+923001112233',
    'gender' => 'female',
]);
$child = $parent->children()->create([
    'name' => 'Smoke Child',
    'date_of_birth' => '2020-03-15',
    'pronouns' => 'they/them',
    'communication_preferences' => 'Short sentences',
    'interests' => 'Music and blocks',
    'strengths' => 'Kind and curious',
    'support_preferences' => 'Visual prompts',
    'learning_goals' => 'Daily routines',
]);

$doctor = $mkUser(User::ROLE_APPROVED_DOCTOR, 'smoke.doctor@example.test', 'Smoke', 'Doctor');
$profile = $doctor->doctorProfile()->create([
    'phone' => '+923001234567',
    'professional_title' => 'Dr.',
    'specialty' => 'Pediatrics',
    'qualification' => 'MBBS, FCPS',
    'institution' => 'Smoke Medical University',
    'years_experience' => 8,
    'workplace' => 'Smoke Clinic',
    'city' => 'Lahore',
    'biography' => 'Synthetic profile created for local smoke testing.',
    'license_number' => 'SMOKE-'.Str::upper(Str::random(10)),
    'issuing_authority' => 'Smoke Council',
    'license_expires_at' => now()->addYears(3),
    'status' => DoctorProfile::STATUS_APPROVED,
    'submitted_at' => now(),
    'reviewed_at' => now(),
    'reviewed_by' => $admin->id,
    'is_public' => true,
    'timezone' => 'Asia/Karachi',
    'languages' => ['English', 'Urdu'],
    'availability_published_at' => now(),
]);
$profile->documents()->createMany([
    [
        'document_type' => 'license', 'original_name' => 'license.pdf', 'storage_path' => 'doctor-verification/smoke/license.pdf',
        'mime_type' => 'application/pdf', 'size_bytes' => 1024, 'version' => 1, 'status' => 'approved', 'is_current' => true,
        'reviewed_by' => $admin->id, 'reviewed_at' => now(),
    ],
    [
        'document_type' => 'qualification', 'original_name' => 'qualification.pdf', 'storage_path' => 'doctor-verification/smoke/qualification.pdf',
        'mime_type' => 'application/pdf', 'size_bytes' => 1024, 'version' => 1, 'status' => 'approved', 'is_current' => true,
        'reviewed_by' => $admin->id, 'reviewed_at' => now(),
    ],
]);
$option = $profile->consultationOptions()->create(['type' => 'online', 'duration_minutes' => 30, 'fee' => 2500, 'is_active' => true]);
$start = CarbonImmutable::now('Asia/Karachi')->addDays(3)->setTime(11, 0);
$slot = DoctorAppointmentSlot::create([
    'doctor_profile_id' => $profile->id,
    'consultation_option_id' => $option->id,
    'starts_at' => $start->utc(),
    'ends_at' => $start->addMinutes(30)->utc(),
    'timezone' => 'Asia/Karachi',
    'meeting_url' => 'https://meet.example.test/smoke-slot',
    'status' => DoctorAppointmentSlot::OPEN,
]);

$pending = $mkUser(User::ROLE_PENDING_DOCTOR, 'smoke.pending@example.test', 'Smoke', 'Pending');
$pendingProfile = $pending->doctorProfile()->create([
    'phone' => '+923001234568',
    'professional_title' => 'Dr.',
    'specialty' => 'Psychology',
    'qualification' => 'MSc',
    'institution' => 'Smoke University',
    'years_experience' => 3,
    'city' => 'Karachi',
    'license_number' => 'SMOKE-P-'.Str::upper(Str::random(8)),
    'issuing_authority' => 'Smoke Council',
    'status' => DoctorProfile::STATUS_PENDING,
    'submitted_at' => now(),
    'timezone' => 'Asia/Karachi',
]);
$pendingProfile->documents()->create([
    'document_type' => 'license', 'original_name' => 'pending-license.pdf', 'storage_path' => 'doctor-verification/smoke/pending-license.pdf',
    'mime_type' => 'application/pdf', 'size_bytes' => 1024, 'version' => 1, 'status' => 'pending', 'is_current' => true,
]);

$activity = Activity::create([
    'title' => 'Smoke Learning Activity',
    'short_description' => 'Synthetic activity used for smoke testing.',
    'detailed_description' => 'A simple activity used to exercise public and dashboard pages.',
    'age_group' => '3 to 5 years',
    'development_area' => 'Communication and language',
    'difficulty' => 'beginner',
    'estimated_minutes' => 15,
    'materials' => 'Paper, crayons',
    'learning_objective' => 'Practice naming colours.',
    'instructions' => 'Show each colour and say its name together.',
    'parent_tips' => 'Pause and let the child respond.',
    'status' => Activity::STATUS_PUBLISHED,
    'published_at' => now(),
]);
$lesson = $activity->lessons()->create(['title' => 'Smoke Lesson', 'description' => 'Lesson used for smoke testing.', 'position' => 1]);
$section = $activity->sections()->create([
    'lesson_id' => $lesson->id,
    'title' => 'Smoke Section',
    'content_type' => 'text',
    'content' => ['body' => 'Look at the colours together.'],
    'position' => 1,
]);
ActivityEnrollment::create([
    'user_id' => $parent->id,
    'child_id' => $child->id,
    'activity_id' => $activity->id,
    'status' => 'in_progress',
    'progress_percent' => 10,
    'enrolled_at' => now(),
    'started_at' => now(),
    'last_lesson_id' => $lesson->id,
    'last_section_id' => $section->id,
]);

$appointment = Appointment::create([
    'patient_user_id' => $parent->id,
    'doctor_profile_id' => $profile->id,
    'consultation_option_id' => $option->id,
    'doctor_appointment_slot_id' => $slot->id,
    'consultation_type' => 'online',
    'duration_minutes' => 30,
    'starts_at' => $start->utc(),
    'ends_at' => $start->addMinutes(30)->utc(),
    'timezone' => 'Asia/Karachi',
    'meeting_link' => 'https://meet.example.test/smoke-slot',
    'amount_minor' => 250000,
    'currency' => 'PKR',
    'status' => Appointment::CONFIRMED,
    'idempotency_key' => (string) Str::uuid(),
]);
$slot->update(['status' => DoctorAppointmentSlot::BOOKED]);

$payment = $appointment->payments()->create([
    'payer_user_id' => $parent->id,
    'provider' => 'safepay',
    'status' => PaymentTransaction::PAID,
    'amount_minor' => 250000,
    'currency' => 'PKR',
    'idempotency_key' => (string) Str::uuid(),
    'provider_reference' => 'SMOKE-PAY-'.Str::upper(Str::random(8)),
    'paid_at' => now(),
]);

ContactMessage::create([
    'name' => 'Smoke Parent',
    'email' => 'smoke.parent@example.test',
    'subject' => 'Smoke contact message',
    'message' => 'This message was created by the local smoke test.',
]);

echo json_encode([
    'admin' => $admin->email,
    'parent' => $parent->email,
    'doctor' => $doctor->email,
    'pending_doctor' => $pending->email,
    'password' => $password,
    'activity_id' => $activity->id,
    'lesson_id' => $lesson->id,
    'section_id' => $section->id,
    'child_id' => $child->id,
    'doctor_profile_id' => $profile->id,
    'slot_id' => $slot->id,
    'appointment_id' => $appointment->id,
    'payment_id' => $payment->id,
    'contact_message_id' => ContactMessage::where('email', 'smoke.parent@example.test')->latest('id')->value('id'),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
