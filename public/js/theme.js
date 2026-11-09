// Ładowany z defer: DOM jest już gotowy, więc przycisk na pewno istnieje.
const themeToggle = document.querySelector('[data-theme-toggle]');
const root = document.documentElement;

function updateToggleLabel() {
    const isDark = root.dataset.theme === 'dark';
    // Teksty w aktualnym języku przychodzą z atrybutów data-* ustawionych przez widok.
    themeToggle.setAttribute('aria-label', isDark ? themeToggle.dataset.labelLight : themeToggle.dataset.labelDark);
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
