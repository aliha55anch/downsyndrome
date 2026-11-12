import { createPptxViewer } from 'pptx-vanilla-viewer';
import 'pptx-vanilla-viewer/styles.css';

function mountViewer(shell) {
  const stage = shell.querySelector('[data-pptx-stage]');
  const status = shell.querySelector('[data-pptx-status]');
  const counter = shell.querySelector('[data-pptx-counter]');
  const previous = shell.querySelector('[data-pptx-previous]');
  const next = shell.querySelector('[data-pptx-next]');
  const play = shell.querySelector('[data-pptx-play]');
  const fullscreen = shell.querySelector('[data-pptx-fullscreen]');
  const autoplayDelay = Number(shell.dataset.autoplayDelay || 5000);
  let viewer;
  let timer;

  const updateCounter = (index) => {
    const total = viewer?.getSlideCount?.() ?? 0;
    counter.textContent = total ? `Slide ${index + 1} of ${total}` : 'Loading slides';
    previous.disabled = total < 2;
    next.disabled = total < 2;
  };

  const stop = () => {
    if (timer) window.clearInterval(timer);
    timer = undefined;
    play.textContent = 'Play slideshow';
    play.setAttribute('aria-pressed', 'false');
  };

  const start = () => {
    if (!viewer || (viewer.getSlideCount?.() ?? 0) < 2 || timer) return;
    timer = window.setInterval(() => {
      const index = viewer.getCurrentSlide?.() ?? 0;
      if (index >= viewer.getSlideCount() - 1) {
        stop();
        return;
      }
      viewer.next();
    }, autoplayDelay);
    play.textContent = 'Pause slideshow';
    play.setAttribute('aria-pressed', 'true');
  };

  const options = {
    editable: false,
    showToolbar: false,
    showThumbnails: false,
    showCompatibilityToasts: true,
    autosave: false,
    locale: 'en',
    onLoad: ({ slideCount }) => {
      status.textContent = `Presentation ready · ${slideCount} slides`;
      status.dataset.state = 'ready';
      updateCounter(viewer?.getCurrentSlide?.() ?? 0);
    },
    onSlideChange: (index) => updateCounter(index),
    onError: (message) => {
      status.textContent = message || 'This presentation could not be displayed. Download the original file to open it in PowerPoint.';
      status.dataset.state = 'error';
      stop();
    },
  };

  try {
    viewer = createPptxViewer(stage, options);
  } catch (_) {
    status.textContent = 'The presentation viewer could not start in this browser.';
    status.dataset.state = 'error';
    return { loadFile: () => Promise.resolve(false) };
  }

  const loadFile = async (fileOrUrl) => {
    stop();
    status.textContent = 'Loading presentation…';
    status.dataset.state = 'loading';
    try {
      if (fileOrUrl instanceof File) await viewer.loadFile(fileOrUrl);
      else await viewer.loadUrl(fileOrUrl);
      updateCounter(viewer.getCurrentSlide?.() ?? 0);
      return true;
    } catch (_) {
      status.textContent = 'This presentation could not be displayed. Check that it is a valid PPTX file and try again.';
      status.dataset.state = 'error';
      return false;
    }
  };

  previous.addEventListener('click', () => { stop(); viewer.prev(); });
  next.addEventListener('click', () => { stop(); viewer.next(); });
  play.addEventListener('click', () => timer ? stop() : start());
  fullscreen.addEventListener('click', async () => {
    try {
      if (document.fullscreenElement === shell) await document.exitFullscreen();
      else await shell.requestFullscreen();
    } catch (_) {
      status.textContent = 'Full-screen mode is not available in this browser.';
    }
  });
  document.addEventListener('fullscreenchange', () => {
    fullscreen.textContent = document.fullscreenElement === shell ? 'Exit full screen' : 'Full screen';
  });
  shell.addEventListener('keydown', (event) => {
    if (event.key === 'ArrowLeft') { stop(); viewer.prev(); }
    if (event.key === 'ArrowRight') { stop(); viewer.next(); }
    if (event.key === 'Escape') stop();
  });

  return { viewer, loadFile };
}

document.querySelectorAll('[data-pptx-shell]').forEach((shell) => {
  const mounted = mountViewer(shell);
  const source = shell.dataset.pptxSource;
  if (source) mounted.loadFile(source);
});

const adminForm = document.querySelector('[data-section-builder]');
if (adminForm) {
  const shell = adminForm.querySelector('[data-admin-pptx-preview]');
  if (shell) {
    const mounted = mountViewer(shell);
    const source = adminForm.dataset.mediaUrl;
    if (source) mounted.loadFile(source);
    window.loadPptxPreview = (file) => {
      shell.hidden = false;
      mounted.loadFile(file);
    };
  }
}
