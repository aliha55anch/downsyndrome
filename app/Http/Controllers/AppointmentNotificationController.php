<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppointmentNotificationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $notifications = $user->notifications()->latest()->paginate(20);
        if ($user->hasRole(User::ROLE_PARENT)) return view('notifications.index', compact('notifications'));
        return view('notifications.staff', [
            'notifications' => $notifications,
            'layout' => $user->hasRole(User::ROLE_ADMIN) ? 'layouts.admin' : 'layouts.doctor',
            'section' => $user->hasRole(User::ROLE_ADMIN) ? 'content' : 'doctor-content',
        ]);
    }

    public function read(Request $request, string $notification): RedirectResponse
    {
        $item = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        $item->markAsRead();
        $destination = $item->data['url'] ?? null;
        if (is_string($destination) && str_starts_with($destination, '/') && ! str_starts_with($destination, '//')) {
            return redirect()->to($destination);
        }
        return redirect()->route($request->user()->hasRole(User::ROLE_ADMIN) ? 'admin.notifications.index' : 'notifications.index');
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);
        return back()->with('status', 'All notifications marked as read.');
    }
}

