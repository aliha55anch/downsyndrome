<?php

namespace App\Http\Controllers;

use App\Models\ActivityEnrollment;
use App\Models\Appointment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ParentDashboardController extends Controller
{
    public function __invoke(Request $request): RedirectResponse|View
    {
        if ($request->boolean('later')) {
            $request->session()->put('profile_deferred', true);
        }

        $user = $request->user()->load('children');
        $needsProfile = $user->children->isEmpty();
        if ($needsProfile && ! $request->session()->get('profile_deferred')) {
            return redirect()->route('dashboard.profile');
        }

        $enrollments = ActivityEnrollment::with(['activity', 'child'])
            ->where('user_id', $user->id)
            ->orderByDesc('updated_at')
            ->get();
        $enrolledCount = $enrollments->count();
        $inProgressCount = $enrollments->where('status', 'in_progress')->count();
        $completedCount = $enrollments->where('status', 'completed')->count();
        $overallProgress = $enrolledCount
            ? (int) round($enrollments->avg('progress_percent'))
            : 0;
        $appointmentHistory = Appointment::with(['doctorProfile.user', 'payments'])
            ->where('patient_user_id', $user->id)
            ->whereNotIn('status', Appointment::BLOCKING_STATUSES)
            ->where(function ($query): void {
                $query->where('starts_at', '<', now('UTC'))
                    ->orWhereIn('status', [Appointment::CANCELLED, Appointment::EXPIRED]);
            })
            ->orderByDesc('starts_at')->limit(5)->get();
        $upcomingAppointments = Appointment::with(['doctorProfile.user', 'payments'])
            ->where('patient_user_id', $user->id)
            ->whereIn('status', [Appointment::PENDING_PAYMENT, Appointment::PENDING_CONFIRMATION, Appointment::CONFIRMED, Appointment::RESCHEDULED])
            ->where('starts_at', '>=', now('UTC'))
            ->orderBy('starts_at')->limit(3)->get();
        $unreadNotifications = $user->unreadNotifications()->count();

        return view('dashboard.index', compact(
            'user',
            'needsProfile',
            'enrollments',
            'enrolledCount',
            'inProgressCount',
            'completedCount',
            'overallProgress',
            'appointmentHistory',
            'upcomingAppointments',
            'unreadNotifications',
        ));
    }
}
