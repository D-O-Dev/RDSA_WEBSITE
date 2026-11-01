import './push.js';
import './media.js';
import './public-notifications.js';
import './hero.js';
import './interactions.js';
import Collapse from 'bootstrap/js/dist/collapse';
import './reservation.js';
import './messages.js';
import { setupDialog } from './ui';

const menu = document.getElementById('rdsaNavigation'), toggle = document.querySelector('.rdsa-toggler');
if (toggle && menu) {
    const navigation = Collapse.getOrCreateInstance(menu, { toggle: false });
    const closeMenu = () => navigation.hide();
    menu.addEventListener('shown.bs.collapse', () => toggle.setAttribute('aria-label', 'Fermer le menu'));
    menu.addEventListener('hidden.bs.collapse', () => toggle.setAttribute('aria-label', 'Ouvrir le menu'));
    menu.addEventListener('click', event => { if (event.target.closest('a,button')) closeMenu(); });
    document.addEventListener('keydown', event => { if (event.key === 'Escape' && menu.classList.contains('show')) { closeMenu(); toggle.focus(); } });
    document.addEventListener('click', event => { if (!event.target.closest('.rdsa-navbar')) closeMenu(); });
}

const ticketDialog = document.getElementById('ticket-dialog');
if (ticketDialog) {
    const modal = setupDialog(ticketDialog);
    document.querySelectorAll('[data-ticket-open]').forEach(button => button.addEventListener('click', () => modal.open(button)));
    if (ticketDialog.querySelector('[data-lookup-feedback]')?.textContent.trim()) modal.open();
}
for (const [selector, feedbackSelector] of [['[data-ticket-lookup]', '[data-lookup-feedback]']]) {
    document.querySelectorAll(selector).forEach(form => form.addEventListener('submit', async event => {
        event.preventDefault(); if (form.dataset.sending) return;
        form.dataset.sending = 'true';
        const button = form.querySelector('[type=submit]'), label = button.innerHTML, feedback = form.querySelector(feedbackSelector);
        button.disabled = true; button.textContent = 'Un instant…'; feedback.textContent = '';
        try {
            const response = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' }, credentials: 'same-origin', redirect: 'error' });
            if (response.status === 419) throw new Error('Le formulaire a expiré. Recharge la page.');
            if (response.status === 429) throw new Error('Trop de tentatives. Patiente une minute puis réessaie.');
            const data = await response.json();
            if (!response.ok) throw new Error(data.errors ? Object.values(data.errors).flat()[0] : 'La demande a échoué. Réessaie.');
            window.location.assign(data.url);
        } catch (error) { feedback.textContent = error instanceof TypeError ? 'Connexion interrompue. Tes informations sont conservées. Réessaie.' : error.message; button.disabled = false; button.innerHTML = label; delete form.dataset.sending; }
    }));
}
const countdown = document.querySelector('[data-starts-at]');
if (countdown?.dataset.startsAt) {
    const start = Date.parse(countdown.dataset.startsAt);
    let interval;
    const tick = () => {
        const diff = Math.max(0, Math.floor((start - Date.now()) / 1000));
        for (const [id, value] of Object.entries({ days: Math.floor(diff / 86400), hours: Math.floor(diff / 3600) % 24, minutes: Math.floor(diff / 60) % 60, seconds: diff % 60 })) document.getElementById(id).textContent = String(value).padStart(2, '0');
        if (document.getElementById('daysup')) document.getElementById('daysup').textContent = String(Math.floor(diff / 86400)).padStart(2, '0');
        if (!diff) { document.getElementById('countdown-status') && (document.getElementById('countdown-status').textContent = 'Le rendez-vous est arrivé.'); clearInterval(interval); }
    };
    if (Number.isFinite(start)) { interval = setInterval(tick, 1000); tick(); }
}
/* =========================================================
   PARALLAX — HERO RDSA
   ========================================================= */

document.addEventListener('DOMContentLoaded', () => {
    const hero = document.querySelector('.rdsa-hero');
    const background = document.querySelector(
        '.rdsa-hero-background img'
    );

    if (!hero || !background || hero.dataset.heroMode !== 'static') return;

    // Respecter les préférences d'accessibilité.
    const reducedMotion = window.matchMedia(
        '(prefers-reduced-motion: reduce)'
    );

    if (reducedMotion.matches || document.body.dataset.designMotion === 'none') return;

    let ticking = false;

    function updateParallax() {
        const rect = hero.getBoundingClientRect();

        // Ne calculer que lorsque le hero est visible.
        if (rect.bottom > 0 && rect.top < window.innerHeight) {
            const speed = 0.20;
            const offset = -rect.top * speed;

            background.style.transform =
                `translate3d(0, ${offset}px, 0) scale(1.15)`;
        }

        ticking = false;
    }

    function requestParallaxUpdate() {
        if (ticking) return;

        ticking = true;
        requestAnimationFrame(updateParallax);
    }

    window.addEventListener(
        'scroll',
        requestParallaxUpdate,
        { passive: true }
    );

    window.addEventListener('resize', requestParallaxUpdate);

    updateParallax();
});

document.documentElement.classList.add('rdsa-ready');
