import { setupDialog } from './ui';
import { animateContent, releaseContent } from './interactions';
import { navigate, feedback } from './admin-navigation';

const theme = document.getElementById('theme');
function syncThemeButton() {
    const dark = document.documentElement.dataset.theme === 'dark';
    theme?.setAttribute('aria-label', dark ? 'Passer au thème clair' : 'Passer au thème sombre');
    const icon = theme?.querySelector('i'); if (icon) icon.className = dark ? 'bi bi-moon-stars' : 'bi bi-sun';
}
syncThemeButton();
theme?.addEventListener('click', syncThemeButton);

const collapse = document.getElementById('collapse');
function setCollapsed(value) {
    document.body.classList.toggle('col', value);
    collapse?.setAttribute('aria-expanded', String(!value));
    collapse?.setAttribute('aria-label', value ? 'Développer le menu' : 'Réduire le menu');
    document.querySelectorAll('#nav a').forEach(link => {
        link.title = link.querySelector('.nav-label')?.textContent.trim() || link.dataset.label || link.textContent.trim();
        link.setAttribute('aria-label', link.title);
    });
}
try { setCollapsed(localStorage.getItem('rdsa-admin-menu-collapsed') === '1'); } catch { setCollapsed(false); }
collapse?.addEventListener('click', () => {
    const value = !document.body.classList.contains('col'); setCollapsed(value);
    try { localStorage.setItem('rdsa-admin-menu-collapsed', value ? '1' : '0'); } catch { /* Preference is optional. */ }
});

const commands = document.getElementById('admin-commands');
if (commands) {
    const dialog = setupDialog(commands), input = commands.querySelector('input');
    const results = commands.querySelector('#command-results');
    const normalize = text => text.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
    function commandEntries() {
        const entries = [...document.querySelectorAll('#nav a')].map(link => ({
        label: link.querySelector('.nav-label')?.textContent.trim() || link.textContent.trim(),
        icon: link.querySelector('i')?.className || 'bi bi-arrow-right',
        run: () => navigate(link.href),
    }));
    if (document.querySelector('#nav a[href*="/media"]')) entries.push({ label: 'Ajouter un média', icon: 'bi bi-plus-lg', run: () => {
        const link = document.querySelector('#nav a[href*="/media"]'); if (link) navigate(`${link.href}?create=1`);
    } });
    entries.push({ label: 'Changer de thème', icon: 'bi bi-moon-stars', run: () => document.getElementById('theme')?.click() });
        return entries;
    }
    let selected = 0, matches = [];
    function select(index) {
        selected = index;
        [...results.children].forEach((button, position) => {
            button.classList.toggle('on', position === selected);
            if (position === selected) button.setAttribute('aria-current', 'true'); else button.removeAttribute('aria-current');
        });
        results.children[selected]?.scrollIntoView({ block: 'nearest' });
    }
    function draw() {
        matches = commandEntries().filter(entry => normalize(entry.label).includes(normalize(input.value.trim())));
        results.replaceChildren(); selected = 0;
        for (const [index, entry] of matches.entries()) {
            const button = document.createElement('button'); button.type = 'button';
            const icon = document.createElement('i'); icon.className = entry.icon; icon.setAttribute('aria-hidden', 'true');
            button.append(icon, document.createTextNode(entry.label));
            button.addEventListener('click', async () => { await dialog.close(); entry.run(); });
            button.addEventListener('pointermove', () => select(index)); results.append(button);
        }
        if (!matches.length) { const empty = document.createElement('p'); empty.className = 'empty'; empty.textContent = 'Aucun résultat.'; results.append(empty); }
        commands.querySelector('[role="status"]').textContent = `${matches.length} résultat(s)`;
        select(0);
    }
    function open() {
        if (document.querySelector('.ov.on')) return;
        input.value = ''; draw(); dialog.open(document.getElementById('kbtn')); input.focus();
    }
    document.getElementById('kbtn')?.addEventListener('click', open);
    input.addEventListener('input', draw);
    input.addEventListener('keydown', event => {
        if (['ArrowDown', 'ArrowUp'].includes(event.key)) {
            event.preventDefault(); select(Math.max(0, Math.min(matches.length - 1, selected + (event.key === 'ArrowDown' ? 1 : -1))));
        }
        if (event.key === 'Enter' && matches[selected]) { event.preventDefault(); const entry = matches[selected]; dialog.close().then(() => entry.run()); }
    });
    document.addEventListener('keydown', event => {
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') { event.preventDefault(); if (commands.open) dialog.close(); else open(); }
    });
}

// Mount per-view controls once and release their listeners before replacing them.
let releaseEditor = () => {}, releaseScanner, scannerVersion = 0;
function mountEditor() {
    releaseEditor();
    const overlay = document.querySelector('#admin-modal-root .ov.on'), editor = overlay?.querySelector('[role="dialog"]');
    if (!editor) { releaseEditor = () => {}; return; }
    const exit = editor.querySelector('.media-editor-close') || editor.querySelector('.act a');
    const listeners = new AbortController(), previousOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    const background = [...document.body.children].filter(node => !node.contains(editor) && !node.classList.contains('live-notice') && !['SCRIPT', 'STYLE', 'LINK', 'DIALOG'].includes(node.tagName));
    const previousInert = background.map(node => node.inert);
    background.forEach(node => { node.inert = true; });
    const focusable = () => [...editor.querySelectorAll('a[href],button,input,select,textarea,[tabindex="0"]')].filter(node => !node.disabled && !node.hidden && node.getClientRects().length);
    editor.tabIndex = -1;
    const focusTarget = editor.querySelector('[aria-invalid="true"]') || editor.querySelector('[role="alert"]') || editor;
    if (!focusTarget.matches('input,select,textarea,button,a[href]')) focusTarget.tabIndex = -1;
    focusTarget.focus({ preventScroll: true });
    document.addEventListener('keydown', event => {
        if (document.querySelector('#admin-leave-confirm[open]')) return;
        if (event.key === 'Escape' && exit) { event.preventDefault(); exit.click(); }
        if (event.key !== 'Tab') return;
        const items = focusable(), first = items[0], last = items.at(-1);
        if (!first) { event.preventDefault(); editor.focus(); return; }
        if (event.shiftKey && (document.activeElement === first || document.activeElement === editor)) { event.preventDefault(); last.focus(); }
        else if (!event.shiftKey && (document.activeElement === last || document.activeElement === editor)) { event.preventDefault(); first.focus(); }
    }, { signal: listeners.signal });
    releaseEditor = () => {
        listeners.abort(); document.body.style.overflow = previousOverflow;
        background.forEach((node, index) => { node.inert = previousInert[index]; });
    };
}
async function mountScannerPage() {
    const version = ++scannerVersion, form = document.getElementById('check-in-form');
    if (!form) return;
    try {
        const module = await import('./admin-scan');
        if (version !== scannerVersion || !form.isConnected) return;
        releaseScanner = module.mountScanner(form);
    } catch { if (form.isConnected) feedback('Le contrôle n’a pas pu être chargé. Recharge cette rubrique.'); }
}
function mountContentEditor() {
    const selected = document.querySelector('#site-content-form [data-content-logo]:checked');
    if (selected) selected.dispatchEvent(new Event('change', { bubbles: true }));
}
function mountPage() {
    setCollapsed(document.body.classList.contains('col'));
    mountEditor(); mountContentEditor(); mountScannerPage(); animateContent(document.getElementById('views'));
}
document.addEventListener('rdsa:before-content-replace', event => {
    releaseEditor(); scannerVersion++;
    if (!event.detail.preserveViews) releaseContent(document.getElementById('views'));
    if (releaseScanner) { event.detail.waitUntil(releaseScanner()); releaseScanner = null; }
});
document.addEventListener('rdsa:content-updated', mountPage);
mountPage();

document.addEventListener('keydown', event => {
    if (event.key !== 'Escape' || !document.getElementById('side')?.classList.contains('open')) return;
    document.getElementById('side').classList.remove('open'); document.getElementById('scrim')?.classList.remove('on');
    document.getElementById('burger')?.focus();
});

// Delegation keeps these handlers valid when the content editor is loaded later.
document.addEventListener('invalid', event => {
    if (!event.target.closest('#site-content-form')) return;
    let section = event.target.closest('details');
    while (section) { section.open = true; section = section.parentElement.closest('details'); }
}, true);
document.addEventListener('change', event => {
    if (!event.target.matches('[data-content-logo]')) return;
    const preview = document.querySelector('[data-content-logo-preview]');
    if (preview) preview.src = event.target.dataset.src;
});
mountContentEditor();
