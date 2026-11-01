// Keep a focused control inside the visible area when a mobile keyboard opens.
let timer;
function revealField() {
    const field = document.activeElement;
    if (!field?.matches('input:not([type=hidden]),textarea,select') || !matchMedia('(max-width: 900px)').matches) return;
    const viewport = window.visualViewport;
    const top = (viewport?.offsetTop || 0) + 18, bottom = (viewport?.offsetTop || 0) + (viewport?.height || innerHeight) - 26;
    const rect = field.getBoundingClientRect();
    const offset = rect.bottom > bottom ? rect.bottom - bottom : rect.top < top ? rect.top - top : 0;
    if (!offset) return;
    let container = field.parentElement;
    while (container && !(container.scrollHeight > container.clientHeight + 2 && /auto|scroll/.test(getComputedStyle(container).overflowY))) container = container.parentElement;
    (container || window).scrollBy({ top: offset, behavior: 'instant' });
}
function update() {
    document.documentElement.style.setProperty('--rdsa-viewport-height', `${window.visualViewport?.height || innerHeight}px`);
    clearTimeout(timer); timer = setTimeout(revealField, 140);
}
window.visualViewport?.addEventListener('resize', update);
window.addEventListener('resize', update);
document.addEventListener('focusin', update);
document.addEventListener('invalid', update, true);
update();
