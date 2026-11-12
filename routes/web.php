<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\DoctorRegistrationController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ActivityController as AdminActivityController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\DoctorManagementController;
use App\Http\Controllers\Admin\ActivitySectionController;
use App\Http\Controllers\Admin\ActivityLessonController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ActivityEnrollmentController;
use App\Http\Controllers\ActivityResourceController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ParentDashboardController;
use App\Http\Controllers\ParentProfileController;
use App\Http\Controllers\ActivityProgressController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\Doctor\DashboardController as DoctorDashboardController;
use App\Http\Controllers\Doctor\ProfileController as DoctorProfileController;
use App\Http\Controllers\Doctor\DocumentController as DoctorDocumentController;
use App\Http\Controllers\Doctor\NotificationController as DoctorNotificationController;
use App\Http\Controllers\ChildController;
use App\Http\Controllers\PublicDoctorController;
use App\Http\Controllers\Doctor\AvailabilityController as DoctorAvailabilityController;
use App\Http\Controllers\Admin\DoctorProfileVisibilityController;
use App\Http\Controllers\AppointmentBookingController;
use App\Http\Controllers\ParentAppointmentController;
use App\Http\Controllers\SafepayWebhookController;
use App\Http\Controllers\Doctor\AppointmentController as DoctorAppointmentController;
use App\Http\Controllers\Admin\AppointmentManagementController;
use App\Http\Controllers\Admin\RefundManagementController;
use App\Http\Controllers\Admin\NotificationSendController;
use App\Http\Controllers\AppointmentPrescriptionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->file(public_path('index.html'));
});

Route::get('/api/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => config('app.name'),
    ]);
});

Route::get('/api/session', function () {
    if (! auth()->check()) {
        return response()->json(['authenticated' => false])->header('Cache-Control', 'no-store');
    }

    $user = auth()->user();
    $isParent = $user->hasRole(\App\Models\User::ROLE_PARENT);
    $hasPicture = $isParent && $user->parentProfile?->profile_picture_path;
    $recentNotifications = $isParent
        ? $user->notifications()->latest()->limit(5)->get()->map(fn ($item) => [
            'id' => $item->id,
            'title' => $item->data['title'] ?? 'Update',
            'message' => $item->data['message'] ?? '',
            'url' => is_string($item->data['url'] ?? null) && str_starts_with($item->data['url'], '/') && ! str_starts_with($item->data['url'], '//') ? $item->data['url'] : null,
            'created_at' => $item->created_at?->toIso8601String(),
            'read_at' => $item->read_at?->toIso8601String(),
        ])->values()
        : [];
    $dashboardUrl = match ($user->role) {
        \App\Models\User::ROLE_PARENT => route('dashboard'),
        \App\Models\User::ROLE_PENDING_DOCTOR, \App\Models\User::ROLE_APPROVED_DOCTOR => route('doctor.dashboard'),
        \App\Models\User::ROLE_ADMIN => route('admin.dashboard'),
        default => route('login'),
    };

    return response()->json([
        'authenticated' => true,
        'name' => $user->name,
        'role' => $user->role,
        'dashboard_url' => $dashboardUrl,
        'avatar_url' => $hasPicture ? route('dashboard.profile.picture') : null,
        'csrf_token' => csrf_token(),
        'unread_notifications_count' => $isParent ? $user->unreadNotifications()->count() : 0,
        'recent_notifications' => $recentNotifications,
    ])->header('Cache-Control', 'no-store');
});

Route::redirect('/login.html', '/login');
Route::redirect('/register.html', '/register');
Route::redirect('/contact.html', '/contact');
Route::redirect('/activities.html', '/activities');
Route::redirect('/activity_detail.html', '/activities');
Route::redirect('/admin_activities.html', '/admin/activities');
Route::redirect('/doctors.html', '/doctors');
Route::get('/doctors', [PublicDoctorController::class, 'index'])->name('doctors.index');
Route::get('/doctors/{doctorProfile}', [PublicDoctorController::class, 'show'])->name('doctors.show');
Route::get('/doctors/{doctorProfile}/photo', [PublicDoctorController::class, 'photo'])->name('doctors.photo');
Route::get('/doctors/{doctorProfile}/availability/slots', [AppointmentBookingController::class, 'slots'])->name('doctors.slots');
Route::get('/appointments', [ParentAppointmentController::class, 'index'])->name('appointments.index');
Route::get('/my-appointments', fn () => redirect()->route('appointments.index'))
    ->middleware(['auth', 'verified', 'role:parent'])->name('appointments.login');
Route::get('/doctors/{doctorProfile}/book', [AppointmentBookingController::class, 'create'])
    ->middleware(['auth', 'verified'])->name('appointments.create');
Route::post('/webhooks/safepay', SafepayWebhookController::class)->middleware('throttle:120,1')->name('webhooks.safepay');

Route::get('/contact', [ContactController::class, 'create'])->name('contact.create');
Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:5,10')
    ->name('contact.store');

Route::get('/activities', [ActivityController::class, 'index'])->name('activities.index');
Route::get('/activities/{activity}/slideshow', [ActivityController::class, 'slideshow'])->name('activities.slideshow');
Route::get('/activities/{activity}', [ActivityController::class, 'show'])->name('activities.show');
Route::get('/activities/{activity}/image', [ActivityController::class, 'image'])->name('activities.image');
Route::get('/activities/{activity}/icon', [ActivityController::class, 'icon'])->name('activities.icon');
Route::get('/activities/{activity}/sections/{section}/icon', [ActivityController::class, 'sectionIcon'])->name('activities.section-icon');
Route::get('/activities/{activity}/sections/{section}/media', [ActivityController::class, 'sectionMedia'])->name('activities.section-media');
Route::get('/activities/{activity}/download/{type}', [ActivityResourceController::class, 'download'])
    ->middleware(['auth', 'verified', 'role:parent,admin'])
    ->name('activities.download');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:10,1');

    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/register/doctor', [DoctorRegistrationController::class, 'create'])->name('doctor.register');
    Route::post('/register/doctor', [DoctorRegistrationController::class, 'store'])->middleware('throttle:5,1')->name('doctor.register.store');

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->middleware('throttle:5,1')->name('password.email');

    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->middleware('throttle:5,1')->name('password.store');
});

Route::middleware(['auth', 'verified', 'role:pending_doctor,approved_doctor'])->prefix('doctor')->name('doctor.')->group(function (): void {
    Route::get('/', DoctorDashboardController::class)->name('dashboard');
    Route::get('/profile', [DoctorProfileController::class, 'edit'])->name('profile.edit');
    Route::get('/profile/preview', [DoctorProfileController::class, 'preview'])->name('profile.preview');
    Route::put('/profile', [DoctorProfileController::class, 'update'])->name('profile.update');
    Route::get('/profile/photo', [DoctorProfileController::class, 'photo'])->name('profile.photo');
    Route::get('/availability', [DoctorAvailabilityController::class, 'edit'])->name('availability.edit');
    Route::middleware('doctor.approved')->group(function (): void {
        Route::get('/appointments', [DoctorAppointmentController::class, 'index'])->name('appointments.index');
        Route::get('/appointments/history', [DoctorAppointmentController::class, 'history'])->name('appointments.history');
        Route::get('/appointments/{appointment}', [DoctorAppointmentController::class, 'show'])->name('appointments.show');
        Route::patch('/appointments/{appointment}', [DoctorAppointmentController::class, 'update'])->name('appointments.update');
        Route::post('/appointments/{appointment}/cancel', [DoctorAppointmentController::class, 'cancel'])->name('appointments.cancel');
        Route::patch('/appointments/{appointment}/cancellation-request', [DoctorAppointmentController::class, 'reviewCancellation'])->name('appointments.cancellation-review');
        Route::post('/appointments/{appointment}/reschedule', [DoctorAppointmentController::class, 'reschedule'])->name('appointments.reschedule');
        Route::put('/appointments/{appointment}/meeting-link', [DoctorAppointmentController::class, 'updateMeetingLink'])->name('appointments.meeting-link');
        Route::get('/appointments/{appointment}/prescription/edit', [AppointmentPrescriptionController::class, 'edit'])->name('appointments.prescription.edit');
        Route::post('/appointments/{appointment}/prescription/draft', [AppointmentPrescriptionController::class, 'save'])->name('appointments.prescription.save');
        Route::post('/appointments/{appointment}/prescription/publish', [AppointmentPrescriptionController::class, 'publish'])->name('appointments.prescription.publish');
        Route::get('/prescriptions/{appointment}', [AppointmentPrescriptionController::class, 'show'])->name('prescriptions.show');
        Route::get('/prescriptions/{appointment}/download', [AppointmentPrescriptionController::class, 'download'])->name('prescriptions.download');
        Route::get('/revenue', [DoctorAppointmentController::class, 'revenue'])->name('revenue');
        Route::get('/revenue.csv', [DoctorAppointmentController::class, 'revenueCsv'])->name('revenue.csv');
    });
    Route::put('/availability/consultations', [DoctorAvailabilityController::class, 'saveConsultations'])->name('availability.consultations');
    Route::put('/availability/weekly', [DoctorAvailabilityController::class, 'saveWeekly'])->name('availability.weekly');
    Route::post('/availability/publish', [DoctorAvailabilityController::class, 'publishWeekly'])->name('availability.publish');
    Route::post('/availability/slots', [DoctorAvailabilityController::class, 'storeAppointmentSlot'])->name('availability.slots.store');
    Route::delete('/availability/slots/{slot}', [DoctorAvailabilityController::class, 'closeAppointmentSlot'])->name('availability.slots.close');
    Route::post('/availability/unavailable', [DoctorAvailabilityController::class, 'storeUnavailable'])->name('availability.unavailable.store');
    Route::delete('/availability/unavailable/{period}', [DoctorAvailabilityController::class, 'destroyUnavailable'])->name('availability.unavailable.destroy');
    Route::post('/documents', [DoctorDocumentController::class, 'store'])->name('documents.store');
    Route::post('/application/resubmit', [DoctorDocumentController::class, 'resubmit'])->name('application.resubmit');
    Route::patch('/notifications/{notification}/read', [DoctorNotificationController::class, 'read'])->name('notifications.read');
    Route::patch('/notifications/read-all', [App\Http\Controllers\AppointmentNotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::get('/notifications', [DoctorNotificationController::class, 'index'])->name('notifications.index');
});
Route::get('/doctor-documents/{document}', [DoctorDocumentController::class, 'download'])
    ->middleware(['auth', 'verified'])->name('doctor.documents.download');

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::middleware('verified')->group(function (): void {
        Route::get('/password/change', [PasswordController::class, 'edit'])->name('password.change');
        Route::put('/password/change', [PasswordController::class, 'update'])->name('password.update');
    });
});

Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

Route::get('/settings/email/verify/{id}/{token}', [SettingsController::class, 'verifyEmail'])->name('settings.email.verify');
Route::get('/settings/delete/{id}/{token}', [SettingsController::class, 'confirmDeletion'])->name('settings.delete.confirm');

Route::middleware(['auth', 'verified', 'role:parent'])->group(function (): void {
    Route::post('/doctors/{doctorProfile}/book/review', [AppointmentBookingController::class, 'review'])->middleware('throttle:10,1')->name('appointments.review');
    Route::post('/doctors/{doctorProfile}/book', [AppointmentBookingController::class, 'store'])->middleware('throttle:10,1')->name('appointments.store');
    Route::post('/appointments/slot-holds/{hold}/release', [AppointmentBookingController::class, 'releaseSlot'])->name('appointments.slot-holds.release');
    Route::get('/appointment-history', [ParentAppointmentController::class, 'history'])->name('appointments.history');
    Route::get('/doctors-consulted', [ParentAppointmentController::class, 'doctorsConsulted'])->name('appointments.doctors-consulted');
    Route::get('/appointments/{appointment}', [ParentAppointmentController::class, 'show'])->name('appointments.show');
    Route::get('/appointments/{appointment}/slots', [ParentAppointmentController::class, 'slots'])->name('appointments.slots');
    Route::post('/appointments/{appointment}/cancel', [ParentAppointmentController::class, 'cancel'])->name('appointments.cancel');
    Route::post('/appointments/{appointment}/reschedule', [ParentAppointmentController::class, 'reschedule'])->name('appointments.reschedule');
    Route::get('/appointments/{appointment}/payment-return', [AppointmentBookingController::class, 'paymentReturn'])->name('appointments.payment.return');
    Route::get('/appointments/{appointment}/payment-cancel', [AppointmentBookingController::class, 'paymentCancel'])->name('appointments.payment.cancel');
    Route::post('/appointments/{appointment}/payment-retry', [AppointmentBookingController::class, 'retryPayment'])->middleware('throttle:5,1')->name('appointments.payment.retry');
    Route::get('/notifications', [App\Http\Controllers\AppointmentNotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{notification}/read', [App\Http\Controllers\AppointmentNotificationController::class, 'read'])->name('notifications.read');
    Route::patch('/notifications/read-all', [App\Http\Controllers\AppointmentNotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::get('/prescriptions/{appointment}', [AppointmentPrescriptionController::class, 'show'])->name('prescriptions.show');
    Route::get('/prescriptions/{appointment}/download', [AppointmentPrescriptionController::class, 'download'])->name('prescriptions.download');
    Route::get('/payment-history', [ParentAppointmentController::class, 'paymentHistory'])->name('payments.history');
    Route::get('/payments/{payment}/receipt', [ParentAppointmentController::class, 'receipt'])->name('payments.receipt');
    Route::post('/activities/{activity}/enroll', [ActivityEnrollmentController::class, 'store'])->name('activities.enroll');
    Route::get('/dashboard', ParentDashboardController::class)->name('dashboard');
    Route::get('/dashboard/profile', [ParentProfileController::class, 'edit'])->name('dashboard.profile');
    Route::put('/dashboard/profile', [ParentProfileController::class, 'update'])->name('dashboard.profile.update');
    Route::get('/dashboard/profile/picture', [ParentProfileController::class, 'picture'])->name('dashboard.profile.picture');
    Route::get('/dashboard/children', [ChildController::class, 'index'])->name('dashboard.children.index');
    Route::get('/dashboard/children/create', [ChildController::class, 'create'])->name('dashboard.children.create');
    Route::post('/dashboard/children', [ChildController::class, 'store'])->name('dashboard.children.store');
    Route::get('/dashboard/children/{child}/edit', [ChildController::class, 'edit'])->name('dashboard.children.edit');
    Route::put('/dashboard/children/{child}', [ChildController::class, 'update'])->name('dashboard.children.update');
    Route::delete('/dashboard/children/{child}', [ChildController::class, 'destroy'])->name('dashboard.children.destroy');
    Route::get('/dashboard/progress', [ActivityProgressController::class, 'index'])->name('dashboard.progress');
    Route::post('/dashboard/progress/{activity}/tracking', [ActivityProgressController::class, 'setActivityTracking'])->name('dashboard.progress.tracking');
    Route::post('/dashboard/progress/{activity}/sections/{section}', [ActivityProgressController::class, 'toggleSection'])->name('dashboard.progress.section');
    Route::post('/dashboard/progress/{activity}/sections/{section}/track', [ActivityProgressController::class, 'trackSection'])->name('dashboard.progress.section.track');
    Route::post('/dashboard/progress/{activity}/lessons/{lesson}/complete', [ActivityProgressController::class, 'completeLesson'])->name('dashboard.progress.lesson.complete');
    Route::post('/dashboard/progress/{activity}/complete', [ActivityProgressController::class, 'completeActivity'])->name('dashboard.progress.complete');
    Route::get('/dashboard/settings', [SettingsController::class, 'edit'])->name('dashboard.settings');
    Route::post('/dashboard/settings/email', [SettingsController::class, 'requestEmailChange'])->name('dashboard.settings.email');
    Route::post('/dashboard/settings/delete', [SettingsController::class, 'requestDeletion'])->name('dashboard.settings.delete');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'verified', 'role:admin'])->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/notifications/send', [NotificationSendController::class, 'index'])->name('notifications.create');
    Route::post('/notifications/send', [NotificationSendController::class, 'store'])->middleware('throttle:10,1')->name('notifications.store');
    Route::get('/appointments', [AppointmentManagementController::class, 'index'])->name('appointments.index');
    Route::get('/appointments/{appointment}', [AppointmentManagementController::class, 'show'])->name('appointments.show');
    Route::post('/appointments/{appointment}/resolve', [AppointmentManagementController::class, 'resolve'])->name('appointments.resolve');
    Route::get('/refunds', [RefundManagementController::class, 'index'])->name('refunds.index');
    Route::patch('/refunds/{refundCase}', [RefundManagementController::class, 'update'])->name('refunds.update');
    Route::get('/notifications', [App\Http\Controllers\AppointmentNotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{notification}/read', [App\Http\Controllers\AppointmentNotificationController::class, 'read'])->name('notifications.read');
    Route::patch('/notifications/read-all', [App\Http\Controllers\AppointmentNotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::get('/prescriptions/{appointment}', [AppointmentPrescriptionController::class, 'show'])->name('prescriptions.show');
    Route::get('/prescriptions/{appointment}/download', [AppointmentPrescriptionController::class, 'download'])->name('prescriptions.download');
    Route::get('/doctors', [DoctorManagementController::class, 'index'])->name('doctors.index');
    Route::get('/doctors/{doctorProfile}', [DoctorManagementController::class, 'show'])->name('doctors.show');
    Route::patch('/doctors/{doctorProfile}/visibility', [DoctorProfileVisibilityController::class, 'visibility'])->name('doctors.visibility');
    Route::get('/doctor-specialties', [DoctorProfileVisibilityController::class, 'specialties'])->name('doctors.specialties');
    Route::post('/doctor-specialties', [DoctorProfileVisibilityController::class, 'storeSpecialty'])->name('doctors.specialties.store');
    Route::put('/doctor-specialties/{specialty}', [DoctorProfileVisibilityController::class, 'updateSpecialty'])->name('doctors.specialties.update');
    Route::patch('/doctors/{doctorProfile}/status', [DoctorManagementController::class, 'updateStatus'])->name('doctors.status');
    Route::patch('/doctors/{doctorProfile}/documents/{document}', [DoctorManagementController::class, 'reviewDocument'])->name('doctors.documents.review');
    Route::resource('activities', AdminActivityController::class)->except(['show']);
    Route::get('/activities/{activity}/preview', [AdminActivityController::class, 'preview'])->name('activities.preview');
    Route::post('/activities/{activity}/publication', [AdminActivityController::class, 'publication'])->name('activities.publication');
    Route::get('/activities/{activity}/icon', [AdminActivityController::class, 'icon'])->name('activities.icon');
    Route::get('/activities/{activity}/lessons', [ActivityLessonController::class, 'index'])->name('activities.lessons.index');
    Route::get('/activities/{activity}/lessons/create', [ActivityLessonController::class, 'create'])->name('activities.lessons.create');
    Route::post('/activities/{activity}/lessons', [ActivityLessonController::class, 'store'])->name('activities.lessons.store');
    Route::get('/activities/{activity}/lessons/{lesson}/edit', [ActivityLessonController::class, 'edit'])->name('activities.lessons.edit');
    Route::put('/activities/{activity}/lessons/{lesson}', [ActivityLessonController::class, 'update'])->name('activities.lessons.update');
    Route::delete('/activities/{activity}/lessons/{lesson}', [ActivityLessonController::class, 'destroy'])->name('activities.lessons.destroy');
    Route::post('/activities/{activity}/lessons/reorder', [ActivityLessonController::class, 'reorder'])->name('activities.lessons.reorder');
    Route::get('/activities/{activity}/lessons/{lesson}/sections', [ActivitySectionController::class, 'lessonIndex'])->name('activities.lessons.sections.index');
    Route::get('/activities/{activity}/lessons/{lesson}/sections/create', [ActivitySectionController::class, 'lessonCreate'])->name('activities.lessons.sections.create');
    Route::post('/activities/{activity}/lessons/{lesson}/sections', [ActivitySectionController::class, 'lessonStore'])->name('activities.lessons.sections.store');
    Route::get('/activities/{activity}/lessons/{lesson}/sections/{section}/edit', [ActivitySectionController::class, 'lessonEdit'])->name('activities.lessons.sections.edit');
    Route::put('/activities/{activity}/lessons/{lesson}/sections/{section}', [ActivitySectionController::class, 'lessonUpdate'])->name('activities.lessons.sections.update');
    Route::delete('/activities/{activity}/lessons/{lesson}/sections/{section}', [ActivitySectionController::class, 'lessonDestroy'])->name('activities.lessons.sections.destroy');
    Route::post('/activities/{activity}/lessons/{lesson}/sections/reorder', [ActivitySectionController::class, 'lessonReorder'])->name('activities.lessons.sections.reorder');
    Route::post('/activities/{activity}/lessons/{lesson}/sections/{section}/duplicate', [ActivitySectionController::class, 'lessonDuplicate'])->name('activities.lessons.sections.duplicate');
    Route::get('/activities/{activity}/sections', [ActivitySectionController::class, 'index'])->name('activities.sections.index');
    Route::get('/activities/{activity}/sections/create', [ActivitySectionController::class, 'create'])->name('activities.sections.create');
    Route::post('/activities/{activity}/sections', [ActivitySectionController::class, 'store'])->name('activities.sections.store');
    Route::get('/activities/{activity}/sections/{section}/edit', [ActivitySectionController::class, 'edit'])->name('activities.sections.edit');
    Route::put('/activities/{activity}/sections/{section}', [ActivitySectionController::class, 'update'])->name('activities.sections.update');
    Route::delete('/activities/{activity}/sections/{section}', [ActivitySectionController::class, 'destroy'])->name('activities.sections.destroy');
    Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [UserManagementController::class, 'show'])->name('users.show');
    Route::put('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');
    Route::patch('/users/{user}/role', [UserManagementController::class, 'updateRole'])->name('users.role');
    Route::get('/messages', [ContactMessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/{contactMessage}', [ContactMessageController::class, 'show'])->name('messages.show');
    Route::patch('/messages/{contactMessage}/read', [ContactMessageController::class, 'toggleRead'])->name('messages.read');
});
