const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value;
async function post(url, data, method = 'POST') {
    const response = await fetch(url, { method, credentials: 'same-origin', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf() }, body: JSON.stringify(data) });
    const result = await response.json();
    if (!response.ok) throw new Error(Object.values(result.errors || {}).flat()[0] || result.message || 'Opération indisponible.');
    return result;
}
const decode = value => Uint8Array.from(atob((value + '='.repeat((4 - value.length % 4) % 4)).replace(/-/g, '+').replace(/_/g, '/')), c => c.charCodeAt(0));
document.addEventListener('click', async event => {
    const button = event.target.closest('[data-push-toggle]'); if (!button || button.disabled) return;
    const feedback = button.parentElement.querySelector('[data-push-feedback]');
    button.disabled = true;
    try {
        if (!isSecureContext || !('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) throw new Error('Les notifications ne sont pas disponibles dans ce navigateur. Sur iPhone, ajoute le site à l’écran d’accueil puis ouvre-le depuis son icône.');
        const registration = await navigator.serviceWorker.register('/rdsa-push-worker.js');
        await navigator.serviceWorker.ready;
        const key = 'rdsa-push-' + button.dataset.pushScope;
        let saved; try { saved = JSON.parse(localStorage.getItem(key) || 'null'); } catch {}
        const existing = await registration.pushManager.getSubscription();
        if (existing && saved?.endpoint === existing.endpoint) {
            await post(button.dataset.pushDelete, { endpoint: existing.endpoint, delete_token: saved.token }, 'DELETE');
            await existing.unsubscribe(); localStorage.removeItem(key);
            button.textContent = 'Activer les notifications sur cet appareil'; feedback.textContent = 'Notifications désactivées.'; return;
        }
        if (await Notification.requestPermission() !== 'granted') throw new Error('Autorise les notifications dans les réglages du navigateur pour les activer.');
        const { publicKey } = await post(button.dataset.pushPrepare, {});
        const subscription = existing || await registration.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: decode(publicKey) });
        const { token } = await post(button.dataset.pushStore, subscription.toJSON());
        localStorage.setItem(key, JSON.stringify({ endpoint: subscription.endpoint, token }));
        button.textContent = 'Désactiver les notifications sur cet appareil'; feedback.textContent = 'Notifications activées. Leur réception dépend de ton navigateur et des réglages de ton appareil.';
    } catch (error) { feedback.textContent = error.message || 'Impossible d’activer les notifications.'; }
    finally { button.disabled = false; }
});
