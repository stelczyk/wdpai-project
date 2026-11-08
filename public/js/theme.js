// Ładowany z defer: DOM jest już gotowy, więc przycisk na pewno istnieje.
const themeToggle = document.querySelector('[data-theme-toggle]');
const root = document.documentElement;

function updateToggleLabel() {
    const isDark = root.dataset.theme === 'dark';
    themeToggle.setAttribute('aria-label', isDark ? 'Włącz jasny motyw' : 'Włącz ciemny motyw');
}

themeToggle?.addEventListener('click', () => {
    root.dataset.theme = root.dataset.theme === 'dark' ? 'light' : 'dark';
    try {
        localStorage.setItem('theme', root.dataset.theme);
    } catch {
        // Bez localStorage motyw działa do przeładowania strony.
    }
    updateToggleLabel();
});

if (themeToggle) {
    updateToggleLabel();
}
