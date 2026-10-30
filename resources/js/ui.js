const reducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;
export function reveal(element) {
    if (!reducedMotion() && element?.animate) element.animate([{ opacity: 0, transform: 'translateY(8px)' }, { opacity: 1, transform: 'translateY(0)' }], { duration: 350, easing: 'ease' });
}
export function setupDialog(dialog) {
    let trigger, overflow, closing = false;
    const close = async () => {
        if (!dialog.open || closing) return;
        closing = true;
        if (!reducedMotion() && dialog.animate) await dialog.animate([{ opacity: 1, transform: 'translateY(0) scale(1)' }, { opacity: 0, transform: 'translateY(10px) scale(.985)' }], { duration: 350, easing: 'ease' }).finished.catch(() => {});
        dialog.close(); closing = false;
    };
    dialog.addEventListener('cancel', event => { event.preventDefault(); close(); });
    // Search inputs may consume Escape to clear their value before a native cancel.
    dialog.addEventListener('keydown', event => {
        if (event.key === 'Escape') { event.preventDefault(); event.stopPropagation(); close(); }
    });
    dialog.addEventListener('click', event => {
        if (event.target !== dialog) return;
        const r = dialog.getBoundingClientRect();
        if (event.clientX < r.left || event.clientX > r.right || event.clientY < r.top || event.clientY > r.bottom) close();
    });
    dialog.addEventListener('close', () => {
        document.body.style.overflow = overflow ?? '';
        trigger?.focus({ preventScroll: true });
    });
    dialog.querySelectorAll('[data-dialog-close]').forEach(button => button.addEventListener('click', close));
    return { open(opener) {
        if (dialog.open || closing) return;
        trigger = opener || document.activeElement; overflow = document.body.style.overflow;
        dialog.showModal(); document.body.style.overflow = 'hidden'; reveal(dialog);
    }, close };
}

