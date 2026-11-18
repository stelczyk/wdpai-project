// Przełącznik wypraw w headerze: <details> otwiera się sam, tu tylko domykanie panelu.
const expeditionSwitch = document.querySelector('[data-exp-switch]');

function closeSwitch(returnFocus) {
    expeditionSwitch.open = false;
    if (returnFocus) {
        expeditionSwitch.querySelector('summary').focus();
    }
}

if (expeditionSwitch) {
    // Kliknięcie gdziekolwiek poza panelem zamyka listę.
    document.addEventListener('click', (event) => {
        if (expeditionSwitch.open && !expeditionSwitch.contains(event.target)) {
            closeSwitch(false);
        }
    });

    // Escape zamyka i oddaje fokus przyciskowi – użytkownik klawiatury nie gubi miejsca na stronie.
    expeditionSwitch.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && expeditionSwitch.open) {
            closeSwitch(true);
        }
    });
}
