const trigger = document.querySelector('[data-announcements-url]');
if (trigger && !document.body.dataset.designPreview) {
    const toast = document.createElement('aside'); toast.className = 'public-announcement-alert'; toast.hidden = true;
    toast.setAttribute('role', 'status'); toast.setAttribute('aria-live', 'polite');
    const open = document.createElement('button'), close = document.createElement('button');
    open.type = close.type = 'button'; close.textContent = '×'; close.setAttribute('aria-label', 'Fermer cette alerte');
    toast.append(open, close); document.body.append(toast);
    let current = '', loading = false, seen = '', read = new Set(), items = [];
    const key = item => `${item.id}:${item.updated_at}`;
    try { seen = sessionStorage.getItem('rdsa-announcements-seen') || ''; read = new Set(JSON.parse(sessionStorage.getItem('rdsa-announcements-read') || '[]')); } catch {}
    function dismissToast() { seen = current; toast.hidden = true; try { sessionStorage.setItem('rdsa-announcements-seen', seen); } catch {} }
    function updateBadge() {
        const unread = items.filter(item => !read.has(key(item))).length;
        let badge = trigger.querySelector('.questions-info-count');
        if (!badge) { badge = document.createElement('span'); badge.className = 'questions-info-count'; trigger.append(badge); }
        badge.hidden = unread === 0; badge.textContent = unread;
        badge.setAttribute('aria-label', `${unread} annonce(s) non lue(s)`);
        trigger.classList.toggle('has-unread-announcements', unread > 0);
        const label = trigger.querySelector('span:not(.questions-info-count)');
        if (label) label.textContent = items.length ? 'Infos & questions' : 'Une question ?';
    }
    close.addEventListener('click', dismissToast);
    open.addEventListener('click', () => { trigger.click(); dismissToast(); });
    trigger.addEventListener('click', dismissToast);
    async function refresh() {
        if (loading || document.hidden) return; loading = true;
        try {
            const response = await fetch(trigger.dataset.announcementsUrl, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            if (!response.ok) return;
            ({ items } = await response.json()); current = JSON.stringify(items.map(item => [item.id, item.updated_at]));
            let box = document.querySelector('.questions-announcements');
            if (items.length && !box) { box = document.createElement('div'); box.className = 'questions-announcements'; document.querySelector('.questions-dialog-intro').before(box); }
            if (box && box.dataset.version !== current) {
                box.replaceChildren(); box.hidden = !items.length; box.dataset.version = current;
                const h = document.createElement('h3'); h.id = 'questions-announcements-title'; h.textContent = 'Les informations de l’équipe'; box.append(h);
                for (const item of items) {
                    const article = document.createElement('article'), title = document.createElement('h4'), body = document.createElement('p'), dot = document.createElement('span'), mark = document.createElement('button');
                    article.className = 'questions-announcement'; article.classList.toggle('is-unread', !read.has(key(item)));
                    dot.className = 'unread-dot'; dot.setAttribute('role', 'img'); dot.setAttribute('aria-label', 'Annonce non lue'); dot.hidden = read.has(key(item));
                    title.append(dot, document.createTextNode(item.title)); body.textContent = item.body;
                    mark.type = 'button'; mark.className = 'announcement-read-toggle'; mark.textContent = read.has(key(item)) ? 'Lue' : 'Marquer comme lue'; mark.disabled = read.has(key(item));
                    mark.addEventListener('click', () => {
                        read.add(key(item)); article.classList.remove('is-unread'); dot.hidden = true; mark.disabled = true; mark.textContent = 'Lue';
                        try { sessionStorage.setItem('rdsa-announcements-read', JSON.stringify([...read].slice(-200))); } catch {}
                        updateBadge();
                    });
                    article.append(title, body, mark); box.append(article);
                }
            }
            updateBadge();
            if (items.some(item => !read.has(key(item))) && current !== seen) { open.textContent = `Nouvelle information RDSA · ${items[0].title} — Voir les annonces`; toast.hidden = false; }
            else toast.hidden = true;
        } catch { /* Preserve the information and reading state while offline. */ }
        finally { loading = false; }
    }
    setTimeout(refresh, 800); setInterval(refresh, 30000); document.addEventListener('visibilitychange', refresh);
}
