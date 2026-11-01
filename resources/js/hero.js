const hero = document.querySelector('[data-hero-mode]');
if (hero) {
    const slides = [...hero.querySelectorAll('.hero-slide')], pause = hero.querySelector('[data-hero-pause]');
    const reduced = matchMedia('(prefers-reduced-motion: reduce)');
    let current = 0, timer, paused = reduced.matches || document.body.dataset.designMotion === 'none';
    let generation = 0;
    async function show(index) {
        const version = ++generation;
        for (let offset = 0; offset < slides.length; offset++) {
            const next = (index + offset + slides.length) % slides.length, image = slides[next];
            image.loading = 'eager';
            try { await image.decode(); } catch { continue; }
            if (version !== generation) return;
            hero.querySelectorAll('[data-hero-foreground]').forEach(node => { node.src = image.src; if (!reduced.matches && document.body.dataset.designMotion !== 'none') node.animate?.([{opacity:.25},{opacity:1}], {duration:650,easing:'ease'}); });
            slides[current].classList.remove('is-active'); image.classList.add('is-active'); current = next;
            hero.querySelectorAll('[data-hero-select]').forEach(button => { const active = Number(button.dataset.heroSelect) === next; button.classList.toggle('is-active', active); button.setAttribute('aria-pressed', String(active)); if (active && button.parentElement.classList.contains('ref-destination-deck')) { const deck = button.parentElement; deck.scrollTo({ left: Math.max(0, button.offsetLeft - deck.offsetLeft - (deck.clientWidth - button.offsetWidth) / 2), behavior: reduced.matches ? 'instant' : 'smooth' }); } });
            hero.querySelectorAll('[data-hero-position]').forEach(node => node.textContent = String(next + 1).padStart(2, '0'));
            return;
        }
    }
    function schedule() {
        clearInterval(timer);
        if (slides.length > 1 && !paused && !document.hidden && hero.dataset.heroMode !== 'static' && document.body.dataset.designMotion !== 'none') timer = setInterval(() => show(current + 1), Math.max(4, Number(hero.dataset.heroInterval) || 6) * 1000);
    }
    hero.querySelectorAll('[data-hero-select]').forEach(button => button.addEventListener('click', () => { show(Number(button.dataset.heroSelect)); schedule(); }));
    hero.querySelector('[data-hero-next]')?.addEventListener('click', () => { show(current + 1); schedule(); });
    hero.querySelector('[data-hero-prev]')?.addEventListener('click', () => { show(current - 1); schedule(); });
    pause?.addEventListener('click', () => { paused = !paused; pause.textContent = paused ? '▷' : 'Ⅱ'; pause.setAttribute('aria-label', paused ? 'Reprendre le diaporama' : 'Mettre en pause le diaporama'); schedule(); });
    reduced.addEventListener('change', () => { paused = reduced.matches || document.body.dataset.designMotion === 'none'; schedule(); });
    document.addEventListener('visibilitychange', schedule);
    if (hero.dataset.heroMode === 'static') hero.querySelector('.hero-slide-controls')?.setAttribute('hidden', '');
    schedule();
}
