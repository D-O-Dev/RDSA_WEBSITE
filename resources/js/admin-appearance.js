let dispose;
function mount() {
    dispose?.();
    const form = document.getElementById('appearance-form'); if (!form) return;
    const frame = form.querySelector('[data-appearance-preview]'), stage = frame.parentElement, feedback = form.querySelector('[data-preview-feedback]');
    const lifecycle = new AbortController(); let pending, timer, width = 1280, version = 0;
    function size() { const scale = Math.min(1, stage.clientWidth / width); frame.style.width = width + 'px'; frame.style.height = Math.ceil(stage.clientHeight / scale) + 'px'; frame.style.transform = `scale(${scale})`; frame.style.marginLeft = Math.max(0, (stage.clientWidth - width * scale) / 2) + 'px'; }
    const observer = new ResizeObserver(size); observer.observe(stage);
    async function preview() {
        if (!form.checkValidity()) { feedback.textContent = 'Vérifie les champs pour actualiser l’aperçu.'; return; }
        pending?.abort(); pending = new AbortController(); const current = ++version;
        const data = new FormData(form); data.delete('_method');
        feedback.textContent = 'Mise à jour de l’aperçu…'; frame.setAttribute('aria-busy', 'true');
        try {
            const response = await fetch(form.dataset.previewUrl, { method: 'POST', credentials: 'same-origin', body: data, signal: pending.signal, redirect: 'error', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            if (response.status === 422) { const errors = await response.json(); throw new Error(Object.values(errors.errors || {}).flat()[0] || 'Vérifie les réglages de l’apparence.'); }
            if ([401,419].includes(response.status)) throw new Error('La session a expiré. Reconnecte-toi avant de réessayer.');
            if (!response.ok || response.headers.get('X-RDSA-Preview') !== '1') throw new Error('L’aperçu est indisponible. Vérifie les réglages et ta connexion.');
            const html = await response.text(); if (current !== version || !form.isConnected) return;
            frame.srcdoc = html; size(); feedback.textContent = 'Aperçu à jour · aucun enregistrement effectué.';
        } catch (error) { if (error.name !== 'AbortError') feedback.textContent = error instanceof TypeError ? 'Connexion interrompue. L’aperçu sera disponible lorsque la connexion sera rétablie.' : error.message; }
        finally { if (current === version) frame.removeAttribute('aria-busy'); }
    }
    form.addEventListener('input', event => { if (event.target.matches('input[type=url],input[data-image-address]')) return; clearTimeout(timer); timer = setTimeout(preview, 550); }, { signal: lifecycle.signal });
    form.addEventListener('change', () => { clearTimeout(timer); timer = setTimeout(preview, 250); }, { signal: lifecycle.signal });
    form.addEventListener('click', event => {
        const button = event.target.closest('[data-preview-device]');
        if (button) { width = Number(button.dataset.previewDevice); form.querySelectorAll('[data-preview-device]').forEach(node => node.setAttribute('aria-pressed', String(node === button))); size(); }
        if (event.target.closest('[data-refresh-preview]')) preview();
        if (event.target.closest('[data-scroll-preview]')) form.querySelector('.appearance-preview').scrollIntoView({ block: 'start', behavior: 'smooth' });
    }, { signal: lifecycle.signal });
    dispose = () => { lifecycle.abort(); pending?.abort(); clearTimeout(timer); observer.disconnect(); };
    preview();
}
document.addEventListener('rdsa:before-content-replace', () => dispose?.());
document.addEventListener('rdsa:content-updated', mount); mount();
