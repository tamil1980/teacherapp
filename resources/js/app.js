import './bootstrap';

document.querySelectorAll('[data-auto-dismiss]').forEach((alert) => {
    const delay = Number(alert.dataset.autoDismiss);

    if (Number.isFinite(delay) && delay > 0) {
        window.setTimeout(() => alert.remove(), delay);
    }
});

const password = document.querySelector('#password');
const passwordConfirmation = document.querySelector('#password_confirmation');
const passwordMismatch = document.querySelector('[data-password-mismatch]');

if (password instanceof HTMLInputElement && passwordConfirmation instanceof HTMLInputElement) {
    const validatePasswordConfirmation = () => {
        const doesNotMatch = passwordConfirmation.value !== '' && passwordConfirmation.value !== password.value;

        passwordConfirmation.setCustomValidity(doesNotMatch ? 'Passwords do not match.' : '');

        if (passwordMismatch instanceof HTMLElement) {
            passwordMismatch.classList.toggle('hidden', !doesNotMatch);
        }
    };

    password.addEventListener('input', validatePasswordConfirmation);
    passwordConfirmation.addEventListener('input', validatePasswordConfirmation);
}
