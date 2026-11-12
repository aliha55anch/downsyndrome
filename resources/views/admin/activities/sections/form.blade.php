@extends('layouts.admin')
@section('title', $section->exists ? 'Edit section' : 'Add section')
@section('content')
<link rel="stylesheet" href="{{ asset('assets/js/pptx-viewer.bundle.css') }}">
@php($inLesson = isset($lesson) && $lesson)
@php($content = $section->content ?? [])
@php($savedType = $section->content_type)
@php($typeAliases = ['paragraph' => 'text', 'steps' => 'text', 'materials' => 'text', 'tips' => 'text', 'matching' => 'text', 'routine' => 'text', 'worksheet' => 'text', 'external_link' => 'text', 'presentation' => 'text'])
@php($selectedType = old('content_type', $typeAliases[$savedType] ?? $savedType))
@php($legacyType = $section->exists && ! in_array($savedType, $types, true))
<div class="page-title"><h1>{{ $section->exists ? 'Edit' : 'Add' }} section</h1><p>{{ $inLesson ? 'Lesson '.$lesson->position.': '.$lesson->title : $activity->title }} · Save each section separately and keep the lesson in the order you choose.</p></div>
<form method="POST" enctype="multipart/form-data" action="{{ $inLesson ? ($section->exists ? route('admin.activities.lessons.sections.update', [$activity, $lesson, $section]) : route('admin.activities.lessons.sections.store', [$activity, $lesson])) : ($section->exists ? route('admin.activities.sections.update', [$activity, $section]) : route('admin.activities.sections.store', $activity)) }}" class="form-box section-editor" data-section-builder data-current-media="{{ $content['media_path'] ?? '' }}" data-media-url="{{ $section->exists && ($content['media_path'] ?? null) ? route('activities.section-media', [$activity, $section]) : '' }}">
    @csrf
    @if ($section->exists) @method('PUT') @endif
    <label for="section-title">Section title <span class="hint">Optional</span></label>
    <input id="section-title" name="title" value="{{ old('title', $section->title) }}" maxlength="255">
    <input type="hidden" name="position" value="{{ old('position', $section->position ?: 1) }}">
    @if ($legacyType)<div class="alert alert-success" role="status">This section was saved with the older “{{ str_replace('_', ' ', $savedType) }}” format. It remains intact until you save an edit. Choose a supported content type if you want to convert it.</div>@endif
    <label for="content-type">Content type</label>
    <select id="content-type" name="content_type" data-content-type required>
        <option value="">Choose a content type</option>
        <option value="heading" @selected($selectedType === 'heading')>Heading</option>
        <option value="text" @selected($selectedType === 'text')>Text / Paragraph</option>
        <option value="image" @selected($selectedType === 'image')>Image</option>
        <option value="video" @selected($selectedType === 'video')>Video Upload</option>
        <option value="youtube" @selected($selectedType === 'youtube')>YouTube Video</option>
        <option value="powerpoint" @selected($selectedType === 'powerpoint')>PowerPoint Presentation (PPTX)</option>
        <option value="pdf" @selected($selectedType === 'pdf')>PDF Document</option>
        <option value="word" @selected($selectedType === 'word')>Word Document</option>
    </select>

    <section class="section-type-panel" data-panel="heading" hidden>
        <h2>Heading style</h2>
        <label for="heading-text">Heading text</label>
        <input id="heading-text" name="heading_text" value="{{ old('heading_text', $content['text'] ?? $section->title) }}" maxlength="255">
        <div class="form-row">
            <div><label for="heading-font">Font</label><select id="heading-font" name="font_family">@foreach(['Arial', 'Georgia', 'Tahoma', 'Times New Roman', 'Trebuchet MS', 'Verdana'] as $font)<option value="{{ $font }}" @selected(old('font_family', $content['font_family'] ?? 'Arial') === $font)>{{ $font }}</option>@endforeach</select></div>
            <div><label for="heading-size">Size</label><select id="heading-size" name="font_size">@foreach([20, 24, 28, 32, 36, 42, 48] as $size)<option value="{{ $size }}" @selected((int) old('font_size', $content['font_size'] ?? 32) === $size)>{{ $size }} px</option>@endforeach</select></div>
            <div><label for="heading-color">Color</label><input id="heading-color" type="color" name="font_color" value="{{ old('font_color', $content['font_color'] ?? '#23384D') }}"></div>
        </div>
        <label for="heading-align">Alignment</label>
        <select id="heading-align" name="text_align">@foreach(['left' => 'Left', 'center' => 'Center', 'right' => 'Right'] as $value => $label)<option value="{{ $value }}" @selected(old('text_align', $content['text_align'] ?? 'left') === $value)>{{ $label }}</option>@endforeach</select>
        <div class="format-options"><label><input type="checkbox" name="bold" value="1" @checked(old('bold', $content['bold'] ?? false))> Bold</label><label><input type="checkbox" name="italic" value="1" @checked(old('italic', $content['italic'] ?? false))> Italic</label><label><input type="checkbox" name="underline" value="1" @checked(old('underline', $content['underline'] ?? false))> Underline</label></div>
        <div class="heading-preview-wrap"><span class="eyebrow">Preview</span><div class="heading-preview" data-heading-preview aria-live="polite"></div></div>
    </section>

    <section class="section-type-panel" data-panel="text" hidden>
        <h2>Text and paragraph</h2>
        <div class="rich-toolbar" data-rich-toolbar aria-label="Text formatting">
            <button type="button" data-command="bold" aria-label="Bold"><strong>B</strong></button><button type="button" data-command="italic" aria-label="Italic"><em>I</em></button><button type="button" data-command="underline" aria-label="Underline"><u>U</u></button><button type="button" data-command="strikeThrough" aria-label="Strikethrough"><s>S</s></button>
            <select data-command="fontName" aria-label="Font family"><option value="Arial">Arial</option><option value="Georgia">Georgia</option><option value="Tahoma">Tahoma</option><option value="Times New Roman">Times New Roman</option><option value="Trebuchet MS">Trebuchet MS</option><option value="Verdana">Verdana</option></select>
            <select data-command="fontSize" aria-label="Font size"><option value="3">Normal</option><option value="2">Small</option><option value="4">Large</option><option value="5">Very large</option></select>
            <label class="toolbar-color">Text color <input type="color" data-command="foreColor" value="#23384d"></label><label class="toolbar-color">Highlight <input type="color" data-command="hiliteColor" value="#fff2a8"></label>
            <select data-command="formatBlock" aria-label="Paragraph style"><option value="p">Paragraph</option><option value="h2">Heading 2</option><option value="h3">Heading 3</option><option value="blockquote">Quote</option></select>
            <button type="button" data-command="justifyLeft">Left</button><button type="button" data-command="justifyCenter">Center</button><button type="button" data-command="justifyRight">Right</button><button type="button" data-command="justifyFull">Justify</button>
            <button type="button" data-command="insertUnorderedList">Bullets</button><button type="button" data-command="insertOrderedList">Numbered</button><button type="button" data-command="indent">Indent</button><button type="button" data-command="outdent">Outdent</button><button type="button" data-spacing="0.4em">Compact spacing</button><button type="button" data-spacing="1.2em">Roomy spacing</button><button type="button" data-command="undo">Undo</button><button type="button" data-command="redo">Redo</button><button type="button" data-command="removeFormat">Clear formatting</button>
        </div>
        <div class="rich-editor" contenteditable="true" role="textbox" aria-multiline="true" aria-label="Section text" data-rich-editor>{!! old('text_html', $content['html'] ?? e($content['text'] ?? '')) !!}</div>
        <textarea name="text_html" data-rich-value hidden></textarea>
        <p class="hint">Use the toolbar to format text. The content is cleaned before it is saved and displayed.</p>
    </section>

    <section class="section-type-panel" data-panel="image" hidden>
        <h2>Image</h2><label for="section-image">Upload an image</label><input id="section-image" type="file" name="media" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-file-preview>
        <p class="hint">JPG, PNG, or WebP. Maximum 600 KB.</p>
        <p class="selected-file" data-file-name aria-live="polite">{{ $content['media_filename'] ?? '' }}</p>
        <img class="media-preview" data-image-preview alt="Image preview" @if($content['media_path'] ?? null) src="{{ route('activities.section-media', [$activity, $section]) }}" @elseif($content['url'] ?? null) src="{{ $content['url'] }}" @else hidden @endif>
        @if ($content['media_path'] ?? null)<label class="inline-check"><input type="checkbox" name="remove_media" value="1"> Remove the uploaded image</label>@endif
    </section>

    <section class="section-type-panel" data-panel="video" hidden>
        <h2>Video upload</h2><label for="section-video">Choose a video</label><input id="section-video" type="file" name="media" accept=".mp4,.webm,.mov,video/mp4,video/webm,video/quicktime" data-file-preview>
        <p class="hint">MP4, WebM, or MOV. Maximum 35 MB.</p><p class="selected-file" data-file-name aria-live="polite">{{ $content['media_filename'] ?? '' }}</p>
        <video class="media-preview" data-video-preview controls playsinline preload="metadata" @if($content['media_path'] ?? null) src="{{ route('activities.section-media', [$activity, $section]) }}" @else hidden @endif></video>
        @if ($content['media_path'] ?? null)<label class="inline-check"><input type="checkbox" name="remove_media" value="1"> Remove the uploaded video</label>@endif
    </section>

    <section class="section-type-panel" data-panel="youtube" hidden>
        <h2>YouTube video</h2><label for="youtube-url">YouTube video URL</label><input id="youtube-url" type="url" name="url" value="{{ old('url', $content['url'] ?? '') }}" placeholder="https://www.youtube.com/watch?v=…" data-youtube-url>
        <div class="youtube-preview" data-youtube-preview hidden><div class="video-frame"><iframe title="YouTube video preview" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe></div></div>
        <p class="hint" data-youtube-status aria-live="polite">Paste a YouTube watch, short, or embed link to preview it.</p>
    </section>

    <section class="section-type-panel" data-panel="powerpoint" hidden>
        <h2>PowerPoint Presentation (PPTX)</h2><label for="section-powerpoint">Upload a PowerPoint presentation</label><input id="section-powerpoint" type="file" name="media" accept=".pptx,application/vnd.openxmlformats-officedocument.presentationml.presentation" data-file-preview>
        <p class="hint">PPTX only. Maximum 20 MB. The original file is kept private.</p><p class="selected-file" data-file-name aria-live="polite">{{ $content['media_filename'] ?? '' }}</p>
        <div class="pptx-admin-preview" data-admin-pptx-preview data-pptx-shell data-autoplay-delay="5000" @if(!($content['media_path'] ?? null)) hidden @endif><div class="pptx-stage" data-pptx-stage></div><div class="pptx-controls"><button class="btn btn-outline btn-small" type="button" data-pptx-previous disabled>Previous</button><span class="hint" data-pptx-counter aria-live="polite">Loading slides</span><button class="btn btn-outline btn-small" type="button" data-pptx-next disabled>Next</button><button class="btn btn-outline btn-small" type="button" data-pptx-play aria-pressed="false">Play slideshow</button><button class="btn btn-outline btn-small" type="button" data-pptx-fullscreen>Full screen</button></div><p class="pptx-status" data-pptx-status role="status" aria-live="polite">Loading presentation preview…</p></div>
        @if ($content['media_path'] ?? null)<label class="inline-check"><input type="checkbox" name="remove_media" value="1"> Remove the uploaded presentation</label>@endif
    </section>

    <section class="section-type-panel" data-panel="pdf" hidden>
        <h2>PDF document</h2><label for="section-pdf">Upload a PDF file</label><input id="section-pdf" type="file" name="media" accept=".pdf,application/pdf" data-file-preview>
        <p class="hint">PDF only. Maximum 20 MB. The original file is kept private.</p><p class="selected-file" data-file-name aria-live="polite">{{ $content['media_filename'] ?? '' }}</p>
        @if ($content['media_path'] ?? null)<label class="inline-check"><input type="checkbox" name="remove_media" value="1"> Remove the uploaded PDF</label>@endif
    </section>

    <section class="section-type-panel" data-panel="word" hidden>
        <h2>Word document</h2><label for="section-word">Upload a Word document</label><input id="section-word" type="file" name="media" accept=".doc,.docx,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" data-file-preview>
        <p class="hint">Word .doc or .docx only. Maximum 20 MB. The original file is kept private.</p><p class="selected-file" data-file-name aria-live="polite">{{ $content['media_filename'] ?? '' }}</p>
        @if ($content['media_path'] ?? null)<label class="inline-check"><input type="checkbox" name="remove_media" value="1"> Remove the uploaded Word document</label>@endif
    </section>

    <div class="btn-row section-save-row"><button class="btn btn-blue" type="submit">Save section</button>@if($inLesson)<a class="btn btn-outline" href="{{ route('admin.activities.lessons.edit', [$activity, $lesson]) }}">Cancel</a>@else<a class="btn btn-outline" href="{{ route('admin.activities.sections.index', $activity) }}">Cancel</a>@endif</div>
</form>
<script>
(function () {
    var form = document.querySelector('[data-section-builder]');
    if (!form) return;
    var selector = form.querySelector('[data-content-type]');
    var editor = form.querySelector('[data-rich-editor]');
    var hiddenEditor = form.querySelector('[data-rich-value]');
    var mediaUrl = form.dataset.mediaUrl;
    function updatePanels() {
        form.querySelectorAll('[data-panel]').forEach(function (panel) {
            var active = panel.dataset.panel === selector.value;
            panel.hidden = !active;
            panel.querySelectorAll('input, textarea, select, button').forEach(function (control) {
                if (control.type !== 'button') control.disabled = !active;
            });
        });
    }
    selector.addEventListener('change', updatePanels);
    updatePanels();

    form.querySelectorAll('[data-rich-toolbar] [data-command]').forEach(function (control) {
        function apply() {
            if (!editor) return;
            editor.focus();
            document.execCommand(control.dataset.command, false, control.value || null);
        }
        if (control.tagName === 'SELECT' || control.type === 'color') control.addEventListener('change', apply);
        else control.addEventListener('click', apply);
    });
    form.querySelectorAll('[data-rich-toolbar] [data-spacing]').forEach(function (control) {
        control.addEventListener('mousedown', function (event) { event.preventDefault(); });
        control.addEventListener('click', function () {
            if (!editor) return;
            var selection = window.getSelection();
            var node = selection && selection.anchorNode;
            var block = node && (node.nodeType === 1 ? node : node.parentElement).closest('p,div,h2,h3,li,blockquote');
            if (block && editor.contains(block)) block.style.marginBottom = control.dataset.spacing;
            editor.focus();
        });
    });
    form.addEventListener('submit', function () {
        if (editor && !editor.closest('[hidden]')) hiddenEditor.value = editor.innerHTML;
    });

    var headingControls = ['heading-text', 'heading-font', 'heading-size', 'heading-color', 'heading-align', 'bold', 'italic', 'underline'].map(function (id) {
        return form.querySelector('#' + id) || form.querySelector('[name="' + id + '"]');
    }).filter(Boolean);
    var headingPreview = form.querySelector('[data-heading-preview]');
    function updateHeading() {
        if (!headingPreview) return;
        var text = form.querySelector('[name="heading_text"]');
        var font = form.querySelector('[name="font_family"]');
        var size = form.querySelector('[name="font_size"]');
        var color = form.querySelector('[name="font_color"]');
        var align = form.querySelector('[name="text_align"]');
        headingPreview.textContent = text.value || 'Your heading preview';
        headingPreview.style.fontFamily = font.value;
        headingPreview.style.fontSize = size.value + 'px';
        headingPreview.style.color = color.value;
        headingPreview.style.textAlign = align.value;
        headingPreview.style.fontWeight = form.querySelector('[name="bold"]').checked ? '700' : '400';
        headingPreview.style.fontStyle = form.querySelector('[name="italic"]').checked ? 'italic' : 'normal';
        headingPreview.style.textDecoration = form.querySelector('[name="underline"]').checked ? 'underline' : 'none';
    }
    headingControls.forEach(function (control) { control.addEventListener('input', updateHeading); control.addEventListener('change', updateHeading); });
    updateHeading();

    form.querySelectorAll('[data-file-preview]').forEach(function (input) {
        input.addEventListener('change', function () {
            var file = input.files && input.files[0];
            var panel = input.closest('[data-panel]');
            var name = panel.querySelector('[data-file-name]');
            if (!file) return;
            var maxBytes = input.accept.indexOf('.jpg') !== -1 ? 600 * 1024 : (input.accept.indexOf('.mp4') !== -1 ? 35 * 1024 * 1024 : 20 * 1024 * 1024);
            var kind = input.accept.indexOf('.jpg') !== -1 ? 'image' : (input.accept.indexOf('.mp4') !== -1 ? 'video' : 'pptx');
            var extension = file.name.split('.').pop().toLowerCase();
            var allowed = kind === 'image' ? ['jpg', 'jpeg', 'png', 'webp'] : (kind === 'video' ? ['mp4', 'webm', 'mov'] : ['pptx']);
            if (file.size > maxBytes || allowed.indexOf(extension) === -1) {
                name.textContent = file.size > maxBytes ? 'This file is too large for the selected content type.' : 'Choose a supported ' + kind + ' file.';
                name.dataset.invalid = 'true';
                input.value = '';
                return;
            }
            delete name.dataset.invalid;
            name.textContent = file.name;
            if (input.accept.indexOf('image/') !== -1 || input.accept.indexOf('.jpg') !== -1) {
                var image = panel.querySelector('[data-image-preview]');
                if (image) { image.src = URL.createObjectURL(file); image.hidden = false; }
            } else if (input.accept.indexOf('video/') !== -1 || input.accept.indexOf('.mp4') !== -1) {
                var video = panel.querySelector('[data-video-preview]');
                if (video) { video.src = URL.createObjectURL(file); video.hidden = false; video.load(); }
            }
            var warning = panel.querySelector('[data-remove-warning]');
            if (warning) warning.hidden = true;
            if (input.accept.indexOf('.pptx') !== -1 && window.loadPptxPreview) window.loadPptxPreview(file);
        });
    });

    var youtubeInput = form.querySelector('[data-youtube-url]');
    var youtubePreview = form.querySelector('[data-youtube-preview]');
    var youtubeStatus = form.querySelector('[data-youtube-status]');
    function youtubeId(value) {
        try {
            var parsed = new URL(value);
            var host = parsed.hostname.toLowerCase();
            var id = host === 'youtu.be' ? parsed.pathname.slice(1).split('/')[0] : (['youtube.com', 'www.youtube.com', 'm.youtube.com'].indexOf(host) !== -1 ? (parsed.searchParams.get('v') || (parsed.pathname.indexOf('/embed/') === 0 ? parsed.pathname.split('/')[2] : '')) : '');
            if ((parsed.hostname === 'youtube-nocookie.com' || parsed.hostname === 'www.youtube-nocookie.com') && parsed.pathname.indexOf('/embed/') === 0) id = parsed.pathname.split('/')[2];
            return /^[A-Za-z0-9_-]{11}$/.test(id) ? id : '';
        } catch (_) { return ''; }
    }
    function updateYoutube() {
        if (!youtubeInput) return;
        var id = youtubeId(youtubeInput.value.trim());
        youtubePreview.hidden = !id;
        if (id) { youtubePreview.querySelector('iframe').src = 'https://www.youtube-nocookie.com/embed/' + id; youtubeStatus.textContent = 'Previewing a privacy-enhanced YouTube embed.'; }
        else { youtubePreview.querySelector('iframe').removeAttribute('src'); youtubeStatus.textContent = youtubeInput.value ? 'Enter a valid YouTube video link.' : 'Paste a YouTube watch, short, or embed link to preview it.'; }
    }
    if (youtubeInput) { youtubeInput.addEventListener('input', updateYoutube); updateYoutube(); }

    var removeControl = form.querySelector('[name="remove_media"]');
    if (removeControl) removeControl.addEventListener('change', function () {
        var fileInput = removeControl.closest('[data-panel]').querySelector('[data-file-preview]');
        if (removeControl.checked && !fileInput.files.length) {
            var message = document.createElement('span');
            message.className = 'hint';
            message.dataset.removeWarning = '';
            message.textContent = 'The current file will be removed when you save.';
            removeControl.closest('label').after(message);
        } else {
            var warning = form.querySelector('[data-remove-warning]');
            if (warning) warning.remove();
        }
    });
})();
</script>
<script type="module" src="{{ asset('assets/js/pptx-viewer.bundle.js') }}"></script>
@endsection
