// Wspólne formatowanie dla skryptów stron (podgląd treningu, ranking). Ładowany przed nimi – zwykły skrypt,
// więc funkcje są dostępne globalnie jak w pozostałych plikach projektu.

// Jak Translator::number w PHP (number_format): PL „1 330,5”, EN „1,330.5”. Język z <html lang>.
function formatNumber(value, decimals = 1) {
    // toFixed zaokrągla binarną wartość (151.95 → „151.9”); przesunięcie przez notację „e” daje połówki w górę jak w PHP.
    const rounded = Number(`${Math.round(Number(`${Math.abs(value)}e${decimals}`))}e-${decimals}`);
    const [whole, fraction] = rounded.toFixed(decimals).split('.');
    const pl = document.documentElement.lang === 'pl';
    const grouped = whole.replace(/\B(?=(\d{3})+(?!\d))/g, pl ? ' ' : ',');
    const sign = value < 0 ? '-' : '';
    return sign + grouped + (fraction ? (pl ? ',' : '.') + fraction : '');
}

// Szablon z katalogu tłumaczeń, np. „Do punktu {name} zostanie {km} km” + {name: …, km: …}.
function fillTemplate(template, params) {
    return Object.entries(params).reduce((text, [key, value]) => text.replaceAll(`{${key}}`, value), template);
}
