<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\IntendedRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    public function create(): View
    {
        return view('auth.login');
    }

    public function adminCreate(): View
    {
        return view('auth.admin-login');
    }

    public function doctorCreate(): View
    {
        return view('auth.doctor-login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
            'portal' => ['nullable', 'in:family,admin,doctor'],
        ]);

        $key = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            throw ValidationException::withMessages([
                'email' => 'Too many login attempts. Please try again in '.RateLimiter::availableIn($key).' seconds.',
            ]);
        }

        if (! Auth::attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ], (bool) ($credentials['remember'] ?? false))) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages([
                'email' => 'The provided credentials do not match our records.',
            ]);
        }

        $user = $request->user();
        $portal = $credentials['portal'] ?? 'family';

        if ($portal === 'admin' && ! $user->isAdmin()) {
            Auth::logout();
            RateLimiter::clear($key);

            throw ValidationException::withMessages([
                'email' => 'This account does not have administrator access. Use the family login or contact support.',
            ]);
        }

        if ($portal === 'doctor' && ! $user->hasRole(User::ROLE_PENDING_DOCTOR, User::ROLE_APPROVED_DOCTOR)) {
            Auth::logout();
            RateLimiter::clear($key);

            throw ValidationException::withMessages([
                'email' => 'This account is not registered as a healthcare professional. Apply for doctor verification or use the family login.',
            ]);
        }

        if (! $request->user()->hasVerifiedEmail()) {
            Auth::logout();
            RateLimiter::clear($key);

            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'Please verify your email address before logging in.',
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        if ($request->user()->hasRole(User::ROLE_ADMIN)) {
            return IntendedRedirect::administrator($request, route('admin.dashboard'));
        }

        if ($request->user()->hasRole(User::ROLE_PARENT)) {
            return IntendedRedirect::parent($request, route('activities.index'));
        }

        if ($request->user()->hasRole(User::ROLE_PENDING_DOCTOR, User::ROLE_APPROVED_DOCTOR)) {
            return $request->user()->hasRole(User::ROLE_APPROVED_DOCTOR)
                ? redirect()->route('doctor.appointments.index')
                : redirect()->route('doctor.dashboard');
        }

        return redirect()->intended('/');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('status', 'You have been logged out.');
    }

    private function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower($request->input('email')).'|'.$request->ip());
    }
}
