import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

document.addEventListener('click', (event) => {
    if (event.target.closest('[data-print]')) {
        window.print();
    }

    const button = event.target.closest('[data-demo-email]');

    if (! button) {
        return;
    }

    document.querySelectorAll('[data-demo-email]').forEach((other) => {
        other.setAttribute('aria-pressed', other === button ? 'true' : 'false');
    });

    const email = document.getElementById('email');
    const password = document.getElementById('password');

    if (email) {
        email.value = button.dataset.demoEmail ?? '';
        email.dispatchEvent(new Event('input', { bubbles: true }));
    }

    if (password) {
        password.value = button.dataset.demoPassword ?? '';
        password.dispatchEvent(new Event('input', { bubbles: true }));
    }

    email?.focus();
});

const idleMinutes = Number(document.querySelector('meta[name="idle-timeout"]')?.content ?? 0);

if (idleMinutes > 0) {
    let timer;
    const arm = () => {
        clearTimeout(timer);
        timer = setTimeout(() => {
            document.getElementById('idle-logout')?.submit();
        }, idleMinutes * 60 * 1000);
    };

    ['click', 'keydown', 'mousemove', 'scroll', 'touchstart'].forEach((eventName) => {
        document.addEventListener(eventName, arm, { passive: true });
    });

    arm();
}
