<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ActivityLesson;
use App\Models\ActivitySection;
use App\Support\RichTextSanitizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ActivitySectionController extends Controller
{
    public const TYPES = ['heading', 'text', 'image', 'video', 'youtube', 'powerpoint', 'pdf', 'word'];

    private const FONT_FAMILIES = ['Arial', 'Georgia', 'Tahoma', 'Times New Roman', 'Trebuchet MS', 'Verdana'];

    public function index(Activity $activity): View
    {
        return view('admin.activities.sections.index', ['activity' => $activity->load('sections')]);
    }

    public function create(Activity $activity): View
    {
        return $this->form($activity, null, new ActivitySection);
    }

    public function edit(Activity $activity, ActivitySection $section): View
    {
        abort_unless($section->activity_id === $activity->id && ! $section->lesson_id, 404);

        return $this->form($activity, null, $section);
    }

    public function store(Request $request, Activity $activity): RedirectResponse
    {
        $this->save($request, $activity, new ActivitySection);

        return back()->with('status', 'Activity section added.');
    }

    public function update(Request $request, Activity $activity, ActivitySection $section): RedirectResponse
    {
        abort_unless($section->activity_id === $activity->id && ! $section->lesson_id, 404);
        $this->save($request, $activity, $section);

        return back()->with('status', 'Activity section updated.');
    }

    public function destroy(Activity $activity, ActivitySection $section): RedirectResponse
    {
        abort_unless($section->activity_id === $activity->id, 404);
        $lessonId = $section->lesson_id;
        $this->remove($section);
        $this->renumber($activity, $lessonId ? ActivityLesson::find($lessonId) : null);

        return back()->with('status', 'Activity section removed.');
    }

    public function lessonIndex(Activity $activity, ActivityLesson $lesson): View
    {
        $this->ownedLesson($activity, $lesson);

        return view('admin.activities.sections.index', ['activity' => $activity, 'lesson' => $lesson->load('sections')]);
    }

    public function lessonCreate(Activity $activity, ActivityLesson $lesson): View
    {
        $this->ownedLesson($activity, $lesson);
        $section = new ActivitySection;
        $section->position = request()->integer('position', $lesson->sections()->count() + 1);

        return $this->form($activity, $lesson, $section);
    }

    public function lessonStore(Request $request, Activity $activity, ActivityLesson $lesson): RedirectResponse
    {
        $this->ownedLesson($activity, $lesson);
        $this->save($request, $activity, new ActivitySection, $lesson);

        return redirect()->route('admin.activities.lessons.edit', [$activity, $lesson])->with('status', 'Lesson section added.');
    }

    public function lessonEdit(Activity $activity, ActivityLesson $lesson, ActivitySection $section): View
    {
        $this->ownedLesson($activity, $lesson);
        abort_unless($section->activity_id === $activity->id && $section->lesson_id === $lesson->id, 404);

        return $this->form($activity, $lesson, $section);
    }

    public function lessonUpdate(Request $request, Activity $activity, ActivityLesson $lesson, ActivitySection $section): RedirectResponse
    {
        $this->ownedLesson($activity, $lesson);
        abort_unless($section->activity_id === $activity->id && $section->lesson_id === $lesson->id, 404);
        $this->save($request, $activity, $section, $lesson);

        return redirect()->route('admin.activities.lessons.edit', [$activity, $lesson])->with('status', 'Lesson section updated.');
    }

    public function lessonDestroy(Activity $activity, ActivityLesson $lesson, ActivitySection $section): RedirectResponse
    {
        $this->ownedLesson($activity, $lesson);
        abort_unless($section->activity_id === $activity->id && $section->lesson_id === $lesson->id, 404);
        $this->remove($section);
        $this->renumber($activity, $lesson);

        return redirect()->route('admin.activities.lessons.edit', [$activity, $lesson])->with('status', 'Lesson section removed.');
    }

    public function lessonDuplicate(Activity $activity, ActivityLesson $lesson, ActivitySection $section): RedirectResponse
    {
        $this->ownedLesson($activity, $lesson);
        abort_unless($section->activity_id === $activity->id && $section->lesson_id === $lesson->id, 404);

        DB::transaction(function () use ($lesson, $section): void {
            $lesson->sections()->where('position', '>', $section->position)->increment('position');
            $copy = $section->replicate();
            $copy->position = $section->position + 1;
            $copy->save();
        });
        $this->renumber($activity, $lesson);

        return redirect()->route('admin.activities.lessons.edit', [$activity, $lesson])->with('status', 'Section duplicated.');
    }

    public function lessonReorder(Request $request, Activity $activity, ActivityLesson $lesson): RedirectResponse
    {
        $this->ownedLesson($activity, $lesson);
        $ids = $request->validate(['order' => ['required', 'array'], 'order.*' => ['integer']])['order'];
        $allowed = $lesson->sections()->whereIn('id', $ids)->pluck('id')->map(fn ($id) => (int) $id)->all();
        abort_unless(count($allowed) === count($ids) && count(array_unique($ids)) === count($ids), 422);

        DB::transaction(function () use ($lesson, $ids): void {
            foreach (array_values($ids) as $position => $id) {
                $lesson->sections()->whereKey($id)->update(['position' => $position + 1]);
            }
        });

        return redirect()->route('admin.activities.lessons.edit', [$activity, $lesson])->with('status', 'Section order saved.');
    }

    private function form(Activity $activity, ?ActivityLesson $lesson, ActivitySection $section): View
    {
        return view('admin.activities.sections.form', compact('activity', 'lesson', 'section') + ['types' => self::TYPES]);
    }

    private function save(Request $request, Activity $activity, ActivitySection $section, ?ActivityLesson $lesson = null): void
    {
        $type = $request->input('content_type');
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'content_type' => ['required', 'in:'.implode(',', self::TYPES)],
            'position' => ['required', 'integer', 'min:1'],
            'heading_text' => [$type === 'heading' ? 'required' : 'nullable', 'string', 'max:255'],
            'font_family' => [$type === 'heading' ? 'required' : 'nullable', 'in:'.implode(',', self::FONT_FAMILIES)],
            'font_size' => [$type === 'heading' ? 'required' : 'nullable', 'integer', 'between:20,48'],
            'font_color' => [$type === 'heading' ? 'required' : 'nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'text_align' => [$type === 'heading' ? 'required' : 'nullable', 'in:left,center,right'],
            'bold' => ['nullable', 'boolean'],
            'italic' => ['nullable', 'boolean'],
            'underline' => ['nullable', 'boolean'],
            'text_html' => [$type === 'text' ? 'required' : 'nullable', 'string', 'max:30000'],
            'url' => [$type === 'youtube' ? 'required' : 'nullable', 'url', 'max:2048'],
            'media' => ['nullable', 'file', 'max:20480'],
            'remove_media' => ['nullable', 'boolean'],
        ]);

        $existingPath = $section->content['media_path'] ?? null;
        $oldType = $section->content_type;
        $retainedPath = $oldType === $type ? $existingPath : null;
        if (in_array($type, ['image', 'video', 'powerpoint', 'pdf', 'word'], true) && ! $request->hasFile('media') && ! $retainedPath && ! $request->boolean('remove_media')) {
            throw ValidationException::withMessages(['media' => 'Choose a file for this section.']);
        }

        if ($request->hasFile('media')) {
            $file = $request->file('media');

            if ($type === 'image') {
                $request->validate(['media' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:600']]);
            } elseif ($type === 'video') {
                $request->validate(['media' => ['required', 'file', 'mimetypes:video/mp4,video/webm,video/quicktime', 'mimes:mp4,webm,mov', 'max:35840']]);
            } elseif ($type === 'powerpoint') {
                $request->validate(['media' => ['required', 'file', 'mimes:pptx', 'extensions:pptx', 'max:20480']]);
                if (! $this->isPowerPointPackage($file->getRealPath())) {
                    throw ValidationException::withMessages(['media' => 'This file is not a valid PowerPoint presentation. Choose a real .pptx file.']);
                }
            } elseif ($type === 'pdf') {
                $request->validate(['media' => ['required', 'file', 'mimes:pdf', 'max:20480']]);
            } elseif ($type === 'word') {
                $extension = strtolower($file->getClientOriginalExtension());
                $request->validate(['media' => ['required', 'file', 'extensions:doc,docx', 'max:20480']]);
                if (! $this->isWordDocument($file->getRealPath(), $extension)) {
                    throw ValidationException::withMessages(['media' => 'Choose a valid Word document (.doc or .docx).']);
                }
            } else {
                throw ValidationException::withMessages(['media' => 'Choose a file that matches the selected section type.']);
            }
        }

        if ($type === 'youtube') {
            $data['url'] = $this->youtubeEmbedUrl($data['url']);
        }

        $html = $type === 'text' ? RichTextSanitizer::sanitize($data['text_html'] ?? '') : '';
        $heading = $type === 'heading' ? [
            'text' => trim($data['heading_text']),
            'font_family' => $data['font_family'],
            'font_size' => (int) $data['font_size'],
            'font_color' => strtoupper($data['font_color']),
            'text_align' => $data['text_align'],
            'bold' => $request->boolean('bold'),
            'italic' => $request->boolean('italic'),
            'underline' => $request->boolean('underline'),
        ] : [];

        $data['title'] = trim($data['title'] ?? '');
        $oldPath = $existingPath;
        $storedPath = null;
        if ($request->hasFile('media')) {
            $folder = match ($type) {
                'image' => 'activity-content/images',
                'video' => 'activity-content/videos',
                'powerpoint' => 'activity-content/presentations',
                'pdf', 'word' => 'activity-content/documents',
            };
            $storedPath = $request->file('media')->store($folder, 'local');
        }

        $removeMedia = $request->boolean('remove_media');
        $mediaPath = $storedPath ?: ($removeMedia || ($section->exists && $oldType !== $type) ? null : $oldPath);
        $originalName = $storedPath
            ? Str::limit(preg_replace('/[\x00-\x1F\x7F]/u', '', basename(str_replace('\\', '/', $request->file('media')->getClientOriginalName()))) ?: 'presentation.pptx', 180, '')
            : ($removeMedia ? null : ($section->content['media_filename'] ?? null));

        try {
            DB::transaction(function () use ($activity, $lesson, $section, $data, $html, $heading, $mediaPath, $originalName, $request): void {
                Activity::query()->whereKey($activity->id)->lockForUpdate()->firstOrFail();
                $scope = $lesson ? $lesson->sections() : $activity->sections()->whereNull('lesson_id');
                $position = max(1, min((int) $data['position'], $scope->count() + 1));

                if (! $section->exists) {
                    $scope->where('position', '>=', $position)->increment('position');
                } elseif ((int) $section->position !== $position) {
                    if ((int) $section->position > $position) {
                        $scope->where('position', '>=', $position)->where('position', '<', $section->position)->increment('position');
                    } else {
                        $scope->where('position', '>', $section->position)->where('position', '<=', $position)->decrement('position');
                    }
                }

                $oldContent = $section->content ?? [];
                $content = match ($data['content_type']) {
                    'heading' => $heading,
                    'text' => ['html' => $html, 'text' => trim(strip_tags($html))],
                    'youtube' => ['url' => $data['url']],
                    'image', 'video', 'powerpoint', 'pdf', 'word' => array_filter([
                        'media_path' => $mediaPath,
                        'media_filename' => $originalName,
                        'alt' => $data['title'],
                    ], fn ($value) => $value !== null),
                };

                $section->fill([
                    'activity_id' => $activity->id,
                    'lesson_id' => $lesson?->id,
                    'title' => $data['title'],
                    'color' => $oldContent['color'] ?? '#DCEEF7',
                    'content_type' => $data['content_type'],
                    'position' => $position,
                    'content' => $content,
                ])->save();

                $this->renumber($activity, $lesson);
            });
        } catch (\Throwable $exception) {
            if ($storedPath) {
                Storage::disk('local')->delete($storedPath);
            }

            throw $exception;
        }

        if (($storedPath || $removeMedia || ($section->exists && $oldType !== $data['content_type'])) && $oldPath && $oldPath !== $mediaPath) {
            Storage::disk('local')->delete($oldPath);
        }
    }

    private function isPowerPointPackage(string $path): bool
    {
        return $this->isOoxmlPackage($path, 'ppt/presentation.xml', 'application/vnd.openxmlformats-officedocument.presentationml.presentation.main+xml', 'presentation');
    }

    private function isWordDocument(string $path, string $extension): bool
    {
        $handle = @fopen($path, 'rb');
        if (! $handle) {
            return false;
        }
        $header = fread($handle, 8);
        fclose($handle);

        if ($extension === 'doc') {
            return $header === "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1";
        }

        return $this->isOoxmlPackage($path, 'word/document.xml', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml', 'document');
    }

    private function isOoxmlPackage(string $path, string $mainPart, string $mainContentType, string $rootName): bool
    {
        $fileSize = filesize($path);
        if (! $fileSize || $fileSize < 22 || $fileSize > 20 * 1024 * 1024) {
            return false;
        }

        $handle = fopen($path, 'rb');
        if (! $handle) {
            return false;
        }

        try {
            $tailLength = min($fileSize, 65557);
            fseek($handle, $fileSize - $tailLength);
            $tail = fread($handle, $tailLength);
            $endOffset = is_string($tail) ? strrpos($tail, "PK\x05\x06") : false;
            if ($endOffset === false || strlen($tail) < $endOffset + 22) {
                return false;
            }

            $end = unpack('vdisk/vstart/ventries_disk/ventries/Vdirectory_size/Vdirectory_offset/vcomment_length', substr($tail, $endOffset + 4, 18));
            if (! $end || $end['disk'] !== 0 || $end['start'] !== 0 || $end['entries'] < 2 || $end['entries'] > 3000
                || $end['directory_offset'] + $end['directory_size'] > $fileSize || $end['directory_size'] > 8 * 1024 * 1024) {
                return false;
            }

            $entries = [];
            $compressedTotal = 0;
            $uncompressedTotal = 0;
            fseek($handle, $end['directory_offset']);

            for ($i = 0; $i < $end['entries']; $i++) {
                $header = fread($handle, 46);
                if (! is_string($header) || strlen($header) !== 46 || substr($header, 0, 4) !== "PK\x01\x02") {
                    return false;
                }

                // The central directory stores modified time and date before CRC. Keep
                // these fields in the unpack format so all later offsets stay aligned.
                $entry = unpack('vflags/vmethod/vtime/vdate/Vcrc/Vcompressed/Vuncompressed/vname_length/vextra_length/vcomment_length/vdisk/vinternal/Vexternal/Voffset', substr($header, 8, 38));
                if (! $entry || ($entry['flags'] & 1) !== 0 || ! in_array($entry['method'], [0, 8], true)) {
                    return false;
                }
                if ($entry['name_length'] < 1 || $entry['name_length'] > 4096
                    || $entry['extra_length'] > 65535 || $entry['comment_length'] > 65535
                    || $entry['offset'] + 30 > $fileSize) {
                    return false;
                }

                $name = fread($handle, $entry['name_length']);
                if (! is_string($name) || strlen($name) !== $entry['name_length']) {
                    return false;
                }

                if (in_array($name, ['[Content_Types].xml', 'ppt/presentation.xml'], true)) {
                    $entries[$name] = $entry;
                }

                $compressedTotal += $entry['compressed'];
                $uncompressedTotal += $entry['uncompressed'];
                if ($entry['uncompressed'] > 20 * 1024 * 1024 || $uncompressedTotal > 100 * 1024 * 1024) {
                    return false;
                }

                fseek($handle, $entry['extra_length'] + $entry['comment_length'], SEEK_CUR);
            }

            if (! isset($entries['[Content_Types].xml'], $entries['ppt/presentation.xml']) || $compressedTotal > $fileSize) {
                return false;
            }

            foreach (['[Content_Types].xml', $mainPart] as $name) {
                $entry = $entries[$name];
                fseek($handle, $entry['offset']);
                $local = fread($handle, 30);
                if (! is_string($local) || strlen($local) !== 30 || substr($local, 0, 4) !== "PK\x03\x04") {
                    return false;
                }
                $localHeader = unpack('vversion/vflags/vmethod/vtime/vdate/Vcrc/Vcompressed/Vuncompressed/vname_length/vextra_length', substr($local, 4, 26));
                if (($localHeader['flags'] & 1) !== 0 || $localHeader['method'] !== $entry['method']) {
                    return false;
                }
                $xmlOffset = $entry['offset'] + 30 + $localHeader['name_length'] + $localHeader['extra_length'];
                if ($xmlOffset + $entry['compressed'] > $fileSize) {
                    return false;
                }
                fseek($handle, $xmlOffset);
                $bytes = fread($handle, $entry['compressed']);
                $xml = $entry['method'] === 8 ? @gzinflate($bytes) : $bytes;
                if (! is_string($xml) || strlen($xml) > 4 * 1024 * 1024 || strlen($xml) !== $entry['uncompressed']) {
                    return false;
                }

                $document = new \DOMDocument;
                if (! @$document->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS)) {
                    return false;
                }
                $root = $document->documentElement?->localName;
                if (($name === '[Content_Types].xml' && $root !== 'Types') || ($name === $mainPart && $root !== $rootName)) {
                    return false;
                }
                if ($name === '[Content_Types].xml' && ! str_contains($xml, $mainContentType)) {
                    return false;
                }
            }

            return true;
        } finally {
            fclose($handle);
        }
    }

    private function remove(ActivitySection $section): void
    {
        $path = $section->content['media_path'] ?? null;
        $section->delete();
        if ($section->icon_path) {
            Storage::disk('local')->delete($section->icon_path);
        }
        if ($path) {
            Storage::disk('local')->delete($path);
        }
    }

    private function youtubeEmbedUrl(string $url): string
    {
        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');
        $id = null;
        if ($host === 'youtu.be') {
            $id = trim($parts['path'] ?? '', '/');
        } elseif (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'www.youtube-nocookie.com', 'youtube-nocookie.com'], true)) {
            parse_str($parts['query'] ?? '', $query);
            $id = $query['v'] ?? null;
            if (! $id && str_starts_with($parts['path'] ?? '', '/embed/')) {
                $id = basename($parts['path']);
            }
        }

        if (! is_string($id) || ! preg_match('/^[A-Za-z0-9_-]{11}$/', $id)) {
            throw ValidationException::withMessages(['url' => 'Enter a valid YouTube video link.']);
        }

        return 'https://www.youtube-nocookie.com/embed/'.$id;
    }

    private function renumber(Activity $activity, ?ActivityLesson $lesson): void
    {
        $query = $lesson ? $lesson->sections() : $activity->sections()->whereNull('lesson_id');
        $query->orderBy('position')->orderBy('id')->get()->each(fn ($section, $index) => $section->updateQuietly(['position' => $index + 1]));
    }

    private function ownedLesson(Activity $activity, ActivityLesson $lesson): void
    {
        abort_unless($lesson->activity_id === $activity->id, 404);
    }
}
