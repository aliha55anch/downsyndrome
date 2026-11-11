<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ContactMessage;
use App\Models\DoctorProfile;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'totalUsers' => User::count(),
            'totalParents' => User::where('role', User::ROLE_PARENT)->count(),
            'totalDoctors' => DoctorProfile::count(),
            'pendingDoctors' => DoctorProfile::where('status', DoctorProfile::STATUS_PENDING)->count(),
            'underReviewDoctors' => DoctorProfile::where('status', DoctorProfile::STATUS_UNDER_REVIEW)->count(),
            'approvedDoctors' => DoctorProfile::where('status', DoctorProfile::STATUS_APPROVED)->count(),
            'rejectedDoctors' => DoctorProfile::where('status', DoctorProfile::STATUS_REJECTED)->count(),
            'suspendedDoctors' => DoctorProfile::where('status', DoctorProfile::STATUS_SUSPENDED)->count(),
            'totalContactMessages' => ContactMessage::count(),
            'unreadContactMessages' => ContactMessage::where('is_read', false)->count(),
            'totalActivities' => Activity::count(),
            'publishedActivities' => Activity::where('status', Activity::STATUS_PUBLISHED)->count(),
        ]);
    }
}
