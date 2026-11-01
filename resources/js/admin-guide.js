import { setupDialog } from './ui';
import { navigate } from './admin-navigation';
const dialog = document.getElementById('rdsa-admin-guide');
if (dialog) {
    const modal = setupDialog(dialog), select = dialog.querySelector('select');
    const templates = [...dialog.querySelectorAll('template[data-guide-template]')];
    const content = dialog.querySelector('[data-guide-content]');
    const key = `rdsa-admin-guide-v1-${dialog.dataset.guideUser}`;
    const render = () => {
        const index = select.selectedIndex;
        content.replaceChildren(templates[index].content.cloneNode(true));
        dialog.querySelector('[data-guide-progress]').textContent = `${index + 1} / ${templates.length}`;
        dialog.querySelector('[data-guide-previous]').disabled = index === 0;
        dialog.querySelector('[data-guide-next]').textContent = index === templates.length - 1 ? 'Terminer' : 'Suivant';
        content.scrollTop = 0;
    };
    const open = (trigger, welcome = false) => {
        const match = welcome ? 0 : (templates.map((t, index) => ({ path: t.dataset.guidePath, index })).filter(t => t.path && (location.pathname === t.path || location.pathname.startsWith(t.path + '/'))).sort((a, b) => b.path.length - a.path.length)[0]?.index ?? 0);
        select.selectedIndex = Math.max(0, match); render(); modal.open(trigger);
    };
    document.addEventListener('click', event => {
        const trigger = event.target.closest('[data-admin-guide-open]');
        if (!trigger) return;
        event.preventDefault(); event.stopImmediatePropagation(); open(trigger);
    }, true);
    select.addEventListener('change', render);
    dialog.querySelector('[data-guide-previous]').addEventListener('click', () => { select.selectedIndex--; render(); });
    dialog.querySelector('[data-guide-next]').addEventListener('click', () => {
        if (select.selectedIndex === templates.length - 1) { modal.close(); return; }
        select.selectedIndex++; render();
    });
    dialog.addEventListener('click', async event => {
        const link = event.target.closest('[data-guide-go]'); if (!link) return;
        event.preventDefault(); event.stopPropagation(); await modal.close(); navigate(link.href, { push: true });
    });
    dialog.addEventListener('close', () => { try { localStorage.setItem(key, 'seen'); } catch {} });
    document.addEventListener('rdsa:before-content-replace', () => { if (dialog.open) dialog.close(); });
    const autoOpen = () => {
        let seen = false; try { seen = localStorage.getItem(key) === 'seen'; } catch { return; }
        if (seen) return;
        setTimeout(() => {
            if (!document.querySelector('dialog[open],[role="dialog"]') && !document.activeElement?.matches('input,textarea,select')) open(null, true);
        }, 300);
    };
    if (document.documentElement.dataset.rdsaBootReady === 'true') queueMicrotask(autoOpen);
    else window.addEventListener('rdsa:boot-ready', autoOpen, { once: true });
}
