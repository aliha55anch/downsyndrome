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
        if ($status === 'completed' && in_array($section->content_type, ['video', 'youtube'], true)) {
            $watchedPercent = ActivitySectionProgress::where('user_id', $request->user()->id)
                ->where('child_id', $child->id)->where('activity_section_id', $section->id)->value('watched_percent') ?? 0;
            abort_unless($watchedPercent >= 75, 409, 'Watch at least 75% of this video before completing the section.');
        }

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
            'method' => ['required', 'in:content_viewed,video_progress'],
            'watched_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);
        if ($data['method'] === 'video_progress') {
            abort_unless(in_array($section->content_type, ['video', 'youtube'], true), 422);
        }
        if ($data['method'] === 'video_progress') {
            abort_unless(isset($data['watched_percent']), 422);
        }
        $child = $this->ownedChild($request, (int) $data['child_id']);
        $this->requireTracking($request, $activity, $child);

        DB::transaction(function () use ($request, $activity, $section, $child, $data): void {
            if ($data['method'] === 'video_progress') {
                $progress = ActivitySectionProgress::firstOrNew([
                    'user_id' => $request->user()->id,
                    'child_id' => $child->id,
                    'activity_section_id' => $section->id,
                ]);
                $progress->fill([
                    'activity_id' => $activity->id,
                    'section_title_snapshot' => $section->title ?: 'Activity video',
                    'watched_percent' => max((int) $progress->watched_percent, (int) $data['watched_percent']),
                ])->save();
                if ($progress->watched_percent >= 75) {
                    $this->saveSectionCompletion($request, $child, $activity, $section, 'video_threshold');
                }
            } else {
                $this->saveSectionCompletion($request, $child, $activity, $section, $data['method']);
            }
            $this->recalculate($request, $child, $activity);
        });

        $watchedPercent = ActivitySectionProgress::where('user_id', $request->user()->id)
            ->where('child_id', $child->id)->where('activity_section_id', $section->id)->value('watched_percent') ?? 0;
        return response()->json(['ok' => true, 'status' => $watchedPercent >= 75 ? 'completed' : 'in_progress', 'watched_percent' => (int) $watchedPercent, 'threshold' => 75]);
    }

    public function trackActivityVideo(Request $request, Activity $activity): JsonResponse
    {
        abort_unless($activity->isPublished() && ($activity->video_path || $activity->youtube_url), 404);
        $data = $request->validate([
            'child_id' => ['required', 'integer'],
            'watched_percent' => ['required', 'integer', 'min:0', 'max:100'],
        ]);
        $child = $this->ownedChild($request, (int) $data['child_id']);
        $this->requireTracking($request, $activity, $child);

        $progress = ActivityProgress::firstOrNew([
            'user_id' => $request->user()->id,
            'child_id' => $child->id,
            'activity_id' => $activity->id,
        ]);
        // Keep a watched-to-100 video in progress until the parent presses Complete.
        $progress->video_progress_percent = max((int) $progress->video_progress_percent, min(99, (int) $data['watched_percent']));
        $progress->last_practiced_on = today();
        $progress->save();

        $this->recalculate($request, $child, $activity);
        $progress->refresh();
        $overallPercent = (int) (ActivityProgress::where('user_id', $request->user()->id)
            ->where('child_id', $child->id)->where('activity_id', $activity->id)->value('progress_percent') ?? 0);

        return response()->json([
            'ok' => true,
            'watched_percent' => (int) $progress->video_progress_percent,
            'completion_unlocked' => $progress->video_progress_percent >= 75,
            'progress_percent' => $overallPercent,
            'threshold' => 75,
        ]);
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
        $videoSectionIds = $lesson->sections->filter(fn ($section) => in_array($section->content_type, ['video', 'youtube'], true))->pluck('id');
        if ($videoSectionIds->isNotEmpty()) {
            $watchedVideoIds = ActivitySectionProgress::where('user_id', $request->user()->id)
                ->where('child_id', $child->id)->whereIn('activity_section_id', $videoSectionIds)
                ->where('watched_percent', '>=', 75)->pluck('activity_section_id')->all();
            abort_unless($videoSectionIds->diff($watchedVideoIds)->isEmpty(), 409, 'Watch at least 75% of each lesson video before completing this lesson.');
        }

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
        abort_unless($activity->isPublished(), 404);
        $child = $this->ownedChild($request, $request->integer('child_id'));
        $this->requireTracking($request, $activity, $child);
        if ($activity->video_path || $activity->youtube_url) {
            $watchedPercent = ActivityProgress::where('user_id', $request->user()->id)
                ->where('child_id', $child->id)->where('activity_id', $activity->id)->value('video_progress_percent') ?? 0;
            abort_unless($watchedPercent >= 75, 409, 'Watch at least 75% of this activity video before completing the activity.');
        }
        $activity->loadMissing(['sections' => fn ($query) => $query->whereNull('lesson_id'), 'lessons.sections']);
        $videoSectionIds = $this->sectionsFor($activity)
            ->filter(fn ($section) => in_array($section->content_type, ['video', 'youtube'], true))->pluck('id');
        if ($videoSectionIds->isNotEmpty()) {
            $watchedVideoIds = ActivitySectionProgress::where('user_id', $request->user()->id)
                ->where('child_id', $child->id)->whereIn('activity_section_id', $videoSectionIds)
                ->where('watched_percent', '>=', 75)->pluck('activity_section_id')->all();
            abort_unless($videoSectionIds->diff($watchedVideoIds)->isEmpty(), 409, 'Watch at least 75% of each lesson video before completing the activity.');
        }
        DB::transaction(function () use ($request, $child, $activity): void {
            $previousStatus = ActivityProgress::where('user_id', $request->user()->id)
                ->where('child_id', $child->id)->where('activity_id', $activity->id)->value('status');
            $sections = $this->sectionsFor($activity)->filter(fn ($section) => $this->trackable($section));

            foreach ($sections as $section) {
                $alreadyCompleted = ActivitySectionProgress::where('user_id', $request->user()->id)
                    ->where('child_id', $child->id)->where('activity_section_id', $section->id)
                    ->where('status', 'completed')->exists();
                if (! $alreadyCompleted) {
                    $this->saveSectionCompletion($request, $child, $activity, $section, 'manual');
                }
            }

            $videoSectionIds = $sections->filter(fn ($section) => in_array($section->content_type, ['video', 'youtube'], true))->pluck('id');
            if ($videoSectionIds->isNotEmpty()) {
                ActivitySectionProgress::where('user_id', $request->user()->id)
                    ->where('child_id', $child->id)->whereIn('activity_section_id', $videoSectionIds)
                    ->update(['watched_percent' => 100]);
            }

            foreach ($activity->lessons as $lesson) {
                if ($lesson->sections->filter(fn ($section) => $this->trackable($section))->isNotEmpty()) {
                    continue;
                }
                $lessonState = ActivityLessonProgress::firstOrNew([
                    'user_id' => $request->user()->id,
                    'child_id' => $child->id,
                    'activity_lesson_id' => $lesson->id,
                ]);
                $wasComplete = $lessonState->status === 'completed';
                $lessonState->fill(['activity_id' => $activity->id, 'status' => 'completed', 'completed_at' => $lessonState->completed_at ?? now()])->save();
                if (! $wasComplete) {
                    $this->recordEvent($request, $child, $activity, null, 'completed', 'manual', $lesson);
                }
            }

            if ($sections->isNotEmpty() || $activity->lessons->isNotEmpty()) {
                $this->recalculate($request, $child, $activity);
            } else {
                ActivityProgress::updateOrCreate(
                    ['user_id' => $request->user()->id, 'child_id' => $child->id, 'activity_id' => $activity->id],
                    ['status' => 'completed', 'progress_percent' => 100, 'last_practiced_on' => today()]
                );
                $this->syncEnrollment($request, $child, $activity, 100);
            }

            if ($activity->video_path || $activity->youtube_url) {
                ActivityProgress::where('user_id', $request->user()->id)
                    ->where('child_id', $child->id)->where('activity_id', $activity->id)
                    ->update(['video_progress_percent' => 100]);
                $this->recalculate($request, $child, $activity);
            }

            if ($previousStatus !== 'completed') {
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
        $sectionProgressPoints = $sections->sum(function (ActivitySection $section) use ($states): int {
            $state = $states->get($section->id);
            if (in_array($section->content_type, ['video', 'youtube'], true)) {
                return min(100, (int) ($state?->watched_percent ?? 0));
            }
            return ($state?->status ?? '') === 'completed' ? 100 : 0;
        });
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
        $progress = $child ? ActivityProgress::where('user_id', $child->user_id)->where('child_id', $child->id)->where('activity_id', $activity->id)->first() : null;
        $hasActivityVideo = (bool) ($activity->video_path || $activity->youtube_url);
        $activityVideoPercent = (int) ($progress?->video_progress_percent ?? 0);
        $totalUnits = $total + ($hasActivityVideo ? 1 : 0);
        $percent = $totalUnits
            ? (int) floor(($sectionProgressPoints + ($emptyLessonsCompleted * 100) + ($hasActivityVideo ? $activityVideoPercent : 0)) / $totalUnits)
            : (($progress?->status ?? null) === 'completed' ? 100 : 0);

        return [
            'activity' => $activity,
            'sections' => $sections,
            'states' => $states,
            'lessons' => $lessons,
            'completed_lessons' => $lessons->where('status', 'completed')->count(),
            'total_lessons' => $lessons->count(),
            'videos_completed' => $sections->filter(fn ($section) => in_array($section->content_type, ['video', 'youtube'], true) && (int) ($states->get($section->id)?->watched_percent ?? 0) >= 75)->count() + ($hasActivityVideo && $activityVideoPercent >= 75 ? 1 : 0),
            'video_progress_percent' => $activityVideoPercent,
            'completed' => $completedUnits,
            'total' => $total,
            'percent' => $percent,
            'status' => $percent === 100 && ($totalUnits > 0 || ($progress?->status ?? null) === 'completed') ? 'completed' : ($percent > 0 ? 'in_progress' : ($progress?->status ?? 'not_started')),
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
        $sectionProgressPoints = $sections->sum(function (ActivitySection $section) use ($sectionStates): int {
            $state = $sectionStates->get($section->id);
            if (in_array($section->content_type, ['video', 'youtube'], true)) {
                return min(100, (int) ($state?->watched_percent ?? 0));
            }
            return ($state?->status ?? '') === 'completed' ? 100 : 0;
        });

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
        $activityProgress = ActivityProgress::firstOrNew([
            'user_id' => $request->user()->id,
            'child_id' => $child->id,
            'activity_id' => $activity->id,
        ]);
        $hasActivityVideo = (bool) ($activity->video_path || $activity->youtube_url);
        $total = $sections->count() + $emptyLessonCount + ($hasActivityVideo ? 1 : 0);
        $completed = $completedSections + $completedEmptyLessons;
        $percent = $total ? (int) floor(($sectionProgressPoints + ($completedEmptyLessons * 100) + ($hasActivityVideo ? (int) $activityProgress->video_progress_percent : 0)) / $total) : 0;

        $activityProgress->fill([
            'status' => $percent === 100 && $total > 0 ? 'completed' : ($percent > 0 ? 'in_progress' : 'not_started'),
            'progress_percent' => $percent,
            'last_practiced_on' => today(),
        ])->save();
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
