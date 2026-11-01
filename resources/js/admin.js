import './push.js';
import './admin-guide.js';
import './admin-account.js';
import './admin-appearance.js';
import './admin-designs.js';
import './admin-notifications.js';
import './messages.js';
import './admin-images.js';
import './admin-experience.js';
import './admin-navigation';
import '../css/admin-interactions.css';

document.documentElement.classList.add('admin-live');
function syncMediaFields() {
    const kind = document.getElementById('media-kind');
    if (!kind) return;
    const video = kind.value === 'video';
    const photoLink = document.querySelector('[data-photo-link-field]');
    if (photoLink) { photoLink.hidden = video; photoLink.querySelector('input').disabled = video; }
    document.querySelectorAll('[data-video-field]').forEach(field => { field.hidden = !video; field.querySelectorAll('input,select').forEach(input => input.disabled = !video); });
    document.querySelectorAll('[data-photo-file-field]').forEach(field => {
        field.hidden = video;
        field.querySelectorAll('input,select').forEach(input => { input.disabled = video; });
    });
    if (video) document.getElementById('media-upload').value = '';
    document.getElementById('media-video-url').required = video;
}
function mountMedia() {
    syncMediaFields();
    const file = document.getElementById('media-upload');
    if (file?.files.length) file.dispatchEvent(new Event('change', { bubbles: true }));
}
document.addEventListener('rdsa:content-updated', () => { mountMedia(); refreshCount(); });
document.addEventListener('rdsa:before-content-replace', () => {
    if (previewUrl) { URL.revokeObjectURL(previewUrl); previewUrl = null; }
});
syncMediaFields();
// Keep the link field visible; pasting a video link selects its type automatically.
document.addEventListener('input', event => {
    if (event.target.id !== 'media-video-url' || !event.target.value.trim()) return;
    const kind = document.getElementById('media-kind');
    if (kind) { kind.value = 'video'; syncMediaFields(); }
});
document.addEventListener('change', event => {
    if (event.target.id !== 'media-kind') return;
    if (event.target.value === 'photo') {
        const link = document.getElementById('media-video-url');
        if (link) link.value = '';
    }
    syncMediaFields();
});
async function refreshCount() {
    const badge = document.querySelector('[data-message-count]'); if (!badge || document.hidden) return;
    try {
        const response = await fetch(badge.dataset.url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
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

document.addEventListener('input', event => {
    if (event.target.id !== 'media-image-url' || !event.target.value.trim()) return;
    const kind = document.getElementById('media-kind'); kind.value = 'photo';
    document.getElementById('media-video-url').value = '';
    document.getElementById('media-existing').value = '';
    syncMediaFields();
    const target = document.querySelector('[data-media-preview]');
    if (target && /^https:\/\//i.test(event.target.value.trim())) {
        const img = new Image(); img.alt = 'Aperçu de la photo'; img.referrerPolicy = 'no-referrer';
        img.src = event.target.value.trim(); target.replaceChildren(img);
    }
});
document.addEventListener('change', event => {
    if (event.target.id === 'media-existing' && event.target.value) document.getElementById('media-image-url').value = '';
});
