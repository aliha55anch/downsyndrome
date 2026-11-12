<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ActivityLesson;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ActivityLessonController extends Controller
{
    public function index(Activity $activity): View { return view('admin.activities.lessons.index', ['activity' => $activity->load('lessons.sections')]); }
    public function create(Activity $activity): View { return view('admin.activities.lessons.form', ['activity' => $activity, 'lesson' => new ActivityLesson(['submission_key' => (string) \Illuminate\Support\Str::uuid()])]); }

    public function store(Request $request, Activity $activity): RedirectResponse
    {
        $lesson = $this->save($request, $activity, new ActivityLesson);
        return redirect()->route('admin.activities.lessons.index', $activity)->with('status', 'Lesson created successfully.');
    }

    public function edit(Activity $activity, ActivityLesson $lesson): View { $this->owned($activity, $lesson); return view('admin.activities.lessons.form', compact('activity', 'lesson')); }

    public function update(Request $request, Activity $activity, ActivityLesson $lesson): RedirectResponse
    {
        $this->owned($activity, $lesson); $this->save($request, $activity, $lesson);
        return redirect()->route('admin.activities.lessons.edit', [$activity, $lesson])->with('status', 'Lesson details updated.');
    }

    public function destroy(Activity $activity, ActivityLesson $lesson): RedirectResponse
    {
        $this->owned($activity, $lesson); $lesson->delete(); $this->renumber($activity);
        return back()->with('status', 'Lesson deleted.');
    }

    public function reorder(Request $request, Activity $activity): RedirectResponse
    {
        $ids = $request->validate(['order' => ['required','array'], 'order.*' => ['integer']])['order'];
        $allowed = $activity->lessons()->whereIn('id', $ids)->pluck('id')->map(fn ($id) => (int) $id)->all();
        abort_unless(count($allowed) === count($ids) && count(array_unique($ids)) === count($ids), 422);
        foreach (array_values($ids) as $position => $id) $activity->lessons()->whereKey($id)->update(['position' => $position + 1]);
        return back()->with('status', 'Lesson order saved.');
    }

    private function save(Request $request, Activity $activity, ActivityLesson $lesson): ActivityLesson
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'position' => ['required', 'integer', 'min:1'],
            'submission_key' => [$lesson->exists ? 'nullable' : 'required', 'uuid'],
        ]);
        return DB::transaction(function () use ($activity, $lesson, $data): ActivityLesson {
            Activity::query()->whereKey($activity->id)->lockForUpdate()->firstOrFail();

            if (! $lesson->exists && ($existing = $activity->lessons()->where('submission_key', $data['submission_key'])->first())) {
                return $existing;
            }

            $position = max(1, min((int) $data['position'], $activity->lessons()->count() + 1));
            if (! $lesson->exists) {
                $activity->lessons()->where('position', '>=', $position)->increment('position');
            } elseif ((int) $lesson->position !== $position) {
                if ((int) $lesson->position > $position) $activity->lessons()->where('position', '>=', $position)->where('position', '<', $lesson->position)->increment('position');
                else $activity->lessons()->where('position', '>', $lesson->position)->where('position', '<=', $position)->decrement('position');
            }
            $lesson->fill(['activity_id' => $activity->id, 'title' => $data['title'], 'position' => $position]);
            if (! $lesson->exists) {
                $lesson->submission_key = $data['submission_key'];
            }
            $lesson->save();
            $this->renumber($activity);
            return $lesson;
        });
    }

    private function renumber(Activity $activity): void { $activity->lessons()->orderBy('position')->orderBy('id')->get()->each(fn ($lesson, $i) => $lesson->updateQuietly(['position' => $i + 1])); }
    private function owned(Activity $activity, ActivityLesson $lesson): void { abort_unless($lesson->activity_id === $activity->id, 404); }
}
