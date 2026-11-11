<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityEnrollment;
use App\Models\ActivityTrackingPreference;
use App\Models\Child;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ActivityEnrollmentController extends Controller
{
    public function store(Request $request, Activity $activity): RedirectResponse
    {
        abort_unless($activity->isPublished(), 404);
        $user = $request->user();
        $data = $request->validate([
            'child_id' => [
                'nullable',
                'integer',
                Rule::exists('children', 'id')->where(fn ($query) => $query->where('user_id', $user->id)),
            ],
        ]);
        $child = ! empty($data['child_id']) ? $user->children()->findOrFail($data['child_id']) : null;

        try {
            $created = DB::transaction(function () use ($activity, $user, $child): bool {
                Activity::query()->whereKey($activity->id)->lockForUpdate()->firstOrFail();
                if ($user->activityEnrollments()->where('activity_id', $activity->id)->exists()) {
                    return false;
                }

                ActivityEnrollment::create([
                    'user_id' => $user->id,
                    'activity_id' => $activity->id,
                    'child_id' => $child?->id,
                    'status' => 'enrolled',
                    'progress_percent' => 0,
                    'enrolled_at' => now(),
                ]);

                if ($child) {
                    ActivityTrackingPreference::updateOrCreate(
                        ['user_id' => $user->id, 'child_id' => $child->id, 'activity_id' => $activity->id],
                        ['is_tracking' => true]
                    );
                }

                return true;
            });
        } catch (QueryException $exception) {
            if (! $user->activityEnrollments()->where('activity_id', $activity->id)->exists()) {
                throw $exception;
            }
            $created = false;
        }

        return redirect()->route('activities.index', ['tab' => 'my'])
            ->with('status', $created ? 'Successfully Enrolled!' : 'You are already enrolled in this activity.');
    }
}
