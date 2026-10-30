import { setupDialog } from './ui';
import { animateContent } from './interactions';

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
        link.title = link.dataset.label || link.textContent.trim();
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
    const entries = [...document.querySelectorAll('#nav a')].map(link => ({
        label: link.childNodes.length ? [...link.childNodes].filter(node => node.nodeType === Node.TEXT_NODE).map(node => node.textContent).join('').trim() : link.textContent.trim(),
        icon: link.querySelector('i')?.className || 'bi bi-arrow-right',
        run: () => location.assign(link.href),
    }));
    if (document.querySelector('#nav a[href*="/media"]')) entries.push({ label: 'Ajouter un média', icon: 'bi bi-plus-lg', run: () => {
        const link = document.querySelector('#nav a[href*="/media"]'); if (link) location.assign(`${link.href}?create=1`);
    } });
    entries.push({ label: 'Changer de thème', icon: 'bi bi-moon-stars', run: () => document.getElementById('theme')?.click() });
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
        matches = entries.filter(entry => normalize(entry.label).includes(normalize(input.value.trim())));
        results.replaceChildren(); selected = 0;
        for (const [index, entry] of matches.entries()) {
            const button = document.createElement('button'); button.type = 'button';
            const icon = document.createElement('i'); icon.className = entry.icon; icon.setAttribute('aria-hidden', 'true');
            button.append(icon, document.createTextNode(entry.label));
            button.addEventListener('click', () => { dialog.close(); entry.run(); });
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
        if (event.key === 'Enter' && matches[selected]) { event.preventDefault(); dialog.close(); matches[selected].run(); }
    });
    document.addEventListener('keydown', event => {
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') { event.preventDefault(); if (commands.open) dialog.close(); else open(); }
    });
}

// The server-rendered editors keep their routes and gain focus, Escape and exit motion.
const overlay = document.querySelector('.ov.on'), editor = overlay?.querySelector('[role="dialog"]');
if (editor) {
    const exit = editor.querySelector('.media-editor-close') || editor.querySelector('.act a');
    let closing = false;
    const previousOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    const background = [...document.body.children].filter(node => !node.contains(editor) && !['SCRIPT', 'STYLE', 'LINK'].includes(node.tagName));
    background.forEach(node => { node.inert = true; });
    const focusable = () => [...editor.querySelectorAll('a[href],button,input,select,textarea,[tabindex="0"]')].filter(node => !node.disabled && !node.hidden && node.getClientRects().length);
    editor.tabIndex = -1;
    (editor.querySelector('[aria-invalid="true"]') || editor).focus({ preventScroll: true });
    async function close(event) {
        if (!exit || closing || event?.ctrlKey || event?.metaKey || event?.shiftKey || event?.altKey) return;
        event?.preventDefault(); closing = true;
        if (!matchMedia('(prefers-reduced-motion: reduce)').matches) await editor.animate?.([{ opacity: 1, transform: 'scale(1)' }, { opacity: 0, transform: 'translateY(8px) scale(.985)' }], { duration: 180, fill: 'forwards', easing: 'ease-in' }).finished.catch(() => {});
        document.body.style.overflow = previousOverflow;
        location.assign(exit.href);
    }
    exit?.addEventListener('click', close);
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') close(event);
        if (event.key !== 'Tab') return;
        const items = focusable(), first = items[0], last = items.at(-1);
        if (!first) { event.preventDefault(); editor.focus(); return; }
        if (event.shiftKey && (document.activeElement === first || document.activeElement === editor)) { event.preventDefault(); last.focus(); }
        else if (!event.shiftKey && (document.activeElement === last || document.activeElement === editor)) { event.preventDefault(); first.focus(); }
    });
}

document.addEventListener('rdsa:content-updated', () => {
    const root = document.getElementById('views');
    animateContent(root);
    if (document.activeElement === document.body) root.querySelector('#calendar-month-label')?.focus({ preventScroll: true });
});
document.addEventListener('keydown', event => {
    if (event.key !== 'Escape' || !document.getElementById('side')?.classList.contains('open')) return;
    document.getElementById('side').classList.remove('open'); document.getElementById('scrim')?.classList.remove('on');
    document.getElementById('burger')?.focus();
});

// Content sections are plain HTML disclosures, usable without JavaScript.
const contentForm = document.getElementById('site-content-form');
if (contentForm) {
    contentForm.addEventListener('invalid', event => {
        let section = event.target.closest('details');
        while (section) { section.open = true; section = section.parentElement.closest('details'); }
    }, true);
    contentForm.addEventListener('change', event => {
        if (!event.target.matches('[data-content-logo]')) return;
        const preview = contentForm.querySelector('[data-content-logo-preview]');
        if (preview) preview.src = event.target.dataset.src;
    });
    const selectedLogo = contentForm.querySelector('[data-content-logo]:checked');
    if (selectedLogo) selectedLogo.dispatchEvent(new Event('change', { bubbles: true }));
}
