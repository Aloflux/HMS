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
    const emailValue = button.getAttribute('data-demo-email') ?? '';
    const passwordValue = button.getAttribute('data-demo-password') ?? '';

    if (email) {
        email.setAttribute('autocomplete', 'off');
        writeField(email, emailValue);
    }

    // Browsers clear a password field after the email changes. Write it again once that settles.
    const applyPassword = () => {
        if (! password) {
            return;
        }

        password.setAttribute('autocomplete', 'off');
        writeField(password, passwordValue);
    };

    applyPassword();
    requestAnimationFrame(applyPassword);
    window.setTimeout(applyPassword, 50);
});

function writeField(input, value) {
    const setter = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value')?.set;

    if (setter) {
        setter.call(input, value);
    } else {
        input.value = value;
    }

    input.dispatchEvent(new Event('input', { bubbles: true }));
}

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
