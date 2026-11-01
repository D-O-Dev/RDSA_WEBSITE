function sync(picker) {
    if (!picker) return;
    const custom = picker.querySelector('[data-design-colors]').checked;
    picker.querySelector('.design-color-fields').hidden = !custom;
}
document.addEventListener('change', event => {
    const picker = event.target.closest('[data-design-picker]'); if (!picker) return;
    if (event.target.matches('[data-design-preset]') && !picker.querySelector('[data-design-colors]').checked) {
        for (const field of picker.querySelectorAll('[data-design-color]')) field.value = event.target.dataset[field.dataset.designColor];
    }
    sync(picker);
});
function mount() { document.querySelectorAll('[data-design-picker]').forEach(sync); }
document.addEventListener('rdsa:content-updated', mount); mount();

