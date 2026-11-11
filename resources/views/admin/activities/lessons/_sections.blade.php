<section class="form-box lesson-section-builder">
    <div class="panel-heading"><div><span class="eyebrow">Lesson content</span><h2>Sections</h2></div><a class="btn btn-blue btn-small" href="{{ route('admin.activities.lessons.sections.create', [$activity, $lesson, 'position' => 1]) }}">+ Add Section Here</a></div>
    <p class="hint">Add content at the beginning, between sections, or after the last section. Drag sections to change their order.</p>
    <div data-sortable-sections data-reorder-url="{{ route('admin.activities.lessons.sections.reorder', [$activity, $lesson]) }}" data-csrf="{{ csrf_token() }}">
        @forelse ($lesson->sections as $section)
            <article class="lesson-section-row sortable-section" draggable="true" data-sort-id="{{ $section->id }}">
                <div class="section-summary"><span class="eyebrow">Section {{ $section->position }} · {{ ucfirst($section->content_type === 'text' || $section->content_type === 'paragraph' ? 'Text / Paragraph' : str_replace('_', ' ', $section->content_type)) }}</span>@if($section->title)<h3>{{ $section->title }}</h3>@endif
                    <div class="section-preview">
                        @if ($section->content_type === 'heading')
                            <span class="heading-preview" style="font-family:{{ in_array($section->content['font_family'] ?? '', ['Arial','Georgia','Tahoma','Times New Roman','Trebuchet MS','Verdana'], true) ? $section->content['font_family'] : 'Arial' }};font-size:{{ max(20, min(48, (int) ($section->content['font_size'] ?? 32))) }}px;color:{{ preg_match('/^#[0-9A-Fa-f]{6}$/', $section->content['font_color'] ?? '') ? $section->content['font_color'] : '#23384D' }};text-align:{{ in_array($section->content['text_align'] ?? '', ['left','center','right'], true) ? $section->content['text_align'] : 'left' }}">{{ $section->content['text'] ?? $section->title }}</span>
                        @elseif (in_array($section->content_type, ['text', 'paragraph'], true))
                            <div class="rich-content">{!! \App\Support\RichTextSanitizer::sanitize($section->content['html'] ?? e($section->content['text'] ?? '')) !!}</div>
                        @elseif ($section->content_type === 'image' && ($section->content['media_path'] ?? null))
                            <img class="section-preview-image" src="{{ route('activities.section-media', [$activity, $section]) }}" alt="">
                        @elseif ($section->content_type === 'video' && ($section->content['media_path'] ?? null))
                            <span class="content-file-preview">Video uploaded</span>
                        @elseif ($section->content_type === 'youtube' && ($section->content['url'] ?? null))
                            <span class="content-file-preview">YouTube video linked</span>
                        @elseif ($section->content_type === 'powerpoint' && ($section->content['media_path'] ?? null))
                            <span class="content-file-preview">{{ $section->content['media_filename'] ?? 'Presentation uploaded' }}</span>
                        @elseif (in_array($section->content_type, ['pdf', 'word'], true) && ($section->content['media_path'] ?? null))
                            <span class="content-file-preview">{{ $section->content['media_filename'] ?? ucfirst($section->content_type).' uploaded' }}</span>
                        @else
                            <span class="content-file-preview">{{ ucfirst(str_replace('_', ' ', $section->content_type)) }} content</span>
                        @endif
                    </div>
                </div>
                <div class="section-actions"><a class="btn btn-outline btn-small" href="{{ route('admin.activities.lessons.sections.edit', [$activity, $lesson, $section]) }}">Edit</a><form method="POST" action="{{ route('admin.activities.lessons.sections.destroy', [$activity, $lesson, $section]) }}" onsubmit="return confirm('Delete this section and its uploaded file?')">@csrf @method('DELETE')<button class="btn btn-red btn-small" type="submit">Delete</button></form></div>
            </article>
            @if (! $loop->last)<p class="insert-between"><a class="btn btn-outline btn-small" href="{{ route('admin.activities.lessons.sections.create', [$activity, $lesson, 'position' => $section->position + 1]) }}">+ Add Section Here</a></p>@endif
        @empty
            <div class="empty-state"><h3>No sections in this lesson yet.</h3><p>Start with the first heading, text, image, video, YouTube video, or presentation.</p><a class="btn btn-blue" href="{{ route('admin.activities.lessons.sections.create', [$activity, $lesson, 'position' => 1]) }}">+ Add first section</a></div>
        @endforelse
    </div>
    @if ($lesson->sections->isNotEmpty())<p class="insert-after"><a class="btn btn-blue btn-small" href="{{ route('admin.activities.lessons.sections.create', [$activity, $lesson, 'position' => $lesson->sections->count() + 1]) }}">+ Add Section Here</a></p>@endif
</section>
<script>
(function () {
    var list = document.querySelector('[data-sortable-sections]');
    if (!list) return;
    var dragged = null;
    list.querySelectorAll('[data-sort-id]').forEach(function (row) {
        row.addEventListener('dragstart', function () { dragged = row; row.classList.add('is-dragging'); });
        row.addEventListener('dragover', function (event) {
            event.preventDefault();
            if (!dragged || dragged === row) return;
            var bounds = row.getBoundingClientRect();
            list.insertBefore(dragged, event.clientY < bounds.top + bounds.height / 2 ? row : row.nextSibling);
        });
        row.addEventListener('dragend', function () {
            row.classList.remove('is-dragging');
            var order = Array.from(list.querySelectorAll('[data-sort-id]')).map(function (item) { return item.dataset.sortId; });
            fetch(list.dataset.reorderUrl, {method: 'POST', headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': list.dataset.csrf, 'Accept': 'application/json'}, body: JSON.stringify({order: order})})
                .then(function (response) { if (!response.ok) throw new Error(); window.location.reload(); })
                .catch(function () { window.location.reload(); });
            dragged = null;
        });
    });
})();
</script>
