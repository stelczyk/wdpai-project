// Formularz tworzenia wyprawy: dodawanie/usuwanie punktów trasy, długość trasy na żywo,
// włączanie przeliczników dyscyplin i walidacja przed wysłaniem (serwer i tak sprawdza to samo).
const form = document.querySelector('[data-expedition-form]');
const list = form?.querySelector('[data-checkpoints]');
const template = form?.querySelector('[data-checkpoint-template]');
const MIN_CHECKPOINTS = 2;

function checkpointRows() {
    return [...list.querySelectorAll('.checkpoint-row')];
}

function readKm(input) {
    return input.value === '' ? null : Number(input.value);
}

// Długość trasy = km ostatniego punktu.
function updateRouteTotal() {
    const kms = checkpointRows().map((row) => readKm(row.querySelector('[data-checkpoint-km]')));
    const last = kms.at(-1);
    form.querySelector('[data-route-total]').value = last === null ? '0' : last.toLocaleString(document.documentElement.lang);
}

// Start i meta muszą zostać – przy dwóch punktach przyciski „Usuń” są wyłączone.
function updateRemoveButtons() {
    const canRemove = checkpointRows().length > MIN_CHECKPOINTS;
    list.querySelectorAll('[data-remove-checkpoint]').forEach((button) => {
        button.disabled = !canRemove;
    });
}

function addCheckpoint() {
    const row = template.content.firstElementChild.cloneNode(true);
    list.append(row);
    row.querySelector('input').focus();
    updateRemoveButtons();
    updateRouteTotal();
}

function removeCheckpoint(button) {
    button.closest('.checkpoint-row').remove();
    updateRemoveButtons();
    updateRouteTotal();
}

function toggleFactor(checkbox) {
    const factor = checkbox.closest('.discipline-row').querySelector('.factor input');
    factor.disabled = !checkbox.checked;
}

/** @returns {string|null} komunikat pierwszego błędu albo null */
function validate() {
    const rows = checkpointRows();
    let previousKm = null;

    for (const row of rows) {
        const name = row.querySelector('input[type="text"]').value.trim();
        const km = readKm(row.querySelector('[data-checkpoint-km]'));
        if (name === '' || km === null) {
            return form.dataset.msgCheckpointName;
        }
        if (previousKm !== null && km <= previousKm) {
            return form.dataset.msgCheckpointOrder;
        }
        previousKm = km;
    }

    const start = form.elements.start_date.value;
    const end = form.elements.end_date.value;
    if (start && end && end < start) {
        return form.dataset.msgDates;
    }

    if (!form.querySelector('[data-discipline-toggle]:checked')) {
        return form.dataset.msgDisciplines;
    }

    return null;
}

function handleSubmit(event) {
    const error = validate();
    const message = form.querySelector('[data-form-error]');
    message.textContent = error ?? '';

    if (error !== null) {
        event.preventDefault();
        message.scrollIntoView({ block: 'center', behavior: 'smooth' });
    }
}

if (form) {
    form.querySelector('[data-add-checkpoint]').addEventListener('click', addCheckpoint);

    // Delegacja zdarzeń: jeden nasłuchiwacz obsługuje też wiersze dodane później.
    list.addEventListener('click', (event) => {
        const button = event.target.closest('[data-remove-checkpoint]');
        if (button) {
            removeCheckpoint(button);
        }
    });
    list.addEventListener('input', updateRouteTotal);

    form.querySelectorAll('[data-discipline-toggle]').forEach((checkbox) => {
        checkbox.addEventListener('change', () => toggleFactor(checkbox));
    });

    form.addEventListener('submit', handleSubmit);

    updateRemoveButtons();
    updateRouteTotal();
}
