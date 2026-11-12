<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\DoctorProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class UserManagementController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $role = (string) $request->query('role', '');
        $allowedFilters = ['parent', 'doctors', 'pending_doctor', 'under_review', 'approved_doctor', 'rejected', 'suspended', 'admin'];
        if (! in_array($role, $allowedFilters, true)) {
            $role = '';
        }

        $users = User::query()
            ->when($role === 'doctors', fn ($query) => $query->whereHas('doctorProfile'))
            ->when($role === 'pending_doctor', fn ($query) => $query->whereHas('doctorProfile', fn ($profiles) => $profiles->where('status', DoctorProfile::STATUS_PENDING)))
            ->when($role === 'under_review', fn ($query) => $query->whereHas('doctorProfile', fn ($profiles) => $profiles->where('status', DoctorProfile::STATUS_UNDER_REVIEW)))
            ->when($role === 'approved_doctor', fn ($query) => $query->whereHas('doctorProfile', fn ($profiles) => $profiles->where('status', DoctorProfile::STATUS_APPROVED)))
            ->when($role === 'rejected', fn ($query) => $query->whereHas('doctorProfile', fn ($profiles) => $profiles->where('status', DoctorProfile::STATUS_REJECTED)))
            ->when($role === 'suspended', fn ($query) => $query->whereHas('doctorProfile', fn ($profiles) => $profiles->where('status', DoctorProfile::STATUS_SUSPENDED)))
            ->when(in_array($role, ['parent', 'admin'], true), fn ($query) => $query->where('role', $role))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($searchQuery) use ($search): void {
                    $searchQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->with('doctorProfile')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'search' => $search,
            'role' => $role,
            'roles' => [User::ROLE_PARENT, User::ROLE_ADMIN],
        ]);
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'in:parent,admin'],
        ]);

        if ($user->doctorProfile()->exists() || $user->hasRole(User::ROLE_PENDING_DOCTOR, User::ROLE_APPROVED_DOCTOR)) {
            return back()->withErrors(['role' => 'Use Doctor Management to review a doctor application. General role editing cannot verify doctors.']);
        }

        if ($user->is(auth()->user())) {
            return back()->withErrors(['role' => 'You cannot change your own role.']);
        }

        if ($user->isAdmin() && $validated['role'] !== User::ROLE_ADMIN && User::where('role', User::ROLE_ADMIN)->count() <= 1) {
            return back()->withErrors(['role' => 'The last administrator cannot be changed to another role.']);
        }

        $user->forceFill(['role' => $validated['role']])->save();

        return back()->with('status', "The role for {$user->name} was updated.");
    }

    public function show(User $user): View|RedirectResponse
    {
        if ($user->doctorProfile()->exists()) {
            return redirect()->route('admin.doctors.show', $user->doctorProfile()->firstOrFail());
        }

        return view('admin.users.show', ['user' => $user->load(['parentProfile', 'children'])]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'], 'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id], 'role' => ['required', 'in:parent,admin'],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'], 'contact' => ['nullable', 'string', 'max:50'],
        ]);
        if ($user->doctorProfile()->exists() || $user->hasRole(User::ROLE_PENDING_DOCTOR, User::ROLE_APPROVED_DOCTOR)) {
            return back()->withErrors(['role' => 'Use Doctor Management to review a doctor application.']);
        }
        if ($user->is($request->user()) && $data['role'] !== User::ROLE_ADMIN) return back()->withErrors(['role' => 'You cannot remove your own administrator access.']);
        $user->forceFill([
            'first_name' => $data['first_name'], 'last_name' => $data['last_name'],
            'name' => $data['first_name'].' '.$data['last_name'], 'email' => $data['email'], 'role' => $data['role'],
        ])->save();
        $user->parentProfile()->updateOrCreate([], ['date_of_birth' => $data['date_of_birth'] ?? null, 'contact' => $data['contact'] ?? null]);
        return back()->with('status', 'User information updated.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) return back()->withErrors(['user' => 'You cannot delete your own account here.']);
        if ($user->isAdmin() && User::where('role', User::ROLE_ADMIN)->count() <= 1) return back()->withErrors(['user' => 'The last administrator cannot be deleted.']);
        if ($user->doctorProfile()->exists()) return back()->withErrors(['user' => 'Doctor verification history is retained. Suspend the doctor through Doctor Management instead.']);
        if ($user->patientAppointments()->exists() || $user->sentAppointmentPayments()->exists()) return back()->withErrors(['user' => 'Appointment and payment history is retained; this account cannot be deleted.']);
        if ($user->doctorVerificationEvents()->exists() || $user->reviewedDoctorProfiles()->exists() || $user->reviewedDoctorDocuments()->exists()) {
            return back()->withErrors(['user' => 'This account is part of the doctor verification audit history and cannot be deleted.']);
        }
        $user->delete();
        return redirect()->route('admin.users.index')->with('status', 'User deleted.');
    }
}
