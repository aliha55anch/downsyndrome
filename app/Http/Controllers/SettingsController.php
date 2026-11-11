<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\ConfirmAccountDeletionNotification;
use App\Notifications\VerifyNewEmailNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(Request $request): View { return view('dashboard.settings', ['user' => $request->user()]); }

    public function requestEmailChange(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255', 'unique:users,email']]);
        $user = $request->user();
        $token = Str::random(64);
        $user->forceFill(['pending_email' => $data['email'], 'email_change_token_hash' => Hash::make($token), 'email_change_token_expires_at' => now()->addHour()])->save();
        $url = URL::temporarySignedRoute('settings.email.verify', now()->addHour(), ['id' => $user->id, 'token' => $token]);
        $user->notify(new VerifyNewEmailNotification($url));
        return back()->with('status', 'A confirmation link was sent to the new email address.');
    }

    public function verifyEmail(Request $request, int $id, string $token): RedirectResponse
    {
        abort_unless($request->hasValidSignature(), 403);
        $user = User::findOrFail($id);
        abort_unless($user->email_change_token_expires_at?->isFuture() && Hash::check($token, $user->email_change_token_hash ?? ''), 403);
        $user->forceFill(['email' => $user->pending_email, 'pending_email' => null, 'email_change_token_hash' => null, 'email_change_token_expires_at' => null, 'email_verified_at' => now()])->save();
        return redirect()->route('login')->with('status', 'Email updated. Please log in with your new email.');
    }

    public function requestDeletion(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']]);
        $user = $request->user();
        $token = Str::random(64);
        $user->forceFill(['deletion_token_hash' => Hash::make($token), 'deletion_token_expires_at' => now()->addHour()])->save();
        $url = URL::temporarySignedRoute('settings.delete.confirm', now()->addHour(), ['id' => $user->id, 'token' => $token]);
        $user->notify(new ConfirmAccountDeletionNotification($url));
        return back()->with('status', 'A deletion confirmation link was sent to your email.');
    }

    public function confirmDeletion(Request $request, int $id, string $token): RedirectResponse
    {
        abort_unless($request->hasValidSignature(), 403);
        $user = User::findOrFail($id);
        abort_unless($user->deletion_token_expires_at?->isFuture() && Hash::check($token, $user->deletion_token_hash ?? ''), 403);
        if ($user->isAdmin() && User::where('role', User::ROLE_ADMIN)->count() <= 1) return redirect('/')->withErrors(['account' => 'The last administrator cannot be deleted.']);
        if ($user->patientAppointments()->exists() || $user->sentAppointmentPayments()->exists()) {
            return redirect('/')->withErrors(['account' => 'This account has appointment or payment history that must be retained. Contact an administrator for account privacy options.']);
        }
        if (Auth::id() === $user->id) Auth::logout();
        $user->delete();
        return redirect()->route('login')->with('status', 'Your account was permanently deleted.');
    }
}
