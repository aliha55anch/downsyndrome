<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivitySectionProgress;
use App\Models\ActivityLessonProgress;
use App\Models\ActivityTrackingPreference;
use App\Models\ActivityEnrollment;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ActivityController extends Controller
{
    public function index(): View
    {
        $tab = request()->query('tab', 'all');
        if (! in_array($tab, ['my', 'all', 'completed'], true)) {
            $tab = 'all';
        }
        $totalActivities = Activity::published()->count();
        $query = Activity::published()
            ->when(request('search'), function ($query, string $search): void {
                $query->where(function ($searchQuery) use ($search): void {
                    $searchQuery->where('title', 'like', "%{$search}%")
                        ->orWhere('short_description', 'like', "%{$search}%");
                });
            })
            ->when(request('age_group'), fn ($query, string $ageGroup) => $query->where('age_group', $ageGroup))
            ->when(request('development_area'), fn ($query, string $area) => $query->where('development_area', $area))
            ->latest('published_at');

        $allActivities = $query->get();
        $selectedChild = null;
        $enrollmentByActivity = collect();
        if (auth()->check() && auth()->user()->hasRole('parent')) {
            $enrollmentByActivity = ActivityEnrollment::where('user_id', auth()->id())->get()->keyBy('activity_id');
            $children = auth()->user()->children()->orderBy('name')->get();
            $selectedChild = $children->firstWhere('id', (int) request('child_id')) ?? $children->first();
            if ($selectedChild?->date_of_birth) {
                $age = Carbon::parse($selectedChild->date_of_birth)->age;
                $allActivities = $allActivities->sortByDesc(fn (Activity $activity) => $activity->min_age !== null && $activity->max_age !== null && $age >= $activity->min_age && $age <= $activity->max_age)->values();
            }
        }

        if ($tab === 'my') {
            $allActivities = $allActivities->filter(function (Activity $activity) use ($enrollmentByActivity): bool {
                $enrollment = $enrollmentByActivity->get($activity->id);
                return $enrollment && $enrollment->status !== 'completed';
            })->values();
        } elseif ($tab === 'completed') {
            $allActivities = $allActivities->filter(fn (Activity $activity) => $enrollmentByActivity->get($activity->id)?->status === 'completed')->values();
        }

        $page = LengthAwarePaginator::resolveCurrentPage();
        $activities = new LengthAwarePaginator(
            $allActivities->forPage($page, 12)->values(),
            $allActivities->count(),
            12,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => request()->query()]
        );

        return view('activities.index', [
            'activities' => $activities,
            'totalActivities' => $totalActivities,
            'ageGroups' => Activity::AGE_GROUPS,
            'developmentAreas' => Activity::DEVELOPMENT_AREAS,
            'filters' => request()->only(['search', 'age_group', 'development_area']),
            'children' => $children ?? collect(),
            'selectedChild' => $selectedChild,
            'enrollmentByActivity' => $enrollmentByActivity,
            'tab' => $tab,
        ]);
    }

    public function show(Activity $activity): View
    {
        $activity->load(['sections' => fn ($query) => $query->whereNull('lesson_id'), 'lessons.sections']);
        abort_unless($activity->isPublished(), 404);
        $allSections = $activity->sections->concat($activity->lessons->flatMap->sections);
        $selectedLesson = $activity->lessons->firstWhere('id', (int) request('lesson_id'));
        $enrollment = auth()->check() && auth()->user()->hasRole('parent')
            ? ActivityEnrollment::where('user_id', auth()->id())->where('activity_id', $activity->id)->first()
            : null;
        if (! $selectedLesson && $enrollment?->lastLesson && $enrollment->lastLesson->activity_id === $activity->id) {
            $selectedLesson = $activity->lessons->firstWhere('id', $enrollment->last_lesson_id);
        }
        $selectedLesson ??= $activity->lessons->first();
        $selectedSection = null;
        if (request()->filled('section_id')) {
            $selectedSection = $allSections->firstWhere('id', request()->integer('section_id'));
            if ($selectedSection && $selectedSection->activity_id === $activity->id) {
                $selectedLesson = $selectedSection->lesson_id ? $activity->lessons->firstWhere('id', $selectedSection->lesson_id) : $selectedLesson;
            } else {
                $selectedSection = null;
            }
        } elseif ($enrollment?->last_section_id) {
            $savedSection = $allSections->firstWhere('id', $enrollment->last_section_id);
            if ($savedSection && $savedSection->lesson_id && $savedSection->lesson_id === $selectedLesson?->id) {
                $selectedSection = $savedSection;
            }
        }
        if ($enrollment) {
            $enrollment->forceFill([
                'status' => $enrollment->status === 'completed' ? 'completed' : 'in_progress',
                'started_at' => $enrollment->started_at ?? now(),
                'last_lesson_id' => $selectedLesson?->id ?? $enrollment->last_lesson_id,
                'last_section_id' => $selectedSection?->id ?? ($selectedLesson && $selectedLesson->id === $enrollment->last_lesson_id ? $enrollment->last_section_id : null),
            ])->save();
        }

        $children = collect();
        $selectedChild = null;
        $isTracking = false;
        $sectionStates = collect();
        $lessonStates = collect();
        if (auth()->check() && auth()->user()->hasRole('parent')) {
            $children = auth()->user()->children()->orderBy('name')->get();
            $selectedChild = $children->firstWhere('id', (int) request('child_id'))
                ?? $children->firstWhere('id', $enrollment?->child_id)
                ?? $children->first();
            if ($selectedChild) {
                $isTracking = ActivityTrackingPreference::where('user_id', auth()->id())
                    ->where('child_id', $selectedChild->id)
                    ->where('activity_id', $activity->id)
                    ->where('is_tracking', true)
                    ->exists();
                if ($isTracking) {
                    $sectionStates = ActivitySectionProgress::where('user_id', auth()->id())
                        ->where('child_id', $selectedChild->id)
                        ->whereIn('activity_section_id', $allSections->pluck('id'))
                        ->get()->keyBy('activity_section_id');
                    $lessonStates = ActivityLessonProgress::where('user_id', auth()->id())
                        ->where('child_id', $selectedChild->id)
                        ->whereIn('activity_lesson_id', $activity->lessons->pluck('id'))
                        ->get()->keyBy('activity_lesson_id');
                }
            }
        }

        return view('activities.show', compact('activity', 'children', 'selectedChild', 'isTracking', 'sectionStates', 'lessonStates', 'allSections', 'selectedLesson', 'selectedSection', 'enrollment'));
    }

    public function slideshow(Activity $activity): View
    {
        abort_unless($activity->isPublished(), 404);

        $presentation = collect($activity->content_blocks ?? [])->firstWhere('type', 'presentation');
        abort_unless(is_array($presentation) && ! empty($presentation['slides']), 404);

        return view('activities.slideshow', [
            'activity' => $activity,
            'slides' => $presentation['slides'],
        ]);
    }

    public function image(Activity $activity): BinaryFileResponse
    {
        abort_unless(($activity->isPublished() || auth()->user()?->isAdmin()) && $activity->featured_image_path, 404);

        $path = storage_path('app/private/'.$activity->featured_image_path);
        abort_unless(is_file($path), 404);

        return response()->file($path);
    }

    public function icon(Activity $activity): BinaryFileResponse
    {
        abort_unless(($activity->isPublished() || auth()->user()?->isAdmin()) && $activity->icon_path, 404);
        $path = Storage::disk('local')->path($activity->icon_path);
        abort_unless(is_file($path), 404);
        return response()->file($path);
    }

    public function sectionMedia(Activity $activity, \App\Models\ActivitySection $section): BinaryFileResponse
    {
        abort_unless(($activity->isPublished() || auth()->user()?->isAdmin()) && $section->activity_id === $activity->id && ($section->content['media_path'] ?? null), 404);
        $path = Storage::disk('local')->path($section->content['media_path']);
        abort_unless(is_file($path), 404);
        if ($section->content_type === 'pdf') {
            return response()->file($path, ['Content-Type' => 'application/pdf', 'X-Content-Type-Options' => 'nosniff']);
        }
        if ($section->content_type === 'word') {
            $filename = $section->content['media_filename'] ?? 'document.docx';
            return response()->download($path, basename($filename), ['X-Content-Type-Options' => 'nosniff']);
        }
        return response()->file($path);
    }

    public function sectionIcon(Activity $activity, \App\Models\ActivitySection $section): BinaryFileResponse
    {
        abort_unless(($activity->isPublished() || auth()->user()?->isAdmin()) && $section->activity_id === $activity->id && $section->icon_path, 404);
        $path = Storage::disk('local')->path($section->icon_path);
        abort_unless(is_file($path), 404);
        return response()->file($path);
    }
}
