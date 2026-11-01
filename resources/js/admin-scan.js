
export function mountScanner(form) {
const lifecycle = new AbortController();
let disposed = false, starting, stopping, preparing = false;
const reader = document.getElementById('qr-reader');
reader.id = 'rdsa-reader-' + Math.random().toString(36).slice(2);
const listen = (node, type, handler) => node.addEventListener(type, handler, { signal: lifecycle.signal });
const edition = document.getElementById('check-edition');
const code = document.getElementById('check-code');
const result = document.getElementById('check-result');
const confirmButton = document.getElementById('check-confirm');
const inspectButton = document.getElementById('check-inspect');
const nextButton = document.getElementById('check-next');
const startButton = document.getElementById('camera-start');
const stopButton = document.getElementById('camera-stop');
const cameraChoice = document.getElementById('camera-choice');
const photo = document.getElementById('qr-photo');
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
    if (disposed) return;
    result.replaceChildren();
    const badge = document.createElement('p');
    badge.className = 'bd ' + (['valid', 'used'].includes(status) ? 'ok' : status === 'already_used' ? 'wa' : 'er');
    badge.textContent = text;
    result.append(badge);
}

function render(data) {
    if (disposed) return;
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
    startButton.disabled = value || preparing;
    photo.disabled = value || preparing;
    cameraChoice.disabled = value || preparing || running;
}

function pause() {
    if (running && !paused) { scanner.pause(true); paused = true; }
}

async function request(url, originalCode) {
    let response;
    try { response = await fetch(url, {
        method: 'POST', credentials: 'same-origin', redirect: 'error', signal: lifecycle.signal,
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
    if (busy || disposed) return;
    clear();
    if (!edition.value) { message('Choisis d’abord l’édition contrôlée.'); return; }
    if (!value.trim()) { code.focus(); return; }
    pause();
    lock(true);
    try {
        const data = await request(form.dataset.inspect, value);
        render(data);
        if (!disposed && data.can_confirm) { inspectedCode = value; confirmButton.hidden = false; }
    } catch (error) { message(error.message || 'Connexion impossible. Réessaie.'); }
    finally { lock(false); }
}

listen(form, 'submit', event => { event.preventDefault(); inspect(code.value); });
listen(confirmButton, 'click', async () => {
    if (busy || disposed || !inspectedCode) return;
    const value = inspectedCode;
    clear();
    lock(true);
    try { render(await request(form.dataset.confirm, value)); }
    catch (error) { message(error.message || 'Résultat incertain. Vérifie le billet à nouveau avant de laisser entrer la personne.'); }
    finally { lock(false); }
});
listen(code, 'input', clear);
listen(edition, 'change', clear);
listen(nextButton, 'click', () => {
    if (busy || disposed) return;
    clear(); code.value = ''; code.focus();
    if (running && paused) { scanner.resume(); paused = false; }
});
listen(startButton, 'click', async () => {
    if (preparing || running || disposed) return;
    if (!edition.value) { message('Choisis d’abord l’édition contrôlée.'); return; }
    if (!window.isSecureContext || !navigator.mediaDevices) {
        cameraMessage.textContent = 'La caméra nécessite HTTPS et un navigateur compatible. La saisie de référence reste disponible.';
        return;
    }
    startButton.disabled = true; preparing = true;
    try {
        const { Html5Qrcode, Html5QrcodeSupportedFormats } = await import('html5-qrcode');
        if (disposed) return;
        scanner ??= new Html5Qrcode(reader.id, { formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE], verbose: false });
        const options = { fps: 10, qrbox: (width, height) => { const size = Math.floor(Math.min(width, height) * .7); return { width: size, height: size }; } };
        const success = value => { if (busy || inspectedCode || !running) return; code.value = value; inspect(value); };
        const start = constraint => { starting = scanner.start(constraint, options, success, () => {}); return starting; };
        try { await start(cameraChoice.value || { facingMode: 'environment' }); }
        catch (error) {
            if (/NotAllowed|Permission|denied|SecurityError/i.test(String(error))) throw error;
            const cameras = await Html5Qrcode.getCameras();
            if (!cameras.length) throw error;
            await start(cameras.find(camera => /back|rear|arrière|environment/i.test(camera.label))?.id || cameras[0].id);
        }
        await starting;
        running = true; paused = false;
        // The library resolves start() before the video starts playing. Keep it
        // attached until playback is ready, so stop() cannot interrupt its play promise.
        const video = reader.querySelector('video');
        if (video) { video.setAttribute('playsinline', ''); video.muted = true; await video.play(); }
        cameraChoice.disabled = true;
        try { const cameras = (await navigator.mediaDevices.enumerateDevices()).filter(device => device.kind === 'videoinput'); cameraChoice.replaceChildren(new Option('Arrière (automatique)', ''), ...cameras.map(camera => new Option(camera.label || 'Caméra', camera.deviceId))); } catch {}
        if (disposed) { await stopCamera(); return; }
        startButton.hidden = true; stopButton.hidden = false;
        cameraMessage.textContent = 'Caméra active. Place le QR code dans le cadre.';
    } catch (error) {
        if (running) await stopCamera();
        const detail = String(error);
        cameraMessage.textContent = /NotAllowed|Permission|denied|SecurityError/i.test(detail) ? "Accès caméra refusé. Autorise la caméra dans les réglages du navigateur. Dans WhatsApp/Facebook, ouvre cette page dans Safari ou Chrome. Tu peux aussi lire une photo du QR." : 'Caméra indisponible ou accès refusé. Autorise la caméra ou saisis la référence du billet.';
    } finally { preparing = false; startButton.disabled = false; cameraChoice.disabled = running; photo.disabled = false; }
});
async function stopCamera() {
    if (stopping) return stopping;
    if (!running) return;
    stopping = scanner.stop().catch(() => {}).finally(() => { running = false; paused = false; stopping = null; });
    return stopping;
}
listen(stopButton, 'click', async () => {
    if (!running) return;
    stopButton.disabled = true;
    try { await stopCamera(); }
    finally {
        stopButton.hidden = true; startButton.hidden = false; stopButton.disabled = false; cameraChoice.disabled = false;
        cameraMessage.textContent = 'Caméra arrêtée. La saisie de référence reste disponible.';
    }
});
listen(photo, 'change', async () => {
    const file = photo.files[0]; if (!file || busy || preparing || disposed) return;
    if (!edition.value) { message('Choisis d’abord l’édition contrôlée.'); photo.value = ''; return; }
    if (file.size > 15 * 1024 * 1024) { message('Choisis une photo de moins de 15 Mo.'); photo.value = ''; return; }
    preparing = true; lock(true);
    try {
        await stopCamera(); startButton.hidden = false; stopButton.hidden = true;
        const { Html5Qrcode, Html5QrcodeSupportedFormats } = await import('html5-qrcode');
        scanner ??= new Html5Qrcode(reader.id, { formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE], verbose: false });
        const value = await scanner.scanFile(file, true);
        preparing = false; lock(false); code.value = value; await inspect(value);
    } catch { message('Aucun QR lisible sur cette photo. Choisis une image plus nette ou saisis la référence.'); }
    finally { preparing = false; lock(false); photo.value = ''; }
});
listen(document, 'rdsa:before-navigate', event => {
    if (busy || preparing) {
        event.preventDefault(); message(preparing ? 'La caméra se prépare. Autorise ou refuse son accès avant de quitter cette rubrique.' : 'Un contrôle est en cours. Patiente avant de quitter cette rubrique.');
    }
});
const dispose = async () => {
    if (disposed) return stopping;
    disposed = true; lifecycle.abort();
    if (starting && !running) {
        reader.querySelectorAll('video').forEach(video => video.srcObject?.getTracks().forEach(track => track.stop()));
        void starting.then(stopCamera).catch(() => {});
        return;
    }
    await stopCamera();
};
listen(window, 'pagehide', dispose);
inspectButton.disabled = false; startButton.disabled = false;
return dispose;
}
