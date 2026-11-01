import { setupDialog, reveal } from './ui';

const opener = document.getElementById('media-open');
if (opener) {
    let items = JSON.parse(document.getElementById('media-data').textContent);
    const dialog = document.getElementById('media-dialog'), modal = setupDialog(dialog);
    const stage = document.getElementById('media-stage'), strip = document.getElementById('media-filmstrip');
    const album = document.getElementById('media-album'), source = document.getElementById('media-source');
    let kind = 'all', current = 0, filtered = items, touch, page = 1, pages = 1, total = items.length, pending, version = 0;
    const galleryURL = document.getElementById('media-data').dataset.galleryUrl;
    function fitPlayer() {
        const player = stage.querySelector('iframe'); if (!player) return;
        const ratios = { portrait: 9 / 16, square: 1, landscape: 16 / 9 };
        const ratio = ratios[filtered[current]?.format] || 16 / 9;
        const width = Math.min(stage.clientWidth, stage.clientHeight * ratio);
        player.style.width = width + 'px'; player.style.height = width / ratio + 'px';
    }
    if ('ResizeObserver' in window) new ResizeObserver(fitPlayer).observe(stage);
    else window.addEventListener('resize', fitPlayer);

    function show(index) {
        stage.querySelector('video')?.pause(); stage.replaceChildren(); source.hidden = true;
        if (!filtered.length) { document.getElementById('media-dialog-title').textContent = 'La galerie'; document.getElementById('media-dialog-album').textContent = 'L’ALBUM RDSA'; return; }
        current = (index + filtered.length) % filtered.length;
        const item = filtered[current];
        document.getElementById('media-dialog-title').textContent = item.title;
        document.getElementById('media-dialog-album').textContent = item.album;
        document.getElementById('media-position').textContent = `${(page - 1) * 60 + current + 1} / ${total}`;
        let content;
        if (item.kind === 'photo') {
            content = new Image(); content.src = item.source; content.alt = item.title;
            content.addEventListener('error', () => { if (content.isConnected) stage.textContent = 'Cette image est momentanément indisponible.'; });
        } else if (item.embed) {
            content = document.createElement('iframe'); content.src = item.embed; content.title = item.title;
            content.allow = 'autoplay; fullscreen; picture-in-picture; encrypted-media'; content.allowFullscreen = true;
            content.referrerPolicy = 'strict-origin-when-cross-origin';
            content.dataset.format = item.format;
            source.hidden = false;
            const link = document.getElementById('media-original'); link.href = item.source; link.textContent = `Ouvrir sur ${item.provider}`;
        } else {
            content = document.createElement('video'); content.src = item.source; content.poster = item.thumbnail;
            content.controls = true; content.playsInline = true; content.preload = 'metadata';
            content.addEventListener('error', () => { if (content.isConnected) { source.hidden = false; const link = document.getElementById('media-original'); link.href = item.source; link.textContent = 'Ouvrir le fichier vidéo'; } });
        }
        stage.append(content); fitPlayer(); reveal(content);
        strip.querySelectorAll('button').forEach((button, i) => { button.classList.toggle('is-active', i === current); button.setAttribute('aria-pressed', String(i === current)); });
        const active = strip.children[current];
        if (active) { // Only scroll the strip, never the document or dialog header.
            strip.scrollTo({ top: active.offsetTop - strip.offsetTop - strip.clientHeight / 2 + active.clientHeight / 2, left: active.offsetLeft - strip.offsetLeft - strip.clientWidth / 2 + active.clientWidth / 2, behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth' });
        }
    }
    async function render(targetPage = 1, last = false) {
        if (typeof targetPage !== 'number') targetPage = 1;
        const requestVersion = ++version; pending?.abort(); pending = new AbortController();
        stage.querySelector('video')?.pause();
        if (galleryURL) {
            document.querySelector('.rdsa-album-viewer').hidden = false;
            document.getElementById('media-empty').hidden = true;
            stage.textContent = 'Chargement des souvenirs…'; strip.replaceChildren(); source.hidden = true;
            for (const id of ['media-prev', 'media-next']) document.getElementById(id).disabled = true;
            try {
                const url = new URL(galleryURL, location.href); url.search = new URLSearchParams({ kind, album: album.value, page: targetPage });
                const response = await fetch(url, { signal: pending.signal, headers: { Accept: 'application/json' } });
                if (!response.ok) throw new Error('La galerie est momentanément indisponible.');
                const data = await response.json(); if (requestVersion !== version) return;
                items = data.items; page = data.page; pages = data.last_page; total = data.total;
            } catch (error) {
                if (requestVersion !== version || error.name === 'AbortError') return;
                stage.textContent = 'Impossible de charger la galerie. Vérifie ta connexion.';
                const retry = document.createElement('button'); retry.type = 'button'; retry.className = 'btn'; retry.textContent = 'Réessayer'; retry.onclick = () => render(targetPage, last); stage.append(retry); return;
            }
        }
        filtered = items.filter(item => (kind === 'all' || item.kind === kind) && (!album.value || item.album === album.value));
        strip.replaceChildren(...filtered.map((item, index) => {
            const button = document.createElement('button'); button.type = 'button'; button.className = 'rdsa-album-thumb'; button.setAttribute('aria-label', `${index + 1}. ${item.title}`);
            const image = new Image(); image.src = item.thumbnail; image.alt = ''; image.loading = 'lazy';
            const label = document.createElement('span'); label.textContent = item.kind === 'video' ? '▶ Vidéo' : String(index + 1);
            button.append(image, label); button.addEventListener('click', () => show(index)); return button;
        }));
        document.getElementById('media-empty').hidden = filtered.length > 0;
        document.querySelector('.rdsa-album-viewer').hidden = !filtered.length;
        document.getElementById('media-position').textContent = '0 / 0';
        for (const id of ['media-prev', 'media-next']) document.getElementById(id).disabled = total < 2;
        show(last ? filtered.length - 1 : 0);
    }
    function step(direction) {
        if (!filtered.length) return;
        if (direction > 0 && current === filtered.length - 1 && pages > 1) return render(page === pages ? 1 : page + 1);
        if (direction < 0 && current === 0 && pages > 1) return render(page === 1 ? pages : page - 1, true);
        show(current + direction);
    }
    opener.addEventListener('click', () => { modal.open(opener); render(); });
    document.querySelectorAll('[data-album-open]').forEach(link => link.addEventListener('click', event => {
        event.preventDefault(); modal.open(link); album.value = Array.from(album.options).some(option => option.value === link.dataset.albumOpen) ? link.dataset.albumOpen : '';
        kind = 'all'; document.querySelectorAll('[data-media-kind]').forEach(button => { button.classList.toggle('is-active', button.dataset.mediaKind === 'all'); button.setAttribute('aria-pressed', String(button.dataset.mediaKind === 'all')); }); render();
    }));
    document.querySelectorAll('[data-media-kind]').forEach(button => button.addEventListener('click', () => {
        kind = button.dataset.mediaKind;
        document.querySelectorAll('[data-media-kind]').forEach(other => { other.classList.toggle('is-active', other === button); other.setAttribute('aria-pressed', String(other === button)); }); render();
    }));
    album.addEventListener('change', render);
    document.getElementById('media-prev').addEventListener('click', () => step(-1));
    document.getElementById('media-next').addEventListener('click', () => step(1));
    dialog.addEventListener('close', () => { ++version; pending?.abort(); stage.querySelector('video')?.pause(); stage.replaceChildren(); });
    dialog.addEventListener('keydown', event => {
        if (['VIDEO', 'IFRAME', 'INPUT', 'SELECT'].includes(event.target.tagName)) return;
        if (event.key === 'ArrowRight' || event.key === 'ArrowLeft') { event.preventDefault(); step(event.key === 'ArrowRight' ? 1 : -1); }
    });
    stage.addEventListener('pointerdown', event => { touch = event.pointerType === 'touch' && filtered[current]?.kind === 'photo' ? [event.clientX, event.clientY] : null; });
    stage.addEventListener('pointerup', event => { if (!touch) return; const dx = event.clientX - touch[0], dy = event.clientY - touch[1]; touch = null; if (Math.abs(dx) > 60 && Math.abs(dx) > Math.abs(dy)) step(dx < 0 ? 1 : -1); });
}
