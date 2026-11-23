// Panel kapitana: „Zatwierdź” / „Odrzuć” wysyłane przez fetch POST (JSON + X-CSRF-Token) bez przeładowania.
// Serwer zmienia status i zapisuje audit_log w jednej transakcji, a w odpowiedzi oddaje nowe liczniki.
// Bez JS te same przyciski wysyłają zwykły formularz.
const captain = document.querySelector('[data-captain]');

function captainMessage(name, params) {
    return fillTemplate(captain.dataset[name], params);
}

function setOut(name, text) {
    const element = captain.querySelector(`[data-out="${name}"]`);
    if (element) {
        element.textContent = text;
    }
}

// Kafelki, liczniki zakładek i „wpływ kolejki” z odpowiedzi serwera (te same pola co przy renderowaniu strony).
function renderSummary({ counts, impact }) {
    // Licznik oczekujących jest w zakładce i w przycisku „Zatwierdź wszystkie” – aktualizujemy każde wystąpienie.
    Object.entries(counts).forEach(([tab, { count }]) => {
        captain.querySelectorAll(`[data-count="${tab}"]`).forEach((element) => {
            element.textContent = count;
        });
    });
    const approveAll = captain.querySelector('[data-approve-all]');
    if (approveAll) {
        approveAll.hidden = counts.pending.count === 0;
    }
    setOut('pending-count', counts.pending.count);
    setOut('pending-km', captainMessage('msgPendingKm', { km: formatNumber(counts.pending.km) }));
    setOut('approved-km', formatNumber(impact.approvedKm));
    setOut('approved-count', captainMessage('msgApprovedCount', { count: counts.approved.count }));
    setOut('rejected-count', counts.rejected.count);

    const hasPending = impact.pendingKm > 0;
    captain.querySelectorAll('[data-impact]').forEach((element) => {
        element.hidden = !hasPending;
    });
    captain.querySelector('[data-impact-none]').hidden = hasPending;
    setOut('impact-percent', `${formatNumber(impact.nowPercent)}% → ${formatNumber(impact.afterPercent)}%`);
    setOut('impact-now', captainMessage('msgNow', { km: formatNumber(impact.approvedKm) }));
    setOut('impact-queue', captainMessage('msgInQueue', { km: formatNumber(impact.pendingKm) }));
    if (impact.nextName !== null) {
        setOut('impact-next', impact.nextLeftKm <= 0
            ? captainMessage('msgPasses', { name: impact.nextName })
            : captainMessage('msgNext', { name: impact.nextName, km: formatNumber(impact.nextLeftKm) }));
    }
    captain.querySelector('[data-bar-now]').setAttribute('width', impact.nowPercent.toFixed(2));
    const delta = captain.querySelector('[data-bar-delta]');
    delta.setAttribute('x', impact.nowPercent.toFixed(2));
    delta.setAttribute('width', (impact.afterPercent - impact.nowPercent).toFixed(2));
}

function setBusy(card, form, isBusy) {
    card.classList.toggle('is-busy', isBusy);
    card.setAttribute('aria-busy', String(isBusy));
    form.querySelectorAll('button').forEach((button) => {
        button.disabled = isBusy;
    });
}

// Karta po decyzji: status jak na rozpatrzonych kartach, a po chwili znika z kolejki „do zatwierdzenia”.
const SHOW_RESULT_MS = 1200;
const LEAVE_MS = 300;   // = czas przejścia .is-leaving w CSS

function markDecided(card, form, data) {
    const approved = data.status === 'APPROVED';
    card.classList.replace('activity-card--pending', approved ? 'activity-card--approved' : 'activity-card--rejected');
    card.classList.remove('is-busy');
    card.setAttribute('aria-busy', 'false');
    form.querySelector('[data-decision-message]').textContent = data.message;
    form.querySelectorAll('button').forEach((button) => button.remove());

    // Zwykłe opóźnienie zamiast transitionend: przy prefers-reduced-motion przejścia nie ma i zdarzenie nie przyjdzie.
    setTimeout(() => {
        card.classList.add('is-leaving');
        setTimeout(() => {
            card.remove();
            captain.querySelector('[data-queue-empty]').hidden = captain.querySelector('.activity-card') !== null;
        }, LEAVE_MS);
    }, SHOW_RESULT_MS);
}

async function decide(form, decision) {
    const card = form.closest('.activity-card');
    const message = form.querySelector('[data-decision-message]');
    setBusy(card, form, true);
    message.textContent = captain.dataset.msgBusy;

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-Token': form.elements._csrf.value,
            },
            body: JSON.stringify({ decision }),
        });
        const data = await response.json();

        if (response.ok) {
            markDecided(card, form, data);
            renderSummary(data);
            return;
        }
        // 409: ktoś już rozpatrzył tę aktywność – przyciski nie mają sensu, zostaje sam komunikat.
        message.textContent = data.error ?? captain.dataset.msgError;
        if (response.status === 409) {
            form.querySelectorAll('button').forEach((button) => button.remove());
            card.classList.remove('is-busy');
            return;
        }
    } catch {
        message.textContent = captain.dataset.msgError;
    }
    setBusy(card, form, false);
}

// „Zatwierdź wszystkie”: po potwierdzeniu jedno żądanie, serwer zatwierdza całą kolejkę w jednej transakcji.
async function approveAll(form) {
    const button = form.querySelector('button');
    const message = form.querySelector('[data-approve-all-message]');
    const cards = [...captain.querySelectorAll('.activity-card--pending')];
    button.disabled = true;
    message.textContent = captain.dataset.msgBusy;
    cards.forEach((card) => card.classList.add('is-busy'));

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            headers: { Accept: 'application/json', 'X-CSRF-Token': form.elements._csrf.value },
        });
        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.error);
        }

        cards.forEach((card) => markDecided(card, card.querySelector('[data-decision-form]'), { status: 'APPROVED', message: data.message }));
        message.textContent = data.message;
        renderSummary(data);
    } catch {
        message.textContent = captain.dataset.msgError;
        cards.forEach((card) => card.classList.remove('is-busy'));
    }
    button.disabled = false;
}

if (captain) {
    // Delegacja: jeden nasłuchiwacz na całą kolejkę; event.submitter mówi, który przycisk kliknięto.
    captain.addEventListener('submit', (event) => {
        const decisionForm = event.target.closest('[data-decision-form]');
        const approveAllForm = event.target.closest('[data-approve-all]');
        if (!decisionForm && !approveAllForm) {
            return;
        }
        event.preventDefault();

        if (decisionForm) {
            decide(decisionForm, event.submitter?.value ?? '');
            return;
        }
        const count = captain.querySelector('[data-count="pending"]').textContent;
        // Masowa decyzja jest nieodwracalna z poziomu panelu – zwykłe potwierdzenie przeglądarki.
        if (window.confirm(fillTemplate(approveAllForm.dataset.confirm, { count }))) {
            approveAll(approveAllForm);
        }
    });
}
