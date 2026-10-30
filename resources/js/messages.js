import { setupDialog, reveal } from './ui';
const questionsDialog = document.getElementById('questions-dialog');
if (questionsDialog) {
    const questions = setupDialog(questionsDialog);
    document.querySelectorAll('[data-questions-open]').forEach(button => button.addEventListener('click', event => {
        event.preventDefault(); questions.open(button);
    }));
    if (questionsDialog.querySelector('[data-form-feedback]')?.textContent.trim()) questions.open();
}

async function refreshConversation() {
    const target = document.querySelector('[data-conversation]');
    if (!target || document.hidden || target.dataset.loading === 'true') return;
    target.dataset.loading = 'true';
    try {
        const response = await fetch(target.dataset.threadUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        if (!response.ok || response.redirected) return;
        const data = await response.json();
        if (target.isConnected && data.version !== target.dataset.version) {
            target.innerHTML = data.html; target.dataset.version = data.version; reveal(target);
            document.dispatchEvent(new Event('rdsa:messages-updated'));
        }
    } catch { /* Garder la conversation et le brouillon en cas de coupure réseau. */ }
    finally { delete target.dataset.loading; }
}
setInterval(refreshConversation, 15000);
document.addEventListener('visibilitychange', () => { if (!document.hidden) refreshConversation(); });

document.addEventListener('submit', async event => {
    const form = event.target.closest('[data-contact-form], [data-thread-form]');
    if (!form) return;
    event.preventDefault();
    if (form.dataset.sending === 'true') return;
    form.dataset.sending = 'true';
    const feedback = form.querySelector('[data-form-feedback]');
    const button = form.querySelector('[type="submit"]'), original = button.innerHTML;
    const payload = new FormData(form);
    button.disabled = true; button.textContent = 'Envoi en cours…'; feedback.replaceChildren();
    try {
        const response = await fetch(form.action, { method: 'POST', body: payload, headers: { Accept: 'application/json' }, credentials: 'same-origin', redirect: 'error' });
        if (response.status === 419) throw new Error('Le formulaire a expiré. Recharge la page avant de réessayer.');
        if (response.status === 429) throw new Error('Plusieurs messages ont déjà été envoyés. Patiente une minute.');
        let data;
        try { data = await response.json(); } catch { throw new Error('Le service est momentanément indisponible. Ton texte est conservé.'); }
        if (!response.ok) throw new Error(data.errors ? Object.values(data.errors).flat()[0] : 'Ton message n’a pas pu être transmis. Réessaie.');
        feedback.classList.remove('has-error'); feedback.textContent = data.message;
        if (form.matches('[data-contact-form]')) {
            form.reset(); form.elements.submission_token.value = data.next_token;
            const link = document.createElement('a'); link.href = data.conversation_url;
            link.textContent = 'Ouvrir ma conversation privée · ' + data.reference;
            feedback.append(document.createElement('br'), link);
        } else { form.elements.body.value = ''; await refreshConversation(); }
    } catch (error) {
        feedback.classList.add('has-error');
        feedback.textContent = error instanceof TypeError ? 'Connexion interrompue. Ton texte est conservé ; vérifie ta connexion avant de réessayer.' : error.message;
    } finally { button.disabled = false; button.innerHTML = original; delete form.dataset.sending; }
});
