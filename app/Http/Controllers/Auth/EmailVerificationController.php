<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\IntendedRedirect;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    public function notice(): View|RedirectResponse
    {
        if (request()->user()->hasVerifiedEmail()) {
            $user = request()->user();
            $fallback = match (true) {
                $user->isAdmin() => route('admin.dashboard'),
                $user->hasRole(User::ROLE_APPROVED_DOCTOR) => route('doctor.appointments.index'),
                $user->hasRole(User::ROLE_PENDING_DOCTOR) => route('doctor.dashboard'),
                default => '/',
            };
            return $user->isAdmin()
                ? IntendedRedirect::administrator(request(), $fallback)
                : ($user->hasRole(User::ROLE_PARENT)
                    ? IntendedRedirect::parent(request(), $fallback)
                    : redirect()->to($fallback));
        }

        return view('auth.verify-email');
    }

    public function verify(Request $request, int $id, string $hash): RedirectResponse
    {
        $user = User::findOrFail($id);
        abort_unless(hash_equals(sha1($user->getEmailForVerification()), $hash), 403);

        if (! $user->hasVerifiedEmail()) {
            if ($user->markEmailAsVerified()) {
                event(new Verified($user));
            }

            if (Auth::check() && Auth::id() === $user->id) {
                $fallback = match (true) {
                    $user->isAdmin() => route('admin.dashboard'),
                    $user->hasRole(User::ROLE_APPROVED_DOCTOR) => route('doctor.appointments.index'),
                    $user->hasRole(User::ROLE_PENDING_DOCTOR) => route('doctor.dashboard'),
                    default => '/',
                };

                $response = $user->isAdmin()
                    ? IntendedRedirect::administrator($request, $fallback)
                    : ($user->hasRole(User::ROLE_PARENT)
                        ? IntendedRedirect::parent($request, $fallback)
                        : redirect()->to($fallback));

                return $response
                    ->with('status', 'Your email address has been verified.');
            }

            return redirect()->route('login')->with('status', 'Your email address has been verified. You can now log in.');
        }

        return redirect()->route('login')->with('status', 'Your email address is already verified. You can now log in.');
    }

    public function send(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect('/');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'A new verification link has been sent to your email address.');
    }
}
