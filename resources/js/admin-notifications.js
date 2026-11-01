import { navigate, feedback } from './admin-navigation';
const button = document.getElementById('admin-notification-button');
if (button) {
    const dialog = document.getElementById('admin-notifications'), count = button.querySelector('[data-notification-count]');
    const key = 'rdsa-notification-cursor-' + button.dataset.user;
    let cursor = null, oldMessages = null, loading = false;
    try { const saved = sessionStorage.getItem(key); if (saved !== null) cursor = Number(saved); } catch {}
    async function refresh() {
        if (loading || document.hidden) return; loading = true;
        try {
            const url = new URL(button.dataset.url); if (cursor !== null) url.searchParams.set('after', cursor);
            const response = await fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            if (!response.ok || response.redirected) return;
            const data = await response.json();
            const total = data.pending + data.messages; count.hidden = total === 0; count.textContent = total;
            button.setAttribute('aria-label', `${total} alertes : ${data.pending} demandes et ${data.messages} messages`);
            dialog.querySelector('[data-notification-summary]').textContent = `${data.pending} demande(s) à vérifier · ${data.messages} message(s) non lu(s)`;
            if (cursor !== null && data.items.length) {
                feedback(`${data.items.length} nouvelle(s) inscription(s). Ouvre la cloche pour les consulter.`, true);
                button.classList.add('has-new-alert');
                for (const item of [...data.items].reverse()) {
                    const link = document.createElement('a'); link.className = 'notification-item is-unread'; link.href = item.url;
                    link.textContent = `${item.name} a envoyé une demande de participation.`;
                    dialog.querySelector('[data-notification-items]').prepend(link);
                }
                while (dialog.querySelectorAll('.notification-item').length > 20) dialog.querySelector('.notification-item:last-child').remove();
            } else if ((oldMessages === null && total > 0) || (oldMessages !== null && data.messages > oldMessages)) {
                feedback(`${data.pending} demande(s) à vérifier et ${data.messages} message(s) non lu(s).`, true);
            }
            cursor = data.latest; oldMessages = data.messages;
            try { sessionStorage.setItem(key, String(cursor)); } catch {}
        } catch { /* Retry while keeping previously received notifications. */ }
        finally { loading = false; }
    }
    button.addEventListener('click', () => { dialog.showModal(); button.classList.remove('has-new-alert'); refresh(); });
    dialog.querySelector('[data-notification-close]').addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', event => { const a = event.target.closest('a'); if (a) { event.preventDefault(); a.classList.remove('is-unread'); dialog.close(); navigate(a.href); } });
    document.addEventListener('visibilitychange', refresh);
    document.addEventListener('rdsa:content-updated', refresh);
    setInterval(refresh, 10000); refresh();
}
