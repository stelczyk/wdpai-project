// Lista wypraw: zmiana sortowania od razu wysyła formularz filtrów (bez JS działa przycisk „Szukaj”/Enter).
document.querySelector('[data-auto-submit]')?.addEventListener('change', (event) => {
    event.currentTarget.form.requestSubmit();
});
