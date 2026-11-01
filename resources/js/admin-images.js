// Display remote images in the browser only. Never proxy arbitrary image addresses through PHP.
const timers = new WeakMap();
function preview(editor) {
    const input = editor.querySelector('[data-image-address]');
    const image = editor.querySelector('[data-image-preview]');
    const error = editor.querySelector('[data-image-error]');
    const value = input.value.trim();
    const valid = value.startsWith('images/') || /^https:\/\//i.test(value);
    image.parentElement.hidden = !value || !valid;
    error.hidden = !value || valid;
    if (!value || !valid) { image.removeAttribute('src'); return; }
    try { image.src = new URL(value, location.origin + '/').href; }
    catch { error.hidden = false; image.parentElement.hidden = true; }
}
document.addEventListener('input', event => {
    if (!event.target.matches('[data-image-address]')) return;
    const editor = event.target.closest('[data-image-source]');
    clearTimeout(timers.get(editor)); timers.set(editor, setTimeout(() => { if (editor.isConnected) preview(editor); }, 350));
});
document.addEventListener('click', event => {
    const button = event.target.closest('[data-image-choice]'); if (!button) return;
    const editor = button.closest('[data-image-source]');
    const input = editor.querySelector('[data-image-address]');
    input.value = button.dataset.imageChoice;
    input.dispatchEvent(new Event('input', { bubbles: true }));
    preview(editor); editor.querySelector('details').open = false;
});
document.addEventListener('error', event => {
    if (!event.target.matches?.('[data-image-preview]')) return;
    const editor = event.target.closest('[data-image-source]');
    if (editor) editor.querySelector('[data-image-error]').hidden = false;
}, true);
document.addEventListener('load', event => {
    if (!event.target.matches?.('[data-image-preview]')) return;
    const editor = event.target.closest('[data-image-source]');
    if (editor) editor.querySelector('[data-image-error]').hidden = true;
}, true);
