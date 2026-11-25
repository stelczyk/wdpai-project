// Lista wypraw: zmiana sortowania od razu wysyła formularz filtrów (bez JS działa przycisk „Szukaj”/Enter).
document.querySelector('[data-auto-submit]')?.addEventListener('change', (event) => {
    event.currentTarget.form.requestSubmit();
});

// Dołączanie kodem PIN przez Fetch API – bez przeładowania strony.
const joinForm = document.querySelector('[data-join-form]');

function showJoinMessage(text, type) {
    const message = joinForm.querySelector('[data-join-message]');
    message.textContent = text;
    message.dataset.type = type;   // „error” / „success” – kolor z CSS
}

function setJoinLoading(isLoading) {
    const button = joinForm.querySelector('button');
    button.disabled = isLoading;
    button.dataset.label ??= button.textContent;
    button.textContent = isLoading ? joinForm.dataset.loadingLabel : button.dataset.label;
}

async function joinExpedition(event) {
    event.preventDefault();
    const code = joinForm.elements.code.value.trim();

    // Walidacja po stronie klienta – serwer i tak sprawdza to samo (422).
    if (!/^\d{6}$/.test(code)) {
        showJoinMessage(joinForm.dataset.invalidMessage, 'error');
        joinForm.elements.code.focus();
        return;
    }

    setJoinLoading(true);
    showJoinMessage('', '');

    try {
        const { ok, data } = await fetchJson(joinForm.action, {
            method: 'POST',
            csrf: joinForm.elements._csrf.value,
            body: { code },
        });

        if (!ok || data === null) {
            showJoinMessage(data?.error ?? joinForm.dataset.errorMessage, 'error');
            return;
        }

        showJoinMessage(data.message, 'success');
        joinForm.elements.code.value = '';
        // Chwila na przeczytanie komunikatu, potem strona wyprawy.
        setTimeout(() => window.location.assign(data.redirect), 1200);
    } catch {
        // Brak sieci albo odpowiedź, która nie jest JSON-em.
        showJoinMessage(joinForm.dataset.errorMessage, 'error');
    } finally {
        setJoinLoading(false);
    }
}

joinForm?.addEventListener('submit', joinExpedition);
