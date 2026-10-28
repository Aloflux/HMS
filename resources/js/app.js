import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

/**
 * A dropdown you can type into. Options are {value, label, hint?, group?}.
 * The chosen value sits in a hidden input; the text box always shows the chosen label.
 */
Alpine.data('searchSelect', ({ options = [], value = '', emptyLabel = null } = {}) => ({
    options: emptyLabel === null ? options : [{ value: '', label: emptyLabel }, ...options],
    value: value === null ? '' : String(value),
    query: '',
    isOpen: false,
    active: 0,

    init() {
        this.query = this.labelFor(this.value);
        this.$watch('value', (current) => {
            if (! this.isOpen) {
                this.query = this.labelFor(current);
            }
        });
    },

    labelFor(current) {
        return this.options.find((option) => String(option.value) === String(current))?.label ?? '';
    },

    get filtered() {
        const typed = this.query.trim().toLowerCase();

        if (typed === '' || typed === this.labelFor(this.value).toLowerCase()) {
            return this.options.slice(0, 200);
        }

        const terms = typed.split(/\s+/);

        return this.options
            .filter((option) => {
                const text = [option.label, option.hint, option.group].filter(Boolean).join(' ').toLowerCase();

                return terms.every((term) => text.includes(term));
            })
            .slice(0, 200);
    },

    open() {
        this.isOpen = true;
    },

    close() {
        this.isOpen = false;
        this.active = 0;
        this.query = this.labelFor(this.value);
    },

    move(step) {
        this.open();
        const count = this.filtered.length;

        if (count === 0) {
            return;
        }

        this.active = (this.active + step + count) % count;
        this.$nextTick(() => this.$refs.list?.querySelector('[data-active="true"]')?.scrollIntoView({ block: 'nearest' }));
    },

    choose(option) {
        if (! option) {
            return;
        }

        this.value = String(option.value);
        this.close();
        this.$nextTick(() => this.$refs.hidden?.dispatchEvent(new Event('change', { bubbles: true })));
    },
}));

/**
 * Plural of a unit name, e.g. box → boxes, tablet → tablets. Measures such as ml and g stay as they are.
 */
window.pluralize = (word, count = 2) => {
    if (! word || Number(count) === 1 || ['ml', 'g', 'kg', 'l', 'mg', 'iu'].includes(word.toLowerCase())) {
        return word ?? '';
    }

    return /(s|x|z|ch|sh)$/i.test(word) ? `${word}es` : `${word}s`;
};

/**
 * The fields that describe a stock item: dosage form, dispensing unit and purchase pack.
 * Choosing a form fills in the unit and pack that form usually comes in.
 */
Alpine.data('itemFields', (initial = {}, defaults = {}) => ({
    form: initial.form ?? 'tablet',
    unit: initial.unit ?? '',
    content: initial.unit_content ?? '',
    packUnit: initial.pack_unit ?? '',
    packSize: initial.pack_size ?? '',
    category: initial.category ?? 'medicine',
    defaults,

    init() {
        if (! this.unit) {
            this.applyDefaults();
        }
    },

    applyDefaults() {
        const preset = this.defaults[this.form];

        if (! preset) {
            return;
        }

        this.unit = preset.unit;
        this.content = preset.unit_content ?? '';
        this.packUnit = preset.pack_unit ?? '';
        this.packSize = preset.pack_size > 1 ? preset.pack_size : '';
    },

    get meta() {
        const size = Number(this.packSize) || 1;
        const hasPacks = this.packUnit && size > 1;

        return {
            unit: this.unit || 'unit',
            units: window.pluralize(this.unit || 'unit'),
            pack_unit: hasPacks ? this.packUnit : null,
            pack_units: hasPacks ? window.pluralize(this.packUnit) : null,
            pack_size: hasPacks ? size : 1,
        };
    },
}));

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
