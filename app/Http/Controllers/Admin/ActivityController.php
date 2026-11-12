<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ActivityController extends Controller
{
    public function index(Request $request): View
    {
        $activities = Activity::query()
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where(function ($searchQuery) use ($search): void {
                    $searchQuery->where('title', 'like', "%{$search}%")
                        ->orWhere('short_description', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('age_group'), fn ($query) => $query->where('age_group', $request->string('age_group')->toString()))
            ->when($request->filled('development_area'), fn ($query) => $query->where('development_area', $request->string('development_area')->toString()))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.activities.index', [
            'activities' => $activities,
            'ageGroups' => Activity::AGE_GROUPS,
            'developmentAreas' => Activity::DEVELOPMENT_AREAS,
            'statuses' => Activity::STATUSES,
        ]);
    }

    public function create(): View
    {
        return view('admin.activities.basic', $this->formData(new Activity));
    }

    public function store(Request $request): RedirectResponse
    {
        $activity = new Activity;
        $this->saveActivity($request, $activity);

        return redirect()->route('admin.activities.lessons.index', $activity)->with('status', 'Basic activity created. Add lessons when you are ready.');
    }

    public function edit(Activity $activity): View
    {
        $activity->load(['lessons' => fn ($query) => $query->with('sections')]);
        return view('admin.activities.form', $this->formData($activity));
    }

    public function preview(Activity $activity): View
    {
        $activity->load(['sections' => fn ($query) => $query->whereNull('lesson_id'), 'lessons.sections']);
        $allSections = $activity->sections->concat($activity->lessons->flatMap->sections);
        return view('activities.show', ['activity' => $activity, 'children' => collect(), 'selectedChild' => null, 'isTracking' => false, 'sectionStates' => collect(), 'lessonStates' => collect(), 'allSections' => $allSections, 'selectedLesson' => $activity->lessons->first(), 'selectedSection' => null, 'enrollment' => null, 'isPreview' => true]);
    }

    public function update(Request $request, Activity $activity): RedirectResponse
    {
        $this->saveBasicActivity($request, $activity);

        return redirect()->route('admin.activities.edit', $activity)->with('status', 'Basic activity information updated successfully.');
    }

    public function publication(Request $request, Activity $activity): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(Activity::STATUSES)]]);
        $activity->status = $data['status'];
        $activity->published_at = $data['status'] === Activity::STATUS_PUBLISHED
            ? ($activity->published_at ?? now())
            : null;
        $activity->save();

        $message = $activity->isPublished() ? 'Activity published successfully.' : 'Activity moved back to draft.';
        return back()->with('status', $message);
    }

    public function destroy(Activity $activity): RedirectResponse
    {
        foreach ($activity->lessons()->with('sections')->get()->flatMap->sections as $section) {
            if ($section->icon_path) Storage::disk('local')->delete($section->icon_path);
            if ($section->content['media_path'] ?? null) Storage::disk('local')->delete($section->content['media_path']);
        }
        foreach ($activity->sections()->whereNull('lesson_id')->get() as $section) {
            if ($section->icon_path) Storage::disk('local')->delete($section->icon_path);
            if ($section->content['media_path'] ?? null) Storage::disk('local')->delete($section->content['media_path']);
        }
        foreach ([$activity->featured_image_path, $activity->pptx_path, $activity->pdf_path] as $path) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
        }

        $activity->delete();

        return back()->with('status', 'Activity deleted successfully.');
    }

    public function icon(Activity $activity): BinaryFileResponse
    {
        abort_unless($activity->icon_path, 404);
        $path = Storage::disk('local')->path($activity->icon_path);
        abort_unless(is_file($path), 404);
        return response()->file($path);
    }

    private function formData(Activity $activity): array
    {
        return [
            'activity' => $activity,
            'ageGroups' => Activity::AGE_GROUPS,
            'developmentAreas' => Activity::DEVELOPMENT_AREAS,
            'statuses' => Activity::STATUSES,
        ];
    }

    private function saveActivity(Request $request, Activity $activity): void
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'activity_icon' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:500'],
            'remove_activity_icon' => ['nullable', 'boolean'],
            'short_description' => ['required', 'string', 'max:500'],
            'detailed_description' => ['nullable', 'string', 'max:10000'],
            'age_group' => ['required', 'in:'.implode(',', Activity::AGE_GROUPS)],
            'min_age' => ['nullable', 'integer', 'min:0', 'max:100'],
            'max_age' => ['nullable', 'integer', 'min:0', 'max:100', 'gte:min_age'],
            'development_area' => ['required', 'in:'.implode(',', Activity::DEVELOPMENT_AREAS)],
            'difficulty' => ['nullable', 'string', 'max:50'],
            'estimated_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'materials' => ['nullable', 'string', 'max:10000'],
            'learning_objective' => ['nullable', 'string', 'max:5000'],
            'instructions' => ['nullable', 'string', 'max:20000'],
            'parent_tips' => ['nullable', 'string', 'max:10000'],
            'disclaimer' => ['nullable', 'string', 'max:5000'],
            'content_source' => ['nullable', 'string', 'max:255'],
            'reviewer_name' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:'.implode(',', Activity::STATUSES)],
            'save_action' => ['nullable', 'in:draft,published'],
            'youtube_url' => ['nullable', 'url', 'max:2048'],
            'content_blocks' => ['nullable', 'json', 'max:50000'],
            'slides_json' => ['nullable', 'json', 'max:50000'],
            'featured_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'pptx' => ['nullable', 'file', 'mimes:pptx', 'max:20480'],
            'pdf' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        if (! empty($validated['youtube_url'])) {
            $validated['youtube_url'] = $this->youtubeEmbedUrl($validated['youtube_url']);
        }

        $blocks = $this->decodeBlocks($validated['content_blocks'] ?? null);
        $slides = $this->decodeSlides($validated['slides_json'] ?? null);
        if ($slides !== []) {
            $blocks[] = ['type' => 'presentation', 'slides' => $slides];
        }

        if (! empty($validated['save_action'])) {
            $validated['status'] = $validated['save_action'];
        }

        if (($validated['min_age'] ?? null) !== null xor ($validated['max_age'] ?? null) !== null) {
            throw \Illuminate\Validation\ValidationException::withMessages(['min_age' => 'Enter both minimum and maximum ages.']);
        }

        $removeActivityIcon = (bool) ($validated['remove_activity_icon'] ?? false);
        unset($validated['featured_image'], $validated['pptx'], $validated['pdf'], $validated['activity_icon'], $validated['remove_activity_icon'], $validated['content_blocks'], $validated['slides_json'], $validated['save_action']);
        $validated['content_blocks'] = $blocks;
        $validated['published_at'] = $validated['status'] === Activity::STATUS_PUBLISHED
            ? ($activity->published_at ?? now())
            : null;

        foreach (['featured_image' => 'featured_image_path', 'pptx' => 'pptx_path', 'pdf' => 'pdf_path'] as $input => $column) {
            if ($request->hasFile($input)) {
                if ($activity->{$column}) {
                    Storage::disk('local')->delete($activity->{$column});
                }
                $validated[$column] = $request->file($input)->store('activities', 'local');
            }
        }

        if ($request->hasFile('activity_icon')) {
            if ($activity->icon_path) Storage::disk('local')->delete($activity->icon_path);
            $validated['icon_path'] = $request->file('activity_icon')->store('activity-icons', 'local');
        } elseif ($removeActivityIcon && $activity->icon_path) {
            Storage::disk('local')->delete($activity->icon_path);
            $validated['icon_path'] = null;
        }

        $activity->fill($validated)->save();
    }

    private function saveBasicActivity(Request $request, Activity $activity): void
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'activity_icon' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:500'],
            'remove_activity_icon' => ['nullable', 'boolean'],
            'age_group' => ['required', 'in:'.implode(',', Activity::AGE_GROUPS)],
            'min_age' => ['required', 'integer', 'min:0', 'max:100'],
            'max_age' => ['required', 'integer', 'min:0', 'max:100', 'gte:min_age'],
            'development_area' => ['required', 'in:'.implode(',', Activity::DEVELOPMENT_AREAS)],
            'short_description' => ['required', 'string', 'max:500'],
        ]);

        $removeIcon = (bool) ($validated['remove_activity_icon'] ?? false);
        unset($validated['activity_icon'], $validated['remove_activity_icon']);
        $activity->fill($validated);

        if ($request->hasFile('activity_icon')) {
            if ($activity->icon_path) Storage::disk('local')->delete($activity->icon_path);
            $activity->icon_path = $request->file('activity_icon')->store('activity-icons', 'local');
        } elseif ($removeIcon && $activity->icon_path) {
            Storage::disk('local')->delete($activity->icon_path);
            $activity->icon_path = null;
        }

        $activity->save();
    }

    private function decodeBlocks(?string $json): array
    {
        if (! $json) {
            return [];
        }

        $blocks = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        abort_unless(is_array($blocks), 422, 'Content blocks must be a JSON array.');

        return collect($blocks)->map(function ($block): array {
            abort_unless(is_array($block) && isset($block['type']), 422, 'Each content block needs a type.');
            abort_unless(in_array($block['type'], ['heading', 'paragraph', 'image', 'steps', 'materials', 'tips', 'video', 'matching', 'routine'], true), 422, 'Unsupported content block type.');

            if ($block['type'] === 'video') {
                $block['url'] = $this->youtubeEmbedUrl((string) ($block['url'] ?? ''));
            }

            if ($block['type'] === 'image') {
                $imageUrl = (string) ($block['url'] ?? '');
                abort_unless(filter_var($imageUrl, FILTER_VALIDATE_URL) && in_array(parse_url($imageUrl, PHP_URL_SCHEME), ['http', 'https'], true), 422, 'Image blocks need a valid HTTP or HTTPS URL.');
                $block['url'] = $imageUrl;
            }

            return collect($block)->only(['type', 'text', 'items', 'url', 'title'])->all();
        })->values()->all();
    }

    private function decodeSlides(?string $json): array
    {
        if (! $json) {
            return [];
        }

        $slides = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        abort_unless(is_array($slides), 422, 'Presentation slides must be a JSON array.');

        return collect($slides)->map(function ($slide): array {
            abort_unless(is_array($slide), 422, 'Each slide must be an object.');

            $slide = collect($slide)->only(['title', 'text', 'image_url', 'steps'])->all();
            if (! empty($slide['image_url'])) {
                abort_unless(filter_var($slide['image_url'], FILTER_VALIDATE_URL) && in_array(parse_url($slide['image_url'], PHP_URL_SCHEME), ['http', 'https'], true), 422, 'Slide images need valid HTTP or HTTPS URLs.');
            }

            return $slide;
        })->values()->all();
    }

    private function youtubeEmbedUrl(string $url): string
    {
        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');
        $videoId = null;

        if ($host === 'youtu.be') {
            $videoId = trim($parts['path'] ?? '', '/');
        } elseif (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com'], true)) {
            parse_str($parts['query'] ?? '', $query);
            $videoId = $query['v'] ?? null;
            if (! $videoId && str_starts_with($parts['path'] ?? '', '/embed/')) {
                $videoId = basename($parts['path']);
            }
        }

        abort_unless(is_string($videoId) && preg_match('/^[A-Za-z0-9_-]{11}$/', $videoId), 422, 'Enter a valid YouTube video link.');

        return 'https://www.youtube-nocookie.com/embed/'.$videoId;
    }
}
