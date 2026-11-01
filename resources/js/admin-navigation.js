// Progressive navigation over the existing Laravel routes. HTML remains the source of truth.
const views = document.getElementById('views');
const modalRoot = document.getElementById('admin-modal-root');
const logout = document.querySelector('form[action$="/logout"]');
const base = new URL(logout?.action || '/admin/logout', location.href).pathname.replace(/\/logout\/?$/, '');
let activeURL = location.href, requestId = 0, pendingRead, writing = false, committing = false, confirmation;
let historyBusy = false, popAttempt = 0;
const entryID = () => globalThis.crypto?.randomUUID?.() || `${Date.now()}-${Math.random().toString(36).slice(2)}`;
let entry = history.state?.rdsa || { index: 0, id: entryID() };
const positions = new Map();
const baselines = new WeakMap();
const notice = document.createElement('div');
notice.className = 'live-notice'; notice.setAttribute('role', 'status'); notice.hidden = true; document.body.append(notice);
let noticeTimer;
export function feedback(text, temporary = false) {
    clearTimeout(noticeTimer); notice.textContent = text; notice.hidden = !text;
    if (temporary) noticeTimer = setTimeout(() => { notice.hidden = true; }, 4500);
}
function isAdminURL(value) {
    const url = new URL(value, location.href);
    return url.origin === location.origin && !url.username && !url.password && (url.pathname === base || url.pathname.startsWith(base + '/'))
        && ![base + '/login', base + '/logout'].includes(url.pathname) && !url.searchParams.has('download');
}
function editableForms() {
    return [...document.querySelectorAll('#views form,#admin-modal-root form')].filter(form => form.method.toLowerCase() === 'post' && !form.matches('[data-instant-action],#check-in-form'));
}
function signature(form) {
    return JSON.stringify([...form.elements].filter(field => field.name && !field.disabled && !field.readOnly && !['hidden', 'submit', 'button'].includes(field.type)).map(field => [
        field.name, ['checkbox', 'radio'].includes(field.type) ? field.checked : field.type === 'file' ? [...field.files].map(file => [file.name, file.size, file.lastModified]) : field.value,
    ]));
}
export function rememberForms(forceDirty = false) {
    for (const form of editableForms()) {
        if (!baselines.has(form)) baselines.set(form, signature(form));
        if (forceDirty && [...form.elements].some(field => field.name && !['password', 'hidden', 'submit', 'button'].includes(field.type) && !field.readOnly && field.value)) form.dataset.unsaved = 'true';
    }
}
function isDirty(except) {
    return editableForms().some(form => form !== except && (form.dataset.unsaved === 'true' || baselines.has(form) && signature(form) !== baselines.get(form)));
}
const leaveDialog = document.getElementById('admin-leave-confirm');
leaveDialog?.querySelectorAll('[data-leave]').forEach(button => button.addEventListener('click', () => leaveDialog.close(button.dataset.leave)));
leaveDialog?.addEventListener('cancel', event => { event.preventDefault(); leaveDialog.close('stay'); });
async function mayLeave(except, ignoreDirty = false) {
    if (writing || committing || document.querySelector('[data-sending="true"]')) { feedback('Une opération est en cours. Patiente avant de changer de rubrique.'); return false; }
    const event = new CustomEvent('rdsa:before-navigate', { cancelable: true });
    if (!document.dispatchEvent(event)) return false;
    if (ignoreDirty || !isDirty(except)) return true;
    if (!leaveDialog) return window.confirm('Des modifications ne sont pas enregistrées. Quitter cette rubrique ?');
    if (!confirmation) confirmation = new Promise(resolve => {
        const trigger = document.activeElement;
        leaveDialog.returnValue = 'stay';
        leaveDialog.addEventListener('close', () => {
            const discard = leaveDialog.returnValue === 'discard'; confirmation = null;
            if (!discard && trigger?.isConnected) trigger.focus({ preventScroll: true });
            resolve(discard);
        }, { once: true });
        leaveDialog.showModal(); leaveDialog.querySelector('[data-leave="stay"]').focus();
    });
    return confirmation;
}
function savePosition() { positions.set(entry.id, [scrollX, scrollY]); }
if (views && modalRoot) {
    history.replaceState({ ...history.state, rdsa: entry }, '', location.href);
    history.scrollRestoration = 'manual';
}
function parsePage(html) {
    const doc = new DOMParser().parseFromString(html, 'text/html');
    // Never execute scripts received through a partial navigation.
    doc.querySelectorAll('script').forEach(script => script.remove());
    return doc;
}
async function replacePage(doc, url, options = {}) {
    if (options.version !== requestId) return false;
    committing = true;
    try {
        const replacement = doc.getElementById('views'), nextModal = doc.getElementById('admin-modal-root');
        const token = doc.querySelector('input[name="_token"]')?.value;
        if (!replacement || !nextModal) throw new Error('Cette page ne peut pas être affichée. Réessaie ou recharge la page.');
        const previousModal = modalRoot.querySelector('[role="dialog"]');
        const opening = Boolean(nextModal.querySelector('[role="dialog"]'));
        const samePage = new URL(activeURL).pathname === new URL(url).pathname;
        const preserveViews = opening && samePage;
        const active = document.activeElement;
        const name = options.restoreFocus && views.contains(active) ? active.name : null;
        const selection = name && ['text', 'search', 'tel'].includes(active.type) ? [active.selectionStart, active.selectionEnd] : null;
        const waits = [];
        document.dispatchEvent(new CustomEvent('rdsa:before-content-replace', { detail: { preserveViews, waitUntil: promise => waits.push(promise) } }));
        await Promise.all(waits);
        if (previousModal && !opening && !matchMedia('(prefers-reduced-motion: reduce)').matches) await previousModal.animate?.([{ opacity: 1 }, { opacity: 0, transform: 'translateY(8px) scale(.985)' }], { duration: 160, easing: 'ease', fill: 'forwards' }).finished.catch(() => {});
        if (options.version !== requestId) return false;
        if (!preserveViews) views.replaceChildren(...replacement.childNodes);
        modalRoot.replaceChildren(...nextModal.childNodes);
        const nextNav = doc.getElementById('nav');
        if (nextNav) {
            const nav = document.getElementById('nav'), scroll = nav.scrollTop;
            nav.replaceChildren(...nextNav.childNodes); nav.scrollTop = scroll;
        }
        for (const id of ['title', 'crumb']) {
            const next = doc.getElementById(id); if (next) document.getElementById(id).textContent = next.textContent;
        }
        const nextProfile = doc.querySelector('.me'), profile = document.querySelector('.side>.me');
        if (nextProfile && profile) profile.replaceChildren(...nextProfile.childNodes);
        document.title = doc.title;
        const background = doc.querySelector('meta[name="rdsa-admin-background"]')?.content;
        if (background) document.documentElement.style.setProperty('--bgimg', `url(${JSON.stringify(background)})`);
        const logo = doc.querySelector('meta[name="rdsa-admin-logo"]')?.content;
        if (logo && document.querySelector('[data-admin-logo]')) document.querySelector('[data-admin-logo]').src = logo;
        // A successful password change may rotate the session: use the fresh CSRF token everywhere.
        if (token) document.querySelectorAll('input[name="_token"]').forEach(input => { input.value = token; });
        const urlObject = new URL(url);
        if (!options.pop) {
            savePosition();
            if (options.push) { entry = { index: entry.index + 1, id: entryID() }; history.pushState({ rdsa: entry }, '', url); }
            else history.replaceState({ ...history.state, rdsa: entry }, '', url);
        } else entry = options.state;
        activeURL = url;
        document.getElementById('side').classList.remove('open'); document.getElementById('scrim').classList.remove('on');
        document.dispatchEvent(new CustomEvent('rdsa:content-updated', { detail: { preserveViews } }));
        rememberForms(options.validation);
        if (name && !opening) {
            const field = [...views.querySelectorAll('[name]')].find(input => input.name === name);
            field?.focus({ preventScroll: true }); if (field && selection) field.setSelectionRange(...selection);
        } else if (!opening) {
            const target = views.querySelector('[role="alert"]') || document.getElementById('title');
            target.tabIndex = -1; target.focus({ preventScroll: true });
        }
        if (options.pop) window.scrollTo({ left: options.scroll?.[0] || 0, top: options.scroll?.[1] || 0, behavior: 'instant' });
        else if (urlObject.hash && document.getElementById(decodeURIComponent(urlObject.hash.slice(1)))) document.getElementById(decodeURIComponent(urlObject.hash.slice(1))).scrollIntoView();
        else if (!opening && !previousModal && !options.keepScroll) window.scrollTo({ top: 0, behavior: 'instant' });
        return true;
    } finally { committing = false; }
}
function requestError(response, mutation = false) {
    if (response.status === 419 || response.status === 401) return 'La session a expiré. Reconnecte-toi avant de réessayer.';
    if (response.status === 403) return 'Ton compte n’a pas accès à cette fonctionnalité.';
    if (response.status === 429) return 'Trop de demandes. Patiente un instant puis réessaie.';
    if (response.status === 413) return 'Le fichier est trop volumineux pour ce serveur. Choisis un fichier plus léger.';
    return mutation ? 'L’opération n’a pas pu être confirmée. Vérifie son résultat avant de réessayer.' : 'Le chargement a échoué. Tes informations sont conservées ; réessaie.';
}
export async function navigate(url, options = {}) {
    clearTimeout(filterTimer);
    if (historyBusy && !options.pop) { feedback('Le changement de rubrique est en cours. Patiente un instant.'); return false; }
    url = new URL(url, location.href).href;
    if (!isAdminURL(url)) { location.assign(url); return false; }
    if (!await mayLeave(null, options.ignoreDirty) || writing || committing) return false;
    pendingRead?.abort(); pendingRead = new AbortController(); const version = ++requestId;
    views.setAttribute('aria-busy', 'true'); feedback('');
    const timer = setTimeout(() => { if (version === requestId) feedback(options.message || 'Chargement…'); }, 450);
    try {
        const response = await fetch(url, { credentials: 'same-origin', signal: pendingRead.signal, referrer: activeURL, referrerPolicy: 'same-origin', headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest', 'X-RDSA-Navigation': '1' } });
        if (version !== requestId) return false;
        if (!isAdminURL(response.url)) {
            // Leaving was already confirmed; do not show a second native draft prompt.
            for (const form of editableForms()) { baselines.set(form, signature(form)); delete form.dataset.unsaved; }
            location.assign(response.url); return false;
        }
        if (!response.ok) throw new Error(requestError(response));
        const doc = parsePage(await response.text());
        if (version !== requestId) return false;
        const result = await replacePage(doc, response.url, { push: true, ...options, version });
        if (result) feedback('');
        return result;
    } catch (error) {
        if (error.name !== 'AbortError' && version === requestId) feedback(error instanceof TypeError ? 'Connexion interrompue. La rubrique actuelle et tes informations sont conservées.' : error.message);
        return false;
    } finally { clearTimeout(timer); if (version === requestId) views.removeAttribute('aria-busy'); }
}
function preserveFiles(form, doc) {
    for (const input of form.querySelectorAll('input[type="file"]')) {
        if (!input.files.length) continue;
        const next = [...doc.querySelectorAll('input[type="file"]')].find(field => field.name === input.name);
        if (next) next.replaceWith(input);
    }
}
export async function submitForm(form, submitter, override) {
    clearTimeout(filterTimer);
    if (!await mayLeave(form) || writing || committing) return false;
    pendingRead?.abort(); const version = ++requestId; writing = true;
    const data = new FormData(form);
    if (submitter?.name) data.append(submitter.name, submitter.value);
    if (override) data.set(override.name, override.value);
    const previousInert = form.inert; form.inert = true;
    form.setAttribute('aria-busy', 'true'); submitter?.setAttribute('aria-disabled', 'true'); submitter?.classList.add('action-pending'); feedback('');
    try {
        const response = await fetch(new URL(form.getAttribute('action') || activeURL, activeURL).href, { method: 'POST', credentials: 'same-origin', body: data, referrer: activeURL, referrerPolicy: 'same-origin', headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest', 'X-RDSA-Navigation': '1' } });
        if (!isAdminURL(response.url)) throw new Error('La session a expiré. Tes saisies sont conservées ; reconnecte-toi avant de réessayer.');
        if (!response.ok) throw new Error(requestError(response, true));
        const doc = parsePage(await response.text());
        const validation = Boolean(doc.querySelector('#views [role="alert"],#admin-modal-root [role="alert"],[aria-invalid="true"]'));
        if (validation) preserveFiles(form, doc);
        const result = await replacePage(doc, response.url, { version, push: response.url !== activeURL, keepScroll: true, validation });
        if (result) feedback(validation ? 'Vérifie les champs signalés. Les modifications ne sont pas encore enregistrées.' : 'Modification enregistrée.', !validation);
        return result && !validation;
    } catch (error) {
        feedback(error instanceof TypeError ? 'Connexion interrompue. Vérifie le résultat avant de renvoyer : l’opération peut avoir été enregistrée.' : error.message);
        return false;
    } finally { writing = false; form.inert = previousInert; form.removeAttribute('aria-busy'); submitter?.removeAttribute('aria-disabled'); submitter?.classList.remove('action-pending'); }
}
const getFilter = element => element.closest('#views form[method="GET"],#views form[method="get"]');
let filterTimer;
function filter(form) {
    const url = new URL(form.getAttribute('action') || activeURL, activeURL); url.search = new URLSearchParams(new FormData(form)).toString();
    return navigate(url, { push: false, restoreFocus: true, keepScroll: true, message: 'Recherche en cours…' });
}
document.addEventListener('input', event => {
    const form = getFilter(event.target);
    if (writing || committing || historyBusy || !form || event.isComposing || !event.target.matches('input:not([type="hidden"])')) return;
    pendingRead?.abort(); requestId++; views.removeAttribute('aria-busy'); feedback('');
    clearTimeout(filterTimer); filterTimer = setTimeout(() => { if (form.isConnected) filter(form); }, 320);
});
document.addEventListener('change', event => { const form = getFilter(event.target); if (!writing && !committing && !historyBusy && form && event.target.matches('select')) { clearTimeout(filterTimer); filter(form); } });
document.addEventListener('submit', event => {
    if (event.defaultPrevented) return;
    const form = event.target;
    if (!form.closest('#views,#admin-modal-root') || form.hasAttribute('data-native-navigation') || form.target && form.target !== '_self') return;
    if (form.matches('[data-contact-form],[data-thread-form],#check-in-form,[data-instant-action]')) return;
    if (form.method.toLowerCase() === 'get') { event.preventDefault(); clearTimeout(filterTimer); filter(form); }
    else if (isAdminURL(new URL(form.getAttribute('action') || activeURL, activeURL).href)) { event.preventDefault(); submitForm(form, event.submitter); }
});
document.addEventListener('change', async event => {
    const input = event.target, form = input.closest('[data-instant-action]');
    if (!form || input.type !== 'checkbox') return;
    const checked = input.checked; input.disabled = true;
    const result = await submitForm(form, null, { name: input.name, value: checked ? '1' : '0' });
    input.disabled = false;
    if (!result && input.isConnected) input.checked = !checked;
});
document.addEventListener('click', event => {
    if (event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
    const link = event.target.closest('a[href]');
    if (!link || !link.closest('#nav,#views,#admin-modal-root') || link.hasAttribute('download') || link.target && link.target !== '_self' || !isAdminURL(link.href)) return;
    const url = new URL(link.href), current = new URL(activeURL);
    event.preventDefault(); clearTimeout(filterTimer);
    if (historyBusy) { feedback('Le changement de rubrique est en cours. Patiente un instant.'); return; }
    if (url.pathname === current.pathname && url.search === current.search && url.hash) {
        const target = document.getElementById(decodeURIComponent(url.hash.slice(1)));
        if (target && url.href === activeURL) { target.scrollIntoView(); return; }
        if (target) { savePosition(); entry = { index: entry.index + 1, id: entryID() }; history.pushState({ rdsa: entry }, '', url); activeURL = url.href; target.scrollIntoView(); }
        return;
    }
    navigate(link.href, { push: true, keepScroll: Boolean(link.closest('.pgn,.calendar-nav,form[method="GET"]')) });
});
let restoringHistory = false;
window.addEventListener('popstate', async event => {
    if (restoringHistory) { restoringHistory = false; historyBusy = false; return; }
    const attempt = ++popAttempt; historyBusy = true;
    const target = event.state?.rdsa;
    if (!target) { location.reload(); return; }
    savePosition();
    const previous = new URL(activeURL), next = new URL(location.href);
    if (previous.pathname === next.pathname && previous.search === next.search) { entry = target; activeURL = next.href; historyBusy = false; window.scrollTo(...(positions.get(entry.id) || [0, 0])); return; }
    const delta = entry.index - target.index;
    const ok = await navigate(location.href, { pop: true, state: target, scroll: positions.get(target.id), keepScroll: true });
    if (attempt !== popAttempt) return;
    if (!ok && delta) { restoringHistory = true; history.go(delta); } else historyBusy = false;
});
window.addEventListener('beforeunload', event => { if (writing || isDirty()) { event.preventDefault(); event.returnValue = ''; } });
queueMicrotask(() => rememberForms());
