<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\DoctorProfile;
use App\Models\User;

class EnsureApprovedDoctor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_unless($user?->hasRole(User::ROLE_APPROVED_DOCTOR)
            && $user->doctorProfile?->status === DoctorProfile::STATUS_APPROVED, 403);

        return $next($request);
    }
}
