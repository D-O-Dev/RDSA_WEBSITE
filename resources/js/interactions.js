// Shared feedback for the existing Blade pages; no content depends on animation.
const reduced = matchMedia('(prefers-reduced-motion: reduce)');
const seen = new WeakSet();
const observer = 'IntersectionObserver' in window ? new IntersectionObserver(entries => {
    for (const entry of entries) {
        if (!entry.isIntersecting) continue;
        observer.unobserve(entry.target);
        if (!reduced.matches) entry.target.animate?.([
            { opacity: .25, transform: 'translateY(12px)' },
            { opacity: 1, transform: 'translateY(0)' },
        ], { duration: 420, easing: 'cubic-bezier(.2,.7,.2,1)' });
    }
}, { threshold: .08 }) : null;
export function animateContent(root = document) {
    root.querySelectorAll('.card,.edition-card,.contact-info-card,.rdsa-album-box,.ticket-launch,.ticket').forEach(element => {
        if (seen.has(element)) return;
        seen.add(element); observer?.observe(element);
    });
}
animateContent();

// A subtle press response follows pointer and keyboard activation without adding markup.
document.addEventListener('click', event => {
    if (reduced.matches) return;
    const button = event.target.closest('button,.btn,.button,.rdsa-primary-button,.rdsa-outline-button,.ticket-launch');
    if (!button || button.disabled || button.getAttribute('aria-disabled') === 'true') return;
    button.animate?.([{ scale: '1' }, { scale: '.97', offset: .4 }, { scale: '1' }], { duration: 220, easing: 'ease-out' });
});

// Only native submissions are handled here; AJAX forms keep their own feedback.
const pending = new Map();
function unlock(form) {
    const state = pending.get(form); if (!state) return;
    clearTimeout(state.timer); form.removeAttribute('aria-busy');
    state.button?.removeAttribute('aria-disabled'); state.button?.classList.remove('action-pending');
    pending.delete(form);
}
document.addEventListener('submit', event => {
    const form = event.target;
    if (pending.has(form)) { event.preventDefault(); return; }
    if ((form.method || '').toLowerCase() !== 'post') return;
    // Other submit listeners must first finish validation or prevent native submission.
    queueMicrotask(() => {
        if (event.defaultPrevented) return;
        const button = event.submitter;
        form.setAttribute('aria-busy', 'true');
        button?.setAttribute('aria-disabled', 'true'); button?.classList.add('action-pending');
        pending.set(form, { button, timer: setTimeout(() => unlock(form), 20000) });
    });
});
window.addEventListener('pageshow', () => { for (const form of pending.keys()) unlock(form); });
