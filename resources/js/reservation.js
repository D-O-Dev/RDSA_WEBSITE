const form = document.querySelector('[data-reservation-form]');
if (form) {
    const feedback = form.querySelector('[data-reservation-feedback]');
    let current = 1;
    function validateInfo() {
        for (const name of ['full_name', 'phone']) if (!form.elements[name].reportValidity()) return false;
        if (!['0', '1'].includes(form.elements.has_attended_before.value)) { feedback.textContent = 'Indique si tu as déjà participé à une édition précédente.'; return false; }
        if (form.elements.has_attended_before.value === '1' && !form.querySelector('[name="previous_edition_ids[]"]:checked')) { feedback.textContent = 'Sélectionne au moins une édition précédente.'; return false; }
        return true;
    }
    function chooseHistory(value) {
        form.elements.has_attended_before.value = value;
        form.querySelectorAll('[data-history]').forEach(button => { const active = button.dataset.history === (value === '1' ? 'yes' : 'no'); button.classList.toggle('active', active); button.setAttribute('aria-pressed', String(active)); button.querySelector('i').className = active ? 'bi bi-check-circle-fill' : 'bi bi-circle'; });
        form.querySelector('#previous-editions-container').hidden = value !== '1';
        form.querySelectorAll('[name="previous_edition_ids[]"]').forEach(input => input.disabled = value !== '1');
    }
    form.querySelectorAll('[data-history]').forEach(button => button.addEventListener('click', () => { chooseHistory(button.dataset.history === 'yes' ? '1' : '0'); form.elements.review_confirmed.checked = false; }));
    if (['0', '1'].includes(form.elements.has_attended_before.value)) chooseHistory(form.elements.has_attended_before.value);
    window.nextParticipationStep = step => {
        if (![1, 2].includes(step) || form.dataset.sending) return;
        feedback.textContent = '';
        if (step === 2 && !validateInfo()) return;
        if (step === 2) {
            document.getElementById('review_name').textContent = form.elements.full_name.value.trim();
            document.getElementById('review_phone').textContent = form.elements.phone.value.trim();
            document.getElementById('review_history').textContent = form.elements.has_attended_before.value === '1' ? Array.from(form.querySelectorAll('[name="previous_edition_ids[]"]:checked')).map(input => input.dataset.editionName).join(', ') : 'Première participation';
        } else form.elements.review_confirmed.checked = false;
        current = step;
        form.querySelectorAll('.participation-form-step').forEach(panel => panel.classList.toggle('active', panel.id === `participation-step-${step}`));
        form.querySelectorAll('[data-progress-step]').forEach(panel => panel.classList.toggle('active', Number(panel.dataset.progressStep) <= step));
        const panel = document.getElementById(`participation-step-${step}`);
        const heading = panel.querySelector('h3'); heading.tabIndex = -1; heading.focus({ preventScroll: true });
        if (form.getBoundingClientRect().top < 0 || form.getBoundingClientRect().top > innerHeight / 2) form.scrollIntoView({ behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth', block: 'start' });
    };
    form.addEventListener('input', event => { if (event.target !== form.elements.review_confirmed) form.elements.review_confirmed.checked = false; });
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (form.dataset.sending) return;
        if (current !== 2) { window.nextParticipationStep(2); return; }
        if (!validateInfo()) { window.nextParticipationStep(1); return; }
        if (!form.elements.review_confirmed.checked) { feedback.textContent = 'Confirme que tu as vérifié ton nom et ton téléphone.'; form.elements.review_confirmed.focus(); return; }
        const button = form.querySelector('[type=submit]'), label = button.innerHTML;
        form.dataset.sending = 'true'; button.disabled = true; button.textContent = 'ENVOI EN COURS…'; feedback.textContent = '';
        try {
            const response = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' }, credentials: 'same-origin', redirect: 'error' });
            if (response.status === 419) throw new Error('Le formulaire a expiré. Recharge la page.');
            if (response.status === 429) throw new Error('La limite de demandes est atteinte. Patiente avant de réessayer.');
            const data = await response.json();
            if (!response.ok) throw new Error(data.errors ? Object.values(data.errors).flat()[0] : 'La demande n’a pas pu être enregistrée. Réessaie.');
            window.location.assign(data.url);
        } catch (error) { feedback.textContent = error instanceof TypeError ? 'Connexion interrompue. Tes informations sont conservées. Réessaie.' : error.message; button.disabled = false; button.innerHTML = label; delete form.dataset.sending; }
    });
}
const pending = document.querySelector('[data-reservation-status]');
if (pending) {
    let loading = false, done = false;
    const refresh = async () => {
        if (loading || done || document.hidden) return; loading = true;
        try {
            const response = await fetch(pending.dataset.url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin', redirect: 'error' });
            if (!response.ok) return;
            const data = await response.json();
            if (data.url) { done = true; window.location.assign(data.url); }
            else if (['rejected', 'cancelled'].includes(data.status)) { done = true; window.location.reload(); }
        } catch { /* Keep the request reference available when offline. */ }
        finally { loading = false; }
    };
    refresh(); setInterval(refresh, 10000); document.addEventListener('visibilitychange', refresh);
}
