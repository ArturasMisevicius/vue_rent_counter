import './bootstrap';
import { initializeCalendarModals } from './calendar-modal';

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

const dispatchInputEvents = (element) => {
    ['input', 'change'].forEach((eventName) => {
        element.dispatchEvent(new Event(eventName, { bubbles: true }));
    });
};

const avatarCropperInstances = new WeakSet();

const clamp = (value, min, max) => Math.min(Math.max(value, min), max);

const initializeAvatarCroppers = () => {
    document.querySelectorAll('[data-avatar-cropper]').forEach((cropper) => {
        if (avatarCropperInstances.has(cropper)) {
            return;
        }

        avatarCropperInstances.add(cropper);

        const fileInput = cropper.querySelector('[data-avatar-file]');
        const hiddenInput = cropper.querySelector('[data-avatar-cropped-input]');
        const canvas = cropper.querySelector('[data-avatar-canvas]');
        const zoomInput = cropper.querySelector('[data-avatar-zoom]');
        const controls = cropper.querySelector('[data-avatar-controls]');
        const editor = cropper.querySelector('[data-avatar-editor]');
        const applyButton = cropper.querySelector('[data-avatar-apply]');
        const saveButton = cropper.querySelector('[data-avatar-save]');
        const status = cropper.querySelector('[data-avatar-status]');
        const previewImage = cropper.querySelector('[data-avatar-preview-image]');
        const previewFallback = cropper.querySelector('[data-avatar-preview-fallback]');

        if (!(fileInput instanceof HTMLInputElement)
            || !(hiddenInput instanceof HTMLInputElement)
            || !(canvas instanceof HTMLCanvasElement)
            || !(zoomInput instanceof HTMLInputElement)
            || !(applyButton instanceof HTMLButtonElement)
            || !(saveButton instanceof HTMLButtonElement)
        ) {
            return;
        }

        const context = canvas.getContext('2d');

        if (!context) {
            return;
        }

        const state = {
            baseScale: 1,
            dragging: false,
            image: null,
            lastX: 0,
            lastY: 0,
            offsetX: 0,
            offsetY: 0,
        };

        const setStatus = (text) => {
            if (status) {
                status.textContent = text;
            }
        };

        const constrainOffsets = () => {
            if (!state.image) {
                return;
            }

            const scale = state.baseScale * Number(zoomInput.value || 1);
            const width = state.image.naturalWidth * scale;
            const height = state.image.naturalHeight * scale;
            const maxOffsetX = Math.max(0, (width - canvas.width) / 2);
            const maxOffsetY = Math.max(0, (height - canvas.height) / 2);

            state.offsetX = clamp(state.offsetX, -maxOffsetX, maxOffsetX);
            state.offsetY = clamp(state.offsetY, -maxOffsetY, maxOffsetY);
        };

        const render = () => {
            context.clearRect(0, 0, canvas.width, canvas.height);
            context.fillStyle = '#f8fafc';
            context.fillRect(0, 0, canvas.width, canvas.height);

            if (!state.image) {
                context.strokeStyle = '#cbd5e1';
                context.lineWidth = 3;
                context.strokeRect(1.5, 1.5, canvas.width - 3, canvas.height - 3);

                return;
            }

            const scale = state.baseScale * Number(zoomInput.value || 1);
            const width = state.image.naturalWidth * scale;
            const height = state.image.naturalHeight * scale;

            constrainOffsets();

            const x = (canvas.width - width) / 2 + state.offsetX;
            const y = (canvas.height - height) / 2 + state.offsetY;

            context.drawImage(state.image, x, y, width, height);
        };

        const showControls = () => {
            editor?.classList.remove('hidden');
            editor?.classList.add('flex');
            controls?.classList.remove('hidden');
            controls?.classList.add('flex');
            applyButton.disabled = false;
        };

        const hideEditor = () => {
            editor?.classList.add('hidden');
            editor?.classList.remove('flex');
            controls?.classList.add('hidden');
            controls?.classList.remove('flex');
            applyButton.disabled = true;
            saveButton.disabled = true;
        };

        const clearCroppedAvatar = () => {
            hiddenInput.value = '';
            dispatchInputEvents(hiddenInput);
            saveButton.disabled = true;
        };

        const loadImage = (file) => {
            clearCroppedAvatar();

            if (!file || !file.type.startsWith('image/')) {
                state.image = null;
                hideEditor();
                setStatus(cropper.dataset.invalidMessage || '');

                return;
            }

            const reader = new FileReader();

            reader.onerror = () => {
                state.image = null;
                hideEditor();
                setStatus(cropper.dataset.invalidMessage || '');
            };

            reader.onload = () => {
                if (typeof reader.result !== 'string') {
                    state.image = null;
                    hideEditor();
                    setStatus(cropper.dataset.invalidMessage || '');

                    return;
                }

                state.image = new Image();
                state.image.onload = () => {
                    state.baseScale = Math.max(
                        canvas.width / state.image.naturalWidth,
                        canvas.height / state.image.naturalHeight,
                    );
                    state.offsetX = 0;
                    state.offsetY = 0;
                    zoomInput.value = '1';
                    showControls();
                    setStatus(cropper.dataset.readyMessage || '');
                    render();
                };
                state.image.onerror = () => {
                    state.image = null;
                    hideEditor();
                    setStatus(cropper.dataset.invalidMessage || '');
                };
                state.image.src = reader.result;
            };

            reader.readAsDataURL(file);
        };

        const canvasPoint = (event) => {
            const rect = canvas.getBoundingClientRect();
            const ratio = canvas.width / rect.width;

            return {
                x: (event.clientX - rect.left) * ratio,
                y: (event.clientY - rect.top) * ratio,
            };
        };

        fileInput.addEventListener('change', () => {
            loadImage(fileInput.files?.[0]);
        });

        zoomInput.addEventListener('input', render);

        canvas.addEventListener('pointerdown', (event) => {
            if (!state.image) {
                return;
            }

            const point = canvasPoint(event);
            state.dragging = true;
            state.lastX = point.x;
            state.lastY = point.y;
            canvas.setPointerCapture(event.pointerId);
        });

        canvas.addEventListener('pointermove', (event) => {
            if (!state.dragging) {
                return;
            }

            const point = canvasPoint(event);
            state.offsetX += point.x - state.lastX;
            state.offsetY += point.y - state.lastY;
            state.lastX = point.x;
            state.lastY = point.y;
            render();
        });

        canvas.addEventListener('pointerup', (event) => {
            state.dragging = false;
            canvas.releasePointerCapture(event.pointerId);
        });

        applyButton.addEventListener('click', () => {
            if (!state.image) {
                return;
            }

            const dataUrl = canvas.toDataURL('image/png');

            hiddenInput.value = dataUrl;
            dispatchInputEvents(hiddenInput);

            if (previewImage instanceof HTMLImageElement) {
                previewImage.src = dataUrl;
                previewImage.classList.remove('hidden');
            }

            previewFallback?.classList.add('hidden');
            saveButton.disabled = false;
            setStatus(cropper.dataset.croppedMessage || '');
        });

        hideEditor();
        render();
    });
};

document.querySelectorAll('[data-auth-form]').forEach((form) => {
    const submitButton = form.querySelector('[data-submit-button]');
    const password = form.querySelector('[data-password-field]');
    const confirmation = form.querySelector('[data-password-confirmation-field]');
    const confirmationError = form.querySelector('[data-password-confirmation-error]');
    const emailField = form.querySelector('#email');
    const passwordField = form.querySelector('#password');

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

    document.querySelectorAll('[data-demo-account]').forEach((button) => {
        button.addEventListener('click', () => {
            if (!(button instanceof HTMLElement)) {
                return;
            }

            if (emailField instanceof HTMLInputElement) {
                emailField.value = button.dataset.demoEmail ?? '';
                emailField.dispatchEvent(new Event('input', { bubbles: true }));
            }

            if (passwordField instanceof HTMLInputElement) {
                passwordField.value = button.dataset.demoPassword ?? '';
                passwordField.dispatchEvent(new Event('input', { bubbles: true }));
            }
        });
    });

    form.addEventListener('submit', (event) => {
        if (!validatePasswordConfirmation()) {
            event.preventDefault();
            confirmation?.focus();

            return;
        }

        toggleSubmitState(submitButton, true);
    });
});

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') {
        return;
    }

    window.Livewire?.dispatch('shell-search-dismissed');
});

document.querySelectorAll('[data-demo-account-trigger]').forEach((trigger) => {
    if (!(trigger instanceof HTMLButtonElement)) {
        return;
    }

    trigger.addEventListener('click', () => {
        const authForm = document.querySelector('[data-auth-form]');

        if (!(authForm instanceof HTMLFormElement)) {
            return;
        }

        const emailInput = authForm.querySelector('input[name="email"]');
        const passwordInput = authForm.querySelector('input[name="password"]');

        if (!(emailInput instanceof HTMLInputElement) || !(passwordInput instanceof HTMLInputElement)) {
            return;
        }

        emailInput.value = trigger.dataset.demoAccountEmail ?? '';
        passwordInput.value = trigger.dataset.demoAccountPassword ?? '';

        dispatchInputEvents(emailInput);
        dispatchInputEvents(passwordInput);

        passwordInput.focus();
    });
});

document.addEventListener('DOMContentLoaded', initializeAvatarCroppers);
document.addEventListener('livewire:navigated', initializeAvatarCroppers);
document.addEventListener('DOMContentLoaded', initializeCalendarModals);
document.addEventListener('livewire:navigated', initializeCalendarModals);
