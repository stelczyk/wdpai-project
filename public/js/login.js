const passwordToggle = document.querySelector('[data-password-toggle]');

passwordToggle?.addEventListener('click', () => {
    const input = document.getElementById('password');
    const visible = input.type === 'text';

    input.type = visible ? 'password' : 'text';
    passwordToggle.setAttribute('aria-pressed', String(!visible));
    passwordToggle.setAttribute('aria-label', visible ? 'Pokaż hasło' : 'Ukryj hasło');
});

document.querySelector('[data-alert-close]')?.addEventListener('click', (event) => {
    event.currentTarget.closest('[data-alert]').remove();
});
