// Legacy demo profiles retired. Resolve only real database profile IDs.
const id = new URLSearchParams(window.location.search).get('id');
if (/^[1-9][0-9]*$/.test(id || '')) {
  window.location.replace('/doctors/' + encodeURIComponent(id));
} else {
  window.location.replace('/doctors');
}
