document.addEventListener('click', event => {
    const button = event.target.closest('[data-account-password]');
    if (!button) return;
    const input = document.getElementById(button.dataset.accountPassword);
    if (!input) return;
    const reveal = input.type === 'password';
    input.type = reveal ? 'text' : 'password';
    button.setAttribute('aria-pressed', String(reveal));
    const label = document.querySelector(`label[for="${input.id}"]`)?.textContent || 'Mot de passe';
    button.setAttribute('aria-label', `${reveal ? 'Masquer' : 'Afficher'} : ${label}`);
    button.querySelector('i').className = reveal ? 'bi bi-eye-slash' : 'bi bi-eye';
});
