// Ładowany w <head> BEZ defer: ustawia motyw przed pierwszym rysowaniem strony.
// Kolejność: zapisany wybór użytkownika → ustawienie systemu (prefers-color-scheme).
(() => {
    let stored = null;
    try {
        stored = localStorage.getItem('theme');
    } catch {
        // localStorage bywa zablokowany (tryb prywatny) – wtedy zostaje ustawienie systemu.
    }

    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    document.documentElement.dataset.theme = stored ?? (prefersDark ? 'dark' : 'light');
})();
