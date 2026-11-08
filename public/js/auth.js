// Wspólny skrypt ekranów logowania i rejestracji.
// Każdy przycisk „oko” przełącza widoczność hasła w swoim polu.
document.querySelectorAll('[data-password-toggle]').forEach((toggle) => {
    toggle.addEventListener('click', () => {
        const input = toggle.closest('.input-wrap').querySelector('input');
        const visible = input.type === 'text';

        input.type = visible ? 'password' : 'text';
        toggle.setAttribute('aria-pressed', String(!visible));
        toggle.setAttribute('aria-label', visible ? 'Pokaż hasło' : 'Ukryj hasło');
    });
});

document.querySelector('[data-alert-close]')?.addEventListener('click', (event) => {
    event.currentTarget.closest('[data-alert]').remove();
});
