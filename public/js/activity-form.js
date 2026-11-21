// Formularz „Dodaj aktywność”: walidacja po opuszczeniu pola i przed wysłaniem (te same reguły
// co ActivityService – serwer i tak sprawdza wszystko ponownie), ukrywanie dystansu dla siłowni.
const activityForm = document.querySelector('[data-activity-form]');

// Zmiana wyprawy w selekcie od razu przeładowuje formularz (bez JS działa przycisk „Zmień”).
document.querySelector('[data-auto-submit]')?.addEventListener('change', (event) => {
    event.currentTarget.form.requestSubmit();
});

const DISTANCE_MAX = 300;
const DURATION_MAX = 1440;

// „5,2” i „5.2” to ta sama liczba – w PL piszemy przecinek.
function toNumber(value) {
    const normalized = value.trim().replace(',', '.');
    return normalized === '' || Number.isNaN(Number(normalized)) ? null : Number(normalized);
}

function isWholeNumberIn(value, min, max) {
    return /^\d+$/.test(value) && Number(value) >= min && Number(value) <= max;
}

function selectedDiscipline() {
    return activityForm.querySelector('input[name="discipline"]:checked');
}

function usesMinutes() {
    return selectedDiscipline()?.dataset.unit === 'min';
}

function todayIso() {
    const now = new Date();
    return new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 10);
}

// Każda reguła zwraca komunikat błędu albo null. Klucz = id elementu z komunikatem (<klucz>-error).
const rules = {
    discipline: () => (selectedDiscipline() ? null : activityForm.dataset.msgDiscipline),

    distance: () => {
        if (usesMinutes()) {
            return null;
        }
        const km = toNumber(activityForm.elements.distance.value);
        return km !== null && km > 0 && km <= DISTANCE_MAX ? null : activityForm.dataset.msgDistance;
    },

    duration: () => {
        const hours = activityForm.elements.hours.value.trim() || '0';
        const minutes = activityForm.elements.minutes.value.trim() || '0';
        if (!isWholeNumberIn(hours, 0, 24) || !isWholeNumberIn(minutes, 0, 59)) {
            return activityForm.dataset.msgDuration;
        }
        const total = Number(hours) * 60 + Number(minutes);
        return total >= 1 && total <= DURATION_MAX ? null : activityForm.dataset.msgDuration;
    },

    date: () => {
        const input = activityForm.elements.date;
        if (!/^\d{4}-\d{2}-\d{2}$/.test(input.value)) {
            return activityForm.dataset.msgDate;
        }
        if (input.value > todayIso()) {
            return activityForm.dataset.msgDateFuture;
        }
        return input.value < input.min ? activityForm.dataset.msgDateStart : null;
    },

    'heart-rate': () => {
        const input = activityForm.elements.heart_rate;
        const value = input.value.trim();
        return value === '' || isWholeNumberIn(value, Number(input.min), Number(input.max)) ? null : activityForm.dataset.msgHeartRate;
    },

    note: () => {
        const input = activityForm.elements.note;
        return input.value.trim().length <= Number(input.maxLength) ? null : activityForm.dataset.msgNote;
    },

    // Wstępna kontrola zdjęcia (typ zgłoszony przez przeglądarkę i rozmiar) – prawdziwy MIME sprawdza serwer.
    photo: () => {
        const input = activityForm.elements.photo;
        const file = input.files[0];
        if (!file) {
            return null;
        }
        if (!input.accept.split(',').includes(file.type)) {
            return activityForm.dataset.msgPhotoType;
        }
        return file.size <= Number(input.dataset.maxBytes) ? null : activityForm.dataset.msgPhotoSize;
    },
};

// Pola, które dana reguła oznacza jako błędne (aria-invalid) i na które przenosimy fokus.
const fieldsOf = {
    discipline: () => [...activityForm.elements.discipline],
    distance: () => [activityForm.elements.distance],
    duration: () => [activityForm.elements.hours, activityForm.elements.minutes],
    date: () => [activityForm.elements.date],
    'heart-rate': () => [activityForm.elements.heart_rate],
    note: () => [activityForm.elements.note],
    photo: () => [activityForm.elements.photo],
};

function check(key) {
    const message = rules[key]();
    activityForm.querySelector(`#${key}-error`).textContent = message ?? '';
    fieldsOf[key]().forEach((field) => {
        if (message === null) {
            field.removeAttribute('aria-invalid');
        } else {
            field.setAttribute('aria-invalid', 'true');
        }
    });
    return message === null;
}

// Pole na pole, a nie tylko przy wysyłce: błąd widać od razu po opuszczeniu pola.
function ruleFor(element) {
    const byName = { distance: 'distance', hours: 'duration', minutes: 'duration', date: 'date', heart_rate: 'heart-rate', note: 'note' };
    return byName[element.name] ?? null;
}

function toggleDistance() {
    const field = activityForm.querySelector('[data-distance-field]');
    const hidden = usesMinutes();
    field.hidden = hidden;
    activityForm.elements.distance.disabled = hidden;   // wyłączone pole nie jest wysyłane
    if (hidden) {
        check('distance');
    }
}

// Miniatura i nazwa wybranego zdjęcia; adres blob: zwalniamy, gdy przestaje być potrzebny.
let photoUrl = null;

function showPhoto() {
    const input = activityForm.elements.photo;
    const preview = activityForm.querySelector('[data-photo-preview]');
    const file = input.files[0];
    if (photoUrl) {
        URL.revokeObjectURL(photoUrl);
        photoUrl = null;
    }

    if (!check('photo') || !file) {
        preview.hidden = true;
        return;
    }
    photoUrl = URL.createObjectURL(file);
    preview.querySelector('[data-photo-thumb]').src = photoUrl;
    preview.querySelector('[data-photo-name]').textContent = file.name;   // textContent – nazwa pliku to dane od użytkownika
    preview.hidden = false;
}

function removePhoto() {
    activityForm.elements.photo.value = '';
    showPhoto();
    activityForm.elements.photo.focus();
}

function handleSubmit(event) {
    const failed = Object.keys(rules).filter((key) => !check(key));
    if (failed.length > 0) {
        event.preventDefault();
        fieldsOf[failed[0]]()[0].focus();
    }
}

if (activityForm) {
    activityForm.addEventListener('focusout', (event) => {
        const key = ruleFor(event.target);
        // Godziny → minuty to wciąż to samo pole „czas trwania” – sprawdzamy dopiero po wyjściu z obu.
        const staysInDuration = key === 'duration' && event.relatedTarget?.closest('.duration-field');
        if (key !== null && !staysInDuration) {
            check(key);
        }
    });

    activityForm.addEventListener('change', (event) => {
        if (event.target.name === 'discipline') {
            toggleDistance();
            check('discipline');
        }
        if (event.target.name === 'photo') {
            showPhoto();
        }
    });

    activityForm.querySelector('[data-photo-remove]').addEventListener('click', removePhoto);

    activityForm.addEventListener('submit', handleSubmit);
    toggleDistance();
}
