import './bootstrap';

const slugify = (value) =>
    value
        .normalize('NFKD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');

const toggleSubmitState = (button, isLoading) => {
    if (!button) {
        return;
    }

    button.disabled = isLoading;
    button.setAttribute('aria-busy', isLoading ? 'true' : 'false');

    button.querySelector('[data-submit-label]')?.classList.toggle('hidden', isLoading);
    button.querySelector('[data-submit-spinner]')?.classList.toggle('hidden', !isLoading);
    button.querySelector('[data-submit-spinner]')?.classList.toggle('inline-flex', isLoading);
};

document.querySelectorAll('[data-auth-form]').forEach((form) => {
    const submitButton = form.querySelector('[data-submit-button]');
    const password = form.querySelector('[data-password-field]');
    const confirmation = form.querySelector('[data-password-confirmation-field]');
    const confirmationError = form.querySelector('[data-password-confirmation-error]');

    const validatePasswordConfirmation = () => {
        if (!(password instanceof HTMLInputElement) || !(confirmation instanceof HTMLInputElement) || !confirmationError) {
            return true;
        }

        if (confirmation.value === '') {
            confirmation.setCustomValidity('');
            confirmationError.textContent = '';
            confirmationError.classList.add('hidden');

            return true;
        }

        const matches = password.value === confirmation.value;

        confirmation.setCustomValidity(matches ? '' : form.dataset.passwordMismatch ?? '');
        confirmationError.textContent = matches ? '' : form.dataset.passwordMismatch ?? '';
        confirmationError.classList.toggle('hidden', matches);

        return matches;
    };

    password?.addEventListener('input', validatePasswordConfirmation);
    confirmation?.addEventListener('input', validatePasswordConfirmation);
    confirmation?.addEventListener('blur', validatePasswordConfirmation);

    form.addEventListener('submit', (event) => {
        if (!validatePasswordConfirmation()) {
            event.preventDefault();
            confirmation?.focus();

            return;
        }

        toggleSubmitState(submitButton, true);
    });
});

document.querySelectorAll('[data-slug-source]').forEach((source) => {
    if (!(source instanceof HTMLInputElement)) {
        return;
    }

    const target = source.form?.querySelector('[data-slug-target]');

    if (!(target instanceof HTMLInputElement)) {
        return;
    }

    let isManualOverride = target.value !== '' && target.value !== slugify(source.value);

    const syncSlug = () => {
        if (!isManualOverride) {
            target.value = slugify(source.value);
        }
    };

    source.addEventListener('input', syncSlug);

    target.addEventListener('input', () => {
        isManualOverride = target.value !== '' && target.value !== slugify(source.value);
    });

    syncSlug();
});
