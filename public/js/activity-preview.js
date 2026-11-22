// Podgląd treningu na trasie: przy każdej zmianie formularza liczy km trasy (ten sam wzór co
// ActivityService::routeKm – ostatecznie i tak liczy serwer), postęp wyprawy, najbliższy punkt i miejsce w rankingu.
const preview = document.querySelector('[data-route-preview]');
const previewForm = document.querySelector('[data-activity-form]');

// Szablon tekstu z atrybutu data-msg-* panelu (fillTemplate i formatNumber są w format.js).
function message(name, params) {
    return fillTemplate(preview.dataset[name], params);
}

function toNumber(value) {
    const normalized = value.trim().replace(',', '.');
    return normalized === '' || Number.isNaN(Number(normalized)) ? 0 : Math.max(0, Number(normalized));
}

function wholeNumber(value) {
    return /^\d+$/.test(value.trim()) ? Number(value) : 0;
}

/** @returns {{routeKm: number, amount: number, unit: string, factor: number, id: string}|null} */
function readWorkout() {
    const discipline = previewForm.querySelector('input[name="discipline"]:checked');
    if (!discipline) {
        return null;
    }
    const unit = discipline.dataset.unit;
    const factor = Number(discipline.dataset.factor);
    const minutes = wholeNumber(previewForm.elements.hours.value) * 60 + wholeNumber(previewForm.elements.minutes.value);
    const amount = unit === 'min' ? minutes : toNumber(previewForm.elements.distance.value);
    // Zaokrąglenie do 0,01 km jak round(..., 2) w PHP.
    return { routeKm: Math.round(amount * factor * 100) / 100, amount, unit, factor, id: discipline.value };
}

// Miejsce = 1 + liczba osób z większym dystansem – ta sama reguła co ExpeditionService::memberStats.
function positionFor(myKm, others) {
    return others.filter((km) => km > myKm).length + 1;
}

function output(name) {
    return preview.querySelector(`[data-out="${name}"]`);
}

function setText(name, text) {
    const element = output(name);
    if (element) {
        element.value = text;
    }
}

function update() {
    const workout = readWorkout();
    const added = workout?.routeKm ?? 0;
    const approved = Number(preview.dataset.approved);
    const total = Number(preview.dataset.total);
    const after = approved + added;
    const percent = (km) => (total > 0 ? Math.min(100, (km / total) * 100) : 0);

    setText('added', `+${formatNumber(added)}`);
    setText('formula', workout && workout.amount > 0
        ? message(workout.unit === 'min' ? 'msgFormulaMin' : 'msgFormulaKm', {
            amount: formatNumber(workout.amount, workout.unit === 'min' ? 0 : 2),
            factor: formatNumber(workout.factor, 2),
        })
        : '');

    setText('percent', added > 0
        ? `${formatNumber(percent(approved))}% → ${formatNumber(percent(after))}%`
        : `${formatNumber(percent(approved))}%`);
    setText('after', message('msgAfter', { km: formatNumber(after) }));
    preview.querySelector('[data-bar-delta]').setAttribute('width', (percent(after) - percent(approved)).toFixed(2));

    const nextKm = Number(preview.dataset.nextKm);
    if (preview.dataset.nextName !== '') {
        setText('next', after >= nextKm
            ? message('msgPasses', { name: preview.dataset.nextName })
            : message('msgNext', { name: preview.dataset.nextName, km: formatNumber(nextKm - after) }));
    }

    if (preview.dataset.position !== '') {
        const members = JSON.parse(preview.dataset.members);
        const me = members.find((member) => member.me);
        const others = members.filter((member) => !member.me).map((member) => member.km);
        const myAfter = me.km + added;
        const from = Number(preview.dataset.position);
        const to = positionFor(myAfter, others);
        setText('rank', to < from
            ? message('msgRankUp', { from, to })
            : message('msgRankSame', { position: from }));

        const above = others.filter((km) => km > myAfter);
        const below = others.filter((km) => km < myAfter);
        setText('gap', above.length > 0
            ? message('msgBehind', { km: formatNumber(Math.min(...above) - myAfter) })
            : (below.length > 0 ? message('msgLead', { km: formatNumber(myAfter - Math.max(...below)) }) : ''));
    }

    // Podświetlenie przelicznika wybranej dyscypliny.
    preview.querySelectorAll('[data-factor-for]').forEach((row) => {
        row.classList.toggle('is-selected', row.dataset.factorFor === workout?.id);
    });
}

if (preview && previewForm) {
    // Jedno nasłuchiwanie na cały formularz: input dla pól tekstowych, change dla kafelków dyscyplin.
    previewForm.addEventListener('input', update);
    previewForm.addEventListener('change', update);
    update();
}
