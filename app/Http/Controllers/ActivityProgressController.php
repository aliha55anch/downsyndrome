<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityEnrollment;
use App\Models\ActivityLesson;
use App\Models\ActivityLessonProgress;
use App\Models\ActivityProgress;
use App\Models\ActivityProgressEvent;
use App\Models\ActivitySection;
use App\Models\ActivitySectionProgress;
use App\Models\ActivityTrackingPreference;
use App\Models\Child;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ActivityProgressController extends Controller
{
    public function index(Request $request): View
    {
        $children = $request->user()->children()->orderBy('name')->get();
        $selectedChild = $children->firstWhere('id', (int) $request->integer('child_id')) ?? $children->first();
        $activities = Activity::published()->with(['sections' => fn ($query) => $query->whereNull('lesson_id'), 'lessons.sections'])->orderBy('title')->get();
        $trackedActivityIds = $selectedChild
            ? ActivityEnrollment::where('user_id', $request->user()->id)->where('child_id', $selectedChild->id)->whereIn('status', ['enrolled', 'in_progress', 'completed'])->pluck('activity_id')->map(fn ($id) => (int) $id)->all()
            : [];
        $cards = $activities->filter(fn (Activity $activity) => in_array($activity->id, $trackedActivityIds, true))
            ->map(fn (Activity $activity) => $this->cardFor($activity, $selectedChild))->values();
        $history = $selectedChild && $trackedActivityIds
            ? ActivityProgressEvent::where('user_id', $request->user()->id)->where('child_id', $selectedChild->id)->whereIn('activity_id', $trackedActivityIds)->latest()->limit(20)->get()
            : collect();
        $areaSummary = $cards->groupBy(fn ($card) => $card['activity']->development_area)
            ->map(fn ($group) => [
                'completed' => $group->where('status', 'completed')->count(),
                'total' => $group->count(),
                'percent' => $group->count() ? (int) round($group->avg('percent')) : 0,
            ]);

        return view('dashboard.progress', compact('children', 'selectedChild', 'cards', 'activities', 'trackedActivityIds', 'history', 'areaSummary'));
    }

    public function toggleSection(Request $request, Activity $activity, ActivitySection $section): RedirectResponse
    {
        abort_unless($activity->isPublished() && $section->activity_id === $activity->id && $this->trackable($section), 404);
        $child = $this->ownedChild($request, $request->integer('child_id'));
        $this->requireTracking($request, $activity, $child);
        $status = $request->validate(['status' => ['required', 'in:not_started,completed']])['status'];

        DB::transaction(function () use ($request, $activity, $section, $child, $status): void {
            $current = ActivitySectionProgress::firstOrNew([
                'user_id' => $request->user()->id,
                'child_id' => $child->id,
                'activity_section_id' => $section->id,
            ]);
            $changed = $current->status !== $status;
            $current->fill([
                'activity_id' => $activity->id,
                'section_title_snapshot' => $section->title ?: ucfirst(str_replace('_', ' ', $section->content_type)),
                'status' => $status,
                'completion_method' => $status === 'completed' ? 'manual' : null,
                'completed_at' => $status === 'completed' ? ($current->completed_at ?? now()) : null,
            ])->save();
            $this->saveEnrollmentPosition($request, $child, $activity, $section);
            if ($changed) {
                $this->recordEvent($request, $child, $activity, $section, $status, $status === 'completed' ? 'manual' : null);
            }
            $this->recalculate($request, $child, $activity);
        });

        return back()->with('status', 'Section progress updated.');
    }

    public function setActivityTracking(Request $request, Activity $activity): RedirectResponse
    {
        abort_unless($activity->isPublished(), 404);
        $data = $request->validate([
            'child_id' => ['required', 'integer'],
            'is_tracking' => ['required', 'boolean'],
        ]);
        $child = $this->ownedChild($request, (int) $data['child_id']);
        ActivityTrackingPreference::updateOrCreate(
            ['user_id' => $request->user()->id, 'child_id' => $child->id, 'activity_id' => $activity->id],
            ['is_tracking' => (bool) $data['is_tracking']]
        );

        return back()->with('status', $data['is_tracking'] ? 'Progress tracking started for '.$child->name.'.' : 'Progress tracking stopped for '.$child->name.'. Saved progress is kept.');
    }

    public function trackSection(Request $request, Activity $activity, ActivitySection $section): JsonResponse
    {
        abort_unless($activity->isPublished() && $section->activity_id === $activity->id && $this->trackable($section), 404);
        $data = $request->validate([
            'child_id' => ['required', 'integer'],
            'method' => ['required', 'in:content_viewed,video_ended'],
        ]);
        if ($data['method'] === 'video_ended') {
            abort_unless(in_array($section->content_type, ['video', 'youtube'], true), 422);
        }
        $child = $this->ownedChild($request, (int) $data['child_id']);
        $this->requireTracking($request, $activity, $child);

        DB::transaction(function () use ($request, $activity, $section, $child, $data): void {
            $this->saveSectionCompletion($request, $child, $activity, $section, $data['method']);
            $this->recalculate($request, $child, $activity);
        });

        return response()->json(['ok' => true, 'status' => 'completed', 'method' => $data['method']]);
    }

    public function completeLesson(Request $request, Activity $activity, ActivityLesson $lesson): RedirectResponse
    {
        abort_unless($activity->isPublished() && $lesson->activity_id === $activity->id, 404);
        $data = $request->validate([
            'child_id' => ['required', 'integer'],
            'lesson_id' => ['required', 'integer', 'exists:activity_lessons,id'],
        ]);
        abort_unless((int) $data['lesson_id'] === $lesson->id, 422);
        $child = $this->ownedChild($request, (int) $data['child_id']);
        $this->requireTracking($request, $activity, $child);

        DB::transaction(function () use ($request, $activity, $lesson, $child): void {
            $lessonState = ActivityLessonProgress::firstOrNew([
                'user_id' => $request->user()->id,
                'child_id' => $child->id,
                'activity_lesson_id' => $lesson->id,
            ]);
            $lessonWasComplete = $lessonState->status === 'completed';
            $lessonState->fill([
                'activity_id' => $activity->id,
                'status' => 'completed',
                'completed_at' => $lessonState->completed_at ?? now(),
            ])->save();

            foreach ($lesson->sections as $section) {
                if (! $this->trackable($section)) {
                    continue;
                }
                $sectionState = ActivitySectionProgress::firstOrNew([
                    'user_id' => $request->user()->id,
                    'child_id' => $child->id,
                    'activity_section_id' => $section->id,
                ]);
                if ($sectionState->status !== 'completed') {
                    $this->saveSectionCompletion($request, $child, $activity, $section, 'lesson_completed');
                }
            }

            if (! $lessonWasComplete) {
                $this->recordEvent($request, $child, $activity, null, 'completed', 'lesson_completed', $lesson);
            }
            $this->recalculate($request, $child, $activity);
        });

        return back()->with('status', 'Lesson marked complete for '.$child->name.'.');
    }

    public function completeActivity(Request $request, Activity $activity): RedirectResponse
    {
        abort_unless($activity->isPublished() && $this->sectionsFor($activity)->filter(fn ($section) => $this->trackable($section))->isEmpty() && $activity->lessons->isEmpty(), 404);
        $child = $this->ownedChild($request, $request->integer('child_id'));
        $this->requireTracking($request, $activity, $child);
        DB::transaction(function () use ($request, $child, $activity): void {
            $summary = ActivityProgress::firstOrNew(['user_id' => $request->user()->id, 'child_id' => $child->id, 'activity_id' => $activity->id]);
            $changed = $summary->status !== 'completed';
            $summary->fill(['status' => 'completed', 'progress_percent' => 100, 'last_practiced_on' => today()])->save();
            $this->syncEnrollment($request, $child, $activity, 100);
            if ($changed) {
                $this->recordEvent($request, $child, $activity, null, 'completed', 'manual');
            }
        });

        return back()->with('status', 'Activity marked completed.');
    }

    private function cardFor(Activity $activity, ?Child $child): array
    {
        $sections = $this->sectionsFor($activity)->filter(fn ($section) => $this->trackable($section))->values();
        $states = $child && $sections->isNotEmpty()
            ? ActivitySectionProgress::where('user_id', $child->user_id)->where('child_id', $child->id)->whereIn('activity_section_id', $sections->pluck('id'))->get()->keyBy('activity_section_id')
            : collect();
        $lessonStates = $child && $activity->lessons->isNotEmpty()
            ? ActivityLessonProgress::where('user_id', $child->user_id)->where('child_id', $child->id)->whereIn('activity_lesson_id', $activity->lessons->pluck('id'))->get()->keyBy('activity_lesson_id')
            : collect();
        $completed = $states->where('status', 'completed')->count();
        $lessons = $activity->lessons->map(function (ActivityLesson $lesson) use ($states, $lessonStates): array {
            $lessonSections = $lesson->sections->filter(fn ($section) => $this->trackable($section));
            $sectionCompleted = $lessonSections->filter(fn ($section) => ($states->get($section->id)?->status ?? '') === 'completed')->count();
            $explicitStatus = $lessonStates->get($lesson->id)?->status ?? 'not_started';
            $status = $lessonSections->isNotEmpty()
                ? ($sectionCompleted === $lessonSections->count() ? 'completed' : ($sectionCompleted > 0 ? 'in_progress' : $explicitStatus))
                : $explicitStatus;
            $percent = $lessonSections->isNotEmpty()
                ? (int) round(($sectionCompleted / $lessonSections->count()) * 100)
                : ($status === 'completed' ? 100 : 0);

            return ['lesson' => $lesson, 'total' => $lessonSections->count(), 'completed' => $sectionCompleted, 'status' => $status, 'percent' => $percent];
        })->values();

        $emptyLessons = $lessons->where('total', 0);
        $emptyLessonsCompleted = $emptyLessons->where('status', 'completed')->count();
        $total = $sections->count() + $emptyLessons->count();
        $completedUnits = $completed + $emptyLessonsCompleted;
        $legacyStatus = $child ? ActivityProgress::where('user_id', $child->user_id)->where('child_id', $child->id)->where('activity_id', $activity->id)->value('status') : null;
        $percent = $total ? (int) round(($completedUnits / $total) * 100) : ($legacyStatus === 'completed' ? 100 : 0);

        return [
            'activity' => $activity,
            'sections' => $sections,
            'states' => $states,
            'lessons' => $lessons,
            'completed_lessons' => $lessons->where('status', 'completed')->count(),
            'total_lessons' => $lessons->count(),
            'videos_completed' => $states->where('completion_method', 'video_ended')->count(),
            'completed' => $completedUnits,
            'total' => $total,
            'percent' => $percent,
            'status' => $percent === 100 && ($total > 0 || $legacyStatus === 'completed') ? 'completed' : ($percent > 0 ? 'in_progress' : ($legacyStatus ?? 'not_started')),
        ];
    }

    private function saveSectionCompletion(Request $request, Child $child, Activity $activity, ActivitySection $section, string $method): void
    {
        $current = ActivitySectionProgress::firstOrNew([
            'user_id' => $request->user()->id,
            'child_id' => $child->id,
            'activity_section_id' => $section->id,
        ]);
        if ($current->status === 'completed') {
            return;
        }

        $current->fill([
            'activity_id' => $activity->id,
            'section_title_snapshot' => $section->title ?: ucfirst(str_replace('_', ' ', $section->content_type)),
            'status' => 'completed',
            'completion_method' => $method,
            'completed_at' => now(),
        ])->save();
        $this->saveEnrollmentPosition($request, $child, $activity, $section);
        $this->recordEvent($request, $child, $activity, $section, 'completed', $method);
    }

    private function recalculate(Request $request, Child $child, Activity $activity): void
    {
        $sections = $this->sectionsFor($activity)->filter(fn ($section) => $this->trackable($section))->values();
        $sectionStates = $sections->isNotEmpty()
            ? ActivitySectionProgress::where('user_id', $request->user()->id)->where('child_id', $child->id)->whereIn('activity_section_id', $sections->pluck('id'))->get()->keyBy('activity_section_id')
            : collect();
        $completedSections = $sectionStates->where('status', 'completed')->count();

        foreach ($activity->lessons as $lesson) {
            $lessonSections = $lesson->sections->filter(fn ($section) => $this->trackable($section));
            if ($lessonSections->isEmpty()) {
                continue;
            }
            $done = $lessonSections->filter(fn ($section) => ($sectionStates->get($section->id)?->status ?? '') === 'completed')->count();
            $lessonState = ActivityLessonProgress::firstOrNew([
                'user_id' => $request->user()->id,
                'child_id' => $child->id,
                'activity_lesson_id' => $lesson->id,
            ]);
            $status = $done === $lessonSections->count() ? 'completed' : ($done > 0 ? 'in_progress' : 'not_started');
            $wasCompleted = $lessonState->status === 'completed';
            if ($status !== $lessonState->status) {
                $lessonState->fill([
                    'activity_id' => $activity->id,
                    'status' => $status,
                    'completed_at' => $status === 'completed' ? now() : null,
                ])->save();
                if ($status === 'completed' && ! $wasCompleted) {
                    $this->recordEvent($request, $child, $activity, null, 'completed', 'sections_completed', $lesson);
                }
            }
        }

        $lessonIds = $activity->lessons->pluck('id');
        $lessonStates = $lessonIds->isNotEmpty()
            ? ActivityLessonProgress::where('user_id', $request->user()->id)->where('child_id', $child->id)->whereIn('activity_lesson_id', $lessonIds)->get()->keyBy('activity_lesson_id')
            : collect();
        $emptyLessonCount = $activity->lessons->filter(fn ($lesson) => $lesson->sections->filter(fn ($section) => $this->trackable($section))->isEmpty())->count();
        $completedEmptyLessons = $activity->lessons->filter(fn ($lesson) => $lesson->sections->filter(fn ($section) => $this->trackable($section))->isEmpty() && ($lessonStates->get($lesson->id)?->status ?? '') === 'completed')->count();
        $total = $sections->count() + $emptyLessonCount;
        $completed = $completedSections + $completedEmptyLessons;
        $percent = $total ? (int) round(($completed / $total) * 100) : 0;

        ActivityProgress::updateOrCreate(
            ['user_id' => $request->user()->id, 'child_id' => $child->id, 'activity_id' => $activity->id],
            [
                'status' => $percent === 100 && $total > 0 ? 'completed' : ($percent > 0 ? 'in_progress' : 'not_started'),
                'progress_percent' => $percent,
                'last_practiced_on' => today(),
            ]
        );
        $this->syncEnrollment($request, $child, $activity, $percent);
    }

    private function syncEnrollment(Request $request, Child $child, Activity $activity, int $percent): void
    {
        $enrollment = ActivityEnrollment::where('user_id', $request->user()->id)->where('activity_id', $activity->id)->first();
        if (! $enrollment || ($enrollment->child_id && (int) $enrollment->child_id !== (int) $child->id)) {
            return;
        }

        $enrollment->fill([
            'child_id' => $enrollment->child_id ?? $child->id,
            'progress_percent' => $percent,
            'status' => $percent >= 100 ? 'completed' : ($enrollment->started_at ? 'in_progress' : 'enrolled'),
            'completed_at' => $percent >= 100 ? ($enrollment->completed_at ?? now()) : null,
        ])->save();
    }

    private function saveEnrollmentPosition(Request $request, Child $child, Activity $activity, ActivitySection $section): void
    {
        $enrollment = ActivityEnrollment::where('user_id', $request->user()->id)->where('activity_id', $activity->id)->first();
        if (! $enrollment || ($enrollment->child_id && (int) $enrollment->child_id !== (int) $child->id)) {
            return;
        }

        $enrollment->fill([
            'child_id' => $enrollment->child_id ?? $child->id,
            'last_lesson_id' => $section->lesson_id,
            'last_section_id' => $section->id,
        ])->save();
    }

    private function recordEvent(Request $request, Child $child, Activity $activity, ?ActivitySection $section, string $status, ?string $method = null, ?ActivityLesson $lesson = null): void
    {
        ActivityProgressEvent::create([
            'user_id' => $request->user()->id,
            'child_id' => $child->id,
            'child_name_snapshot' => $child->name,
            'activity_id' => $activity->id,
            'activity_title_snapshot' => $activity->title,
            'activity_section_id' => $section?->id,
            'section_title_snapshot' => $section?->title ?: ($lesson ? 'Lesson completed' : null),
            'lesson_title_snapshot' => $lesson?->title,
            'status' => $status,
            'completion_method' => $method,
            'completed_at' => $status === 'completed' ? now() : null,
        ]);
    }

    private function ownedChild(Request $request, int $childId): Child
    {
        abort_if($childId < 1, 422, 'Select a child first.');
        return $request->user()->children()->findOrFail($childId);
    }

    private function requireTracking(Request $request, Activity $activity, Child $child): void
    {
        abort_unless(ActivityTrackingPreference::where('user_id', $request->user()->id)
            ->where('child_id', $child->id)
            ->where('activity_id', $activity->id)
            ->where('is_tracking', true)
            ->exists(), 409, 'Choose to track this activity for the selected child first.');
    }

    private function trackable(ActivitySection $section): bool
    {
        return true;
    }

    private function sectionsFor(Activity $activity)
    {
        $activitySections = $activity->sections->whereNull('lesson_id');
        return $activitySections->concat($activity->lessons->flatMap->sections)->unique('id')->values();
    }
}
