import { Html5Qrcode, Html5QrcodeSupportedFormats } from 'html5-qrcode';

const form = document.getElementById('check-in-form');
const edition = document.getElementById('check-edition');
const code = document.getElementById('check-code');
const result = document.getElementById('check-result');
const confirmButton = document.getElementById('check-confirm');
const inspectButton = document.getElementById('check-inspect');
const nextButton = document.getElementById('check-next');
const startButton = document.getElementById('camera-start');
const stopButton = document.getElementById('camera-stop');
const cameraMessage = document.getElementById('camera-message');
let scanner;
let running = false;
let paused = false;
let busy = false;
let inspectedCode = null;

function clear() {
    inspectedCode = null;
    confirmButton.hidden = true;
    result.replaceChildren();
}

function message(text, status = 'invalid') {
    result.replaceChildren();
    const badge = document.createElement('p');
    badge.className = 'bd ' + (['valid', 'used'].includes(status) ? 'ok' : status === 'already_used' ? 'wa' : 'er');
    badge.textContent = text;
    result.append(badge);
}

function render(data) {
    message(data.message, data.status);
    const list = document.createElement('div');
    list.className = 'kv';
    for (const [key, label] of Object.entries({ participant: 'Participant', reference: 'Billet', edition: 'Édition', checked_at: 'Passage', checked_by: 'Agent' })) {
        if (!data[key]) continue;
        const term = document.createElement('span'), value = document.createElement('b');
        term.textContent = label;
        value.textContent = data[key];
        list.append(term, value);
    }
    result.append(list);
}

function lock(value) {
    busy = value;
    edition.disabled = value;
    code.disabled = value;
    inspectButton.disabled = value;
    confirmButton.disabled = value;
    nextButton.disabled = value;
    startButton.disabled = value;
}

function pause() {
    if (running && !paused) { scanner.pause(true); paused = true; }
}

async function request(url, originalCode) {
    let response;
    try { response = await fetch(url, {
        method: 'POST', credentials: 'same-origin', redirect: 'error',
        headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value },
        body: JSON.stringify({ edition_id: edition.value, code: originalCode }),
    }); } catch {
        throw new Error('Connexion interrompue. Vérifie à nouveau le billet avant d’autoriser l’entrée.');
    }
    if (![200, 409, 422].includes(response.status)) {
        throw new Error(response.status === 419 || response.status === 401 ? 'Session expirée. Reconnecte-toi.' : 'Contrôle impossible. Vérifie la connexion puis recommence la vérification.');
    }
    const data = await response.json();
    if (data.errors) throw new Error(Object.values(data.errors).flat()[0]);
    return data;
}

async function inspect(value) {
    if (busy) return;
    clear();
    if (!edition.value) { message('Choisis d’abord l’édition contrôlée.'); return; }
    if (!value.trim()) { code.focus(); return; }
    pause();
    lock(true);
    try {
        const data = await request(form.dataset.inspect, value);
        render(data);
        if (data.can_confirm) { inspectedCode = value; confirmButton.hidden = false; }
    } catch (error) { message(error.message || 'Connexion impossible. Réessaie.'); }
    finally { lock(false); }
}

form.addEventListener('submit', event => { event.preventDefault(); inspect(code.value); });
confirmButton.addEventListener('click', async () => {
    if (busy || !inspectedCode) return;
    const value = inspectedCode;
    clear();
    lock(true);
    try { render(await request(form.dataset.confirm, value)); }
    catch (error) { message(error.message || 'Résultat incertain. Vérifie le billet à nouveau avant de laisser entrer la personne.'); }
    finally { lock(false); }
});
code.addEventListener('input', clear);
edition.addEventListener('change', clear);
nextButton.addEventListener('click', () => {
    if (busy) return;
    clear(); code.value = ''; code.focus();
    if (running && paused) { scanner.resume(); paused = false; }
});
startButton.addEventListener('click', async () => {
    if (!edition.value) { message('Choisis d’abord l’édition contrôlée.'); return; }
    if (!window.isSecureContext || !navigator.mediaDevices) {
        cameraMessage.textContent = 'La caméra nécessite HTTPS et un navigateur compatible. La saisie de référence reste disponible.';
        return;
    }
    startButton.disabled = true;
    try {
        scanner ??= new Html5Qrcode('qr-reader', { formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE], verbose: false });
        await scanner.start({ facingMode: 'environment' }, { fps: 10, qrbox: (width, height) => {
            const size = Math.floor(Math.min(width, height) * 0.7); return { width: size, height: size };
        } }, value => {
            if (busy || inspectedCode || !running) return;
            code.value = value;
            inspect(value);
        }, () => {});
        running = true; paused = false;
        startButton.hidden = true; stopButton.hidden = false;
        cameraMessage.textContent = 'Caméra active. Place le QR code dans le cadre.';
    } catch {
        cameraMessage.textContent = 'Caméra indisponible ou accès refusé. Autorise la caméra ou saisis la référence du billet.';
    } finally { startButton.disabled = false; }
});
stopButton.addEventListener('click', async () => {
    if (!running) return;
    stopButton.disabled = true;
    try { await scanner.stop(); }
    finally {
        running = false; paused = false;
        stopButton.hidden = true; startButton.hidden = false; stopButton.disabled = false;
        cameraMessage.textContent = 'Caméra arrêtée. La saisie de référence reste disponible.';
    }
});
window.addEventListener('pagehide', () => { if (running) scanner.stop().catch(() => {}); });
