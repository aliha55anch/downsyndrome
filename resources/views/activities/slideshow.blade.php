<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $activity->title }} — Slideshow | Little Steps</title>
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/interface.css') }}">
</head>
<body>
    <main class="container section presentation-page" data-presentation>
        <p><a class="back-link" href="{{ route('activities.show', $activity) }}">Back to activity</a></p>
        <h1>{{ $activity->title }}</h1>
        <div class="presentation-slide" data-slide></div>
        <div class="btn-row">
            <button class="btn btn-outline" type="button" data-previous>Previous</button>
            <span class="hint" data-counter></span>
            <button class="btn btn-blue" type="button" data-next>Next</button>
            <button class="btn btn-outline" type="button" data-fullscreen>Full screen</button>
            <button class="btn btn-outline" type="button" data-replay>Replay animation</button>
        </div>
        <label style="font-weight:400;"><input type="checkbox" data-reduced-motion> Reduce motion</label>
        <script type="application/json" data-slides>@json($slides)</script>
        <div class="disclaimer">This presentation is an educational resource and does not replace professional medical or therapeutic advice.</div>
    </main>
    <script>
    (function () {
        var presentation = document.querySelector('[data-presentation]');
        var slides = JSON.parse(presentation.querySelector('[data-slides]').textContent || '[]');
        var current = 0;
        var slide = presentation.querySelector('[data-slide]');
        var counter = presentation.querySelector('[data-counter]');
        var reduced = presentation.querySelector('[data-reduced-motion]');
        function escapeText(value) { var div = document.createElement('div'); div.textContent = value || ''; return div.innerHTML; }
        function render() {
            var item = slides[current] || {};
            var html = '<h2>' + escapeText(item.title) + '</h2><p>' + escapeText(item.text) + '</p>';
            if (item.image_url) html += '<img src="' + escapeText(item.image_url) + '" alt="" style="max-width:100%;border-radius:12px;">';
            if (Array.isArray(item.steps)) html += '<ol>' + item.steps.map(function (step) { return '<li>' + escapeText(step) + '</li>'; }).join('') + '</ol>';
            slide.innerHTML = html;
            counter.textContent = (current + 1) + ' of ' + slides.length;
            slide.classList.remove('presentation-replay');
            if (!reduced.checked) { void slide.offsetWidth; slide.classList.add('presentation-replay'); }
        }
        presentation.querySelector('[data-previous]').onclick = function () { current = (current - 1 + slides.length) % slides.length; render(); };
        presentation.querySelector('[data-next]').onclick = function () { current = (current + 1) % slides.length; render(); };
        presentation.querySelector('[data-replay]').onclick = render;
        presentation.querySelector('[data-fullscreen]').onclick = function () { if (presentation.requestFullscreen) presentation.requestFullscreen(); };
        reduced.onchange = render;
        render();
    }());
    </script>
</body>
</html>
