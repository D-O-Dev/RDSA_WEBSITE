import './messages.js';
import './admin-experience.js';
import '../css/admin-interactions.css';

document.documentElement.classList.add('admin-live');
let controller, sequence = 0, timer;
const notice = document.createElement('div'); notice.className = 'live-notice'; notice.setAttribute('role', 'status'); notice.hidden = true; document.body.append(notice);
let feedbackTimer;
function feedback(text, temporary = false) {
    clearTimeout(feedbackTimer); notice.textContent = text; notice.hidden = !text;
    if (temporary) feedbackTimer = setTimeout(() => { notice.hidden = true; }, 4500);
}
const getFilter = element => element.closest('form[method="GET"], form[method="get"]');

async function load(url, push = false, restoreFocus = true, loadingMessage = 'Recherche en cours…') {
    controller?.abort(); controller = new AbortController(); const version = ++sequence;
    const views = document.getElementById('views'); views.setAttribute('aria-busy', 'true'); feedback('');
    const pendingNotice = setTimeout(() => { if (version === sequence) feedback(loadingMessage); }, 400);
    try {
        const response = await fetch(url, { signal: controller.signal, headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
        if (response.redirected) { window.location.assign(response.url); return; }
        if (!response.ok) throw new Error('Recherche indisponible. Vérifie les filtres ou réessaie.');
        const html = new DOMParser().parseFromString(await response.text(), 'text/html');
        if (version !== sequence) return;
        const replacement = html.getElementById('views'); if (!replacement) throw new Error('La session a expiré. Recharge la page.');
        const active = document.activeElement;
        const name = restoreFocus && views.contains(active) ? active.name : null;
        const selection = name && ['text', 'search', 'tel'].includes(active.type) ? [active.selectionStart, active.selectionEnd] : null;
        views.replaceChildren(...replacement.childNodes);
        const field = name ? Array.from(views.querySelectorAll('[name]')).find(input => input.name === name) : null;
        if (field) { field.focus({ preventScroll: true }); if (selection) field.setSelectionRange(...selection); }
        const method = push ? 'pushState' : 'replaceState'; history[method](null, '', url);
        syncMediaFields(); feedback(''); refreshCount();
        document.dispatchEvent(new Event('rdsa:content-updated'));
        return true;
    } catch (error) { if (error.name !== 'AbortError' && version === sequence) feedback(error.message || 'Recherche impossible. Réessaie.'); }
    finally { clearTimeout(pendingNotice); if (version === sequence) views.removeAttribute('aria-busy'); }
}
function filter(form) {
    const url = new URL(form.action || location.href); url.search = new URLSearchParams(new FormData(form)).toString();
    return load(url);
}
document.addEventListener('input', event => {
    const form = getFilter(event.target); if (!form || event.isComposing || !event.target.matches('input:not([type="hidden"])')) return;
    // Invalider tout résultat d'une recherche antérieure dès la frappe suivante.
    controller?.abort(); sequence++; document.getElementById('views').removeAttribute('aria-busy'); feedback('');
    clearTimeout(timer); timer = setTimeout(() => filter(form), 320);
});
document.addEventListener('change', event => {
    const form = getFilter(event.target);
    if (form && event.target.matches('select')) { clearTimeout(timer); filter(form); }
    syncMediaFields();
});
document.addEventListener('submit', event => { const form = getFilter(event.target); if (form) { event.preventDefault(); clearTimeout(timer); filter(form); } });
document.addEventListener('click', event => {
    const link = event.target.closest('.pgn a, .calendar-nav a, form[method="GET"] a, form[method="get"] a');
    if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
    event.preventDefault(); clearTimeout(timer); load(link.href, Boolean(link.closest('.pgn,.calendar-nav')), false, link.closest('.calendar-nav') ? 'Chargement du calendrier…' : undefined);
});
window.addEventListener('popstate', () => { clearTimeout(timer); load(location.href, false, false); });

document.addEventListener('change', async event => {
    const form = event.target.closest('[data-instant-action]'); if (!form) return;
    if (event.target.type !== 'checkbox') return;
    const input = event.target, checked = input.checked;
    input.disabled = true;
    const payload = new FormData(form); payload.set(input.name, checked ? '1' : '0');
    try {
        const response = await fetch(form.action, { method: 'POST', body: payload, credentials: 'same-origin', headers: { Accept: 'application/json' } });
        if (!response.ok || response.redirected && response.url.includes('/login')) throw new Error('Modification impossible. Réessaie.');
        if (await load(location.href, false, false)) feedback('Modification enregistrée.', true);
    } catch { input.checked = !checked; feedback('Modification non enregistrée. Vérifie ta connexion.'); }
    finally { input.disabled = false; }
});
function syncMediaFields() {
    const kind = document.getElementById('media-kind');
    if (!kind) return;
    const video = kind.value === 'video';
    document.querySelectorAll('[data-video-field]').forEach(field => { field.hidden = !video; field.querySelectorAll('input,select').forEach(input => input.disabled = !video); });
    const upload = document.getElementById('media-upload');
    if (upload.files[0] && upload.files[0].type.startsWith('video/') !== video) upload.value = '';
    upload.accept = video ? 'video/mp4,video/webm' : 'image/jpeg,image/png,image/webp';
    document.querySelectorAll('#media-existing option[value]').forEach(option => { if (!option.value) return; const isVideo = /\.(mp4|webm)$/i.test(option.value); option.hidden = isVideo !== video; });
    const existing = document.getElementById('media-existing');
    if (existing.selectedOptions[0]?.hidden) existing.value = '';
}
syncMediaFields();
// Keep the link field visible; pasting a video link selects its type automatically.
document.addEventListener('input', event => {
    if (event.target.id !== 'media-video-url' || !event.target.value.trim()) return;
    const kind = document.getElementById('media-kind');
    if (kind) { kind.value = 'video'; syncMediaFields(); }
});
document.addEventListener('change', event => {
    if (event.target.id === 'media-kind' && event.target.value === 'photo') {
        const link = document.getElementById('media-video-url');
        if (link) link.value = '';
    }
});
async function refreshCount() {
    const badge = document.querySelector('[data-message-count]'); if (!badge || document.hidden) return;
    try {
        const response = await fetch(badge.dataset.url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        if (!response.ok || response.redirected) return;
        const data = await response.json(); badge.textContent = data.count; badge.hidden = data.count === 0;
    } catch { /* Le compteur reprendra à la prochaine actualisation. */ }
}
setInterval(refreshCount, 30000);
document.addEventListener('rdsa:messages-updated', refreshCount);
document.addEventListener('visibilitychange', () => { if (!document.hidden) refreshCount(); });

let previewUrl;
document.addEventListener('change', event => {
    if (!['media-upload', 'media-existing'].includes(event.target.id)) return;
    const target = document.querySelector('[data-media-preview]'); if (!target) return;
    const file = document.getElementById('media-upload').files[0];
    const input = document.getElementById('media-upload');
    const limit = Math.min(Number(input.dataset.uploadLimit), document.getElementById('media-kind').value === 'photo' ? 10485760 : 52428800);
    input.setCustomValidity(file && file.size > limit ? 'Le fichier dépasse la limite autorisée. Utilise un fichier plus léger ou un lien vidéo.' : '');
    if (file && file.size > limit) { input.reportValidity(); return; }
    if (previewUrl) URL.revokeObjectURL(previewUrl);
    const option = document.getElementById('media-existing').selectedOptions[0];
    let source = option?.dataset.source;
    const isVideo = file ? file.type.startsWith('video/') : /\.(mp4|webm)$/i.test(source || '');
    if (file) { previewUrl = URL.createObjectURL(file); source = previewUrl; }
    if (!source) return;
    const image = document.createElement(isVideo ? 'video' : 'img');
    image.src = source; if (isVideo) image.controls = true; else image.alt = 'Aperçu du média';
    target.replaceChildren(image);
});

// Background motion from the original admin mockup.
const adminBackground = document.getElementById('bgimg');
if (adminBackground) {
    const reduced = matchMedia('(prefers-reduced-motion: reduce)');
    let tx = 0, ty = 0, sy = 0;
    const paint = () => {
        if (reduced.matches) { adminBackground.style.transform = ''; return; }
        adminBackground.style.transform = `translate3d(${tx}px,${ty + sy}px,0) scale(1.04)`;
    };
    window.addEventListener('mousemove', event => { tx = (event.clientX / innerWidth - .5) * -18; ty = (event.clientY / innerHeight - .5) * -12; paint(); }, { passive: true });
    window.addEventListener('scroll', () => { sy = scrollY * -.06; paint(); }, { passive: true });
    reduced.addEventListener('change', paint);
}
