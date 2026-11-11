<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        return view('notifications.staff', [
            'notifications' => $request->user()->notifications()->latest()->paginate(20),
            'layout' => 'layouts.doctor', 'section' => 'doctor-content',
        ]);
    }

    public function read(Request $request, string $notification): RedirectResponse
    {
        $item = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        $item->markAsRead();
        $destination = $item->data['url'] ?? route('doctor.dashboard');

        return redirect()->to($destination);
    }
}
