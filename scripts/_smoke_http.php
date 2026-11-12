<?php

use App\Models\Activity;
use App\Models\ActivitySection;
use App\Models\ActivityLesson;
use App\Models\Appointment;
use App\Models\Child;
use App\Models\ContactMessage;
use App\Models\DoctorProfile;
use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Support\Str;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$base = getenv('SMOKE_BASE') ?: 'http://127.0.0.1:8010';
$password = 'SmokePass123!';

$parent = User::where('email', 'smoke.parent@example.test')->firstOrFail();
$doctor = User::where('email', 'smoke.doctor@example.test')->firstOrFail();
$pending = User::where('email', 'smoke.pending@example.test')->firstOrFail();
$admin = User::where('email', 'smoke.admin@example.test')->firstOrFail();
$profile = DoctorProfile::where('user_id', $doctor->id)->firstOrFail();
$activity = Activity::where('title', 'Smoke Learning Activity')->firstOrFail();
$lesson = ActivityLesson::where('activity_id', $activity->id)->firstOrFail();
$section = ActivitySection::where('activity_id', $activity->id)->firstOrFail();
$appointment = Appointment::where('patient_user_id', $parent->id)->firstOrFail();
$payment = PaymentTransaction::where('payer_user_id', $parent->id)->firstOrFail();
$child = Child::where('user_id', $parent->id)->firstOrFail();
$contact = ContactMessage::where('email', 'smoke.parent@example.test')->latest('id')->firstOrFail();
$slotDate = $appointment->localStartsAt()->toDateString();

$results = [];
$failures = [];

function request(string $method, string $path, ?array $data = null, bool $json = false): array
{
    global $base, $cookieFile;
    $url = str_starts_with($path, 'http') ? $path : $base.$path;
    $ch = curl_init($url);
    $headers = ['Accept: text/html,application/json'];
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => $headers,
    ]);
    if ($data !== null) {
        if ($json) {
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'X-Requested-With: XMLHttpRequest';
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
    }
    $raw = curl_exec($ch);
    if ($raw === false) {
        $err = curl_error($ch);
        curl_close($ch);

        return ['status' => 0, 'body' => '', 'location' => null, 'error' => $err, 'url' => $url];
    }
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $header = substr($raw, 0, $headerSize);
    $body = substr($raw, $headerSize);
    $location = null;
    if (preg_match('/^Location:\s*(.+)$/mi', $header, $m)) {
        $location = trim($m[1]);
    }
    curl_close($ch);

    return ['status' => $status, 'body' => $body, 'location' => $location, 'error' => null, 'url' => $url];
}

function token(): string
{
    $r = request('GET', '/login');
    if ($r['status'] === 302 && $r['location']) {
        $r = request('GET', $r['location']);
    }
    foreach ([
        '/name="_token"\s+value="([^"]+)"/',
        '/name="csrf-token"\s+content="([^"]+)"/',
    ] as $pattern) {
        if (preg_match($pattern, $r['body'], $m)) {
            return $m[1];
        }
    }
    throw new RuntimeException('Could not read CSRF token from the current session (status '.$r['status'].').');
}

function record(string $label, array $r, array $allowed = [200], bool $expectNoError = true): void
{
    global $results, $failures;
    $ok = in_array($r['status'], $allowed, true);
    if ($expectNoError && $r['status'] >= 500) {
        $ok = false;
    }
    $bodyHit = '';
    foreach (['Server Error', 'Whoops, looks like', 'SQLSTATE[', 'Internal Server Error'] as $needle) {
        if (str_contains($r['body'], $needle)) {
            $bodyHit = $needle;
            $ok = false;
            break;
        }
    }
    $results[] = [$ok ? 'PASS' : 'FAIL', $label, $r['status'], $r['location'] ?? ''];
    if (! $ok) {
        $failures[] = [$label, $r['status'], $r['location'] ?? '', $bodyHit, substr(strip_tags($r['body']), 0, 300)];
    }
}

function crawl(string $label, array $paths, array $allowed = [200]): void
{
    foreach ($paths as $path) {
        $r = request('GET', $path);
        record($label.' GET '.$path, $r, $allowed);
    }
}

function post(string $label, string $method, string $path, array $data, array $allowed = [200, 302]): void
{
    $data['_token'] = token();
    $r = request($method, $path, $data);
    record($label.' '.$method.' '.$path, $r, $allowed);
}

function loginAs(string $email, string $expectPath): void
{
    global $cookieFile, $password;
    $cookieFile = tempnam(sys_get_temp_dir(), 'smoke-cookies-');
    $token = token();
    $r = request('POST', '/login', ['_token' => $token, 'email' => $email, 'password' => $password]);
    record('LOGIN '.$email, $r, [302]);
    if ($r['status'] === 302 && $r['location']) {
        $follow = request('GET', $r['location']);
        record('LOGIN destination '.$email, $follow, [200]);
        if (! str_contains($follow['url'], $expectPath)) {
            $GLOBALS['failures'][] = ['LOGIN destination mismatch '.$email, 0, $follow['url'].' expected '.$expectPath, '', ''];
            $GLOBALS['results'][] = ['FAIL', 'LOGIN destination matches '.$expectPath, 0, $follow['url']];
        }
    }
}

// Public pages
$GLOBALS['cookieFile'] = tempnam(sys_get_temp_dir(), 'smoke-cookies-');
crawl('PUBLIC', ['/', '/login', '/register', '/register/doctor', '/forgot-password', '/contact', '/activities', '/doctors', '/api/health', '/api/session', '/doctors/'.$profile->id, '/activities/'.$activity->id, '/activities/'.$activity->id.'/slideshow', '/this-page-does-not-exist'], [200, 302, 404]);
post('PUBLIC contact submit', 'POST', '/contact', ['name' => 'Smoke Tester', 'email' => 'smoke.contact@example.test', 'subject' => 'Smoke subject', 'message' => 'Smoke test message body.'], [302]);
post('PUBLIC invalid login', 'POST', '/login', ['email' => 'nobody@example.test', 'password' => 'wrong-password'], [302]);

// Parent
loginAs('smoke.parent@example.test', '/activities');
crawl('PARENT', [
    '/dashboard', '/dashboard/profile', '/dashboard/children', '/dashboard/children/create',
    '/dashboard/children/'.$child->id.'/edit', '/dashboard/progress', '/dashboard/settings', '/password/change',
    '/notifications', '/appointments', '/appointment-history', '/doctors-consulted',
    '/appointments/'.$appointment->id, '/appointments/'.$appointment->id.'/slots?date='.$slotDate,
    '/payment-history', '/payments/'.$payment->id.'/receipt',
    '/activities', '/activities/'.$activity->id, '/activities/'.$activity->id.'/slideshow',
    '/doctors/'.$profile->id, '/doctors/'.$profile->id.'/book',
    '/doctors/'.$profile->id.'/availability/slots?date='.$slotDate.'&consultation_option_id='.$appointment->consultation_option_id,
]);
post('PARENT add child', 'POST', '/dashboard/children', ['name' => 'Smoke Child Two', 'date_of_birth' => '2019-04-02'], [302]);
post('PARENT update profile', 'PUT', '/dashboard/profile', ['first_name' => 'Smoke', 'last_name' => 'Parent', 'contact' => '+923001112233', 'gender' => 'female'], [302]);
post('PARENT enroll activity', 'POST', '/activities/'.$activity->id.'/enroll', ['child_id' => $child->id], [302]);
post('PARENT notification read-all', 'PATCH', '/notifications/read-all', [], [302]);
post('PARENT section complete', 'POST', '/dashboard/progress/'.$activity->id.'/sections/'.$section->id, ['child_id' => $child->id, 'status' => 'completed'], [302]);
post('PARENT lesson complete', 'POST', '/dashboard/progress/'.$activity->id.'/lessons/'.$lesson->id.'/complete', ['child_id' => $child->id, 'lesson_id' => $lesson->id], [302]);
post('PARENT logout', 'POST', '/logout', [], [302]);

// Doctor
loginAs('smoke.doctor@example.test', '/doctor/appointments');
crawl('DOCTOR', [
    '/doctor', '/doctor/profile', '/doctor/profile/preview', '/doctor/availability',
    '/doctor/appointments', '/doctor/appointments/history', '/doctor/appointments/'.$appointment->id,
    '/doctor/appointments/'.$appointment->id.'/prescription/edit',
    '/doctor/revenue', '/doctor/revenue.csv', '/doctor/notifications',
], [200, 409]);
post('DOCTOR profile update', 'PUT', '/doctor/profile', [
    'first_name' => 'Smoke', 'last_name' => 'Doctor', 'phone' => '+923001234567', 'professional_title' => 'Dr.',
    'specialty' => 'Pediatrics', 'qualification' => 'MBBS, FCPS', 'institution' => 'Smoke Medical University',
    'years_experience' => 8, 'workplace' => 'Smoke Clinic', 'city' => 'Lahore', 'biography' => 'Synthetic profile.',
    'license_number' => $profile->license_number, 'issuing_authority' => 'Smoke Council',
    'license_expires_at' => $profile->license_expires_at?->toDateString(), 'timezone' => 'Asia/Karachi',
    'languages_text' => 'English, Urdu',
], [302]);
post('DOCTOR notifications read-all', 'PATCH', '/doctor/notifications/read-all', [], [302]);
post('DOCTOR logout', 'POST', '/logout', [], [302]);

// Pending doctor
loginAs('smoke.pending@example.test', '/doctor');
crawl('PENDING DOCTOR', ['/doctor', '/doctor/profile', '/doctor/availability', '/doctor/profile/preview', '/doctor/notifications'], [200]);
post('PENDING DOCTOR logout', 'POST', '/logout', [], [302]);

// Admin
loginAs('smoke.admin@example.test', '/admin');
crawl('ADMIN', [
    '/admin', '/admin/notifications/send', '/admin/appointments', '/admin/appointments/'.$appointment->id,
    '/admin/refunds', '/admin/notifications', '/admin/doctors', '/admin/doctors/'.$profile->id,
    '/admin/doctor-specialties', '/admin/activities', '/admin/activities/create',
    '/admin/activities/'.$activity->id.'/edit', '/admin/activities/'.$activity->id.'/preview',
    '/admin/activities/'.$activity->id.'/lessons', '/admin/activities/'.$activity->id.'/lessons/create',
    '/admin/activities/'.$activity->id.'/lessons/'.$lesson->id.'/edit',
    '/admin/activities/'.$activity->id.'/lessons/'.$lesson->id.'/sections',
    '/admin/activities/'.$activity->id.'/lessons/'.$lesson->id.'/sections/create',
    '/admin/activities/'.$activity->id.'/sections', '/admin/activities/'.$activity->id.'/sections/create',
    '/admin/users', '/admin/users/'.$parent->id, '/admin/messages', '/admin/messages/'.$contact->id,
]);
post('ADMIN send notification', 'POST', '/admin/notifications/send', [
    'send_key' => (string) Str::uuid(), 'audience' => 'patients', 'title' => 'Smoke notification',
    'message' => 'Synthetic notification from the smoke test.', 'internal_url' => '/appointments',
], [302]);
post('ADMIN toggle message read', 'PATCH', '/admin/messages/'.$contact->id.'/read', [], [302]);
post('ADMIN add specialty', 'POST', '/admin/doctor-specialties', ['name' => 'Smoke Specialty '.Str::upper(Str::random(5))], [302]);
post('ADMIN notification read-all', 'PATCH', '/admin/notifications/read-all', [], [302]);
post('ADMIN logout', 'POST', '/logout', [], [302]);

echo "HTTP smoke results\n";
echo str_repeat('=', 100)."\n";
foreach ($results as [$state, $label, $status, $location]) {
    printf("%-4s %-4s %-75s %s\n", $state, $status, $label, $location ? '-> '.$location : '');
}
echo str_repeat('=', 100)."\n";
echo count($results).' checks, '.count($failures)." failure(s)\n";
foreach ($failures as $failure) {
    echo "\nFAILED: {$failure[0]}\n  status: {$failure[1]}\n  location: {$failure[2]}\n  marker: {$failure[3]}\n  body: {$failure[4]}\n";
}

exit($failures ? 1 : 0);
