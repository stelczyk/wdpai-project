// Panel użytkowników (admin): lista z GET /api/users, blokada przez PATCH, usuwanie przez DELETE.
// Wiersze powstają z <template> i są wypełniane przez textContent – imię czy e-mail nigdy nie są HTML-em.
// Komunikaty PL/EN przychodzą z atrybutów data-msg-* (fillTemplate z format.js podstawia {name}, {count}).
const panel = document.querySelector('[data-users-panel]');

if (panel) {
    const msg = panel.dataset;
    const list = panel.querySelector('[data-users-list]');
    const status = panel.querySelector('[data-users-status]');
    const refreshButton = panel.querySelector('[data-users-refresh]');
    const rowTemplate = panel.querySelector('[data-user-row]');
    const adminId = Number(msg.adminId);

    const setStatus = (text, isError = false) => {
        status.textContent = text;
        status.classList.toggle('is-error', isError);
    };

    // fetchJson (api.js) + komunikat błędu: sieć → tekst z data-msg-network, HTTP → {error} z API albo kod.
    const request = async (url, options = {}) => {
        let result;
        try {
            result = await fetchJson(url, { csrf: msg.csrfToken, ...options });
        } catch {
            throw new Error(msg.msgNetwork);
        }
        if (!result.ok) {
            throw new Error(result.data?.error ?? `HTTP ${result.status}`);
        }
        return result.data;
    };

    const initials = (name) => name.trim().split(/\s+/).slice(0, 2).map((part) => part[0]).join('').toUpperCase();

    // Stan blokady w wierszu: odznaka, ikona kłódki i opis przycisku dla czytników ekranu.
    const applyBlocked = (row, isBlocked) => {
        const name = row.dataset.name;
        const button = row.querySelector('[data-action="block"]');
        row.dataset.blocked = String(isBlocked);
        row.classList.toggle('is-blocked', isBlocked);
        row.querySelector('[data-field="state"]').textContent = isBlocked ? msg.msgBlocked : msg.msgActive;
        const label = fillTemplate(isBlocked ? msg.msgUnblock : msg.msgBlock, { name });
        button.setAttribute('aria-label', label);
        button.title = label;
    };

    const createRow = (user) => {
        const row = rowTemplate.content.firstElementChild.cloneNode(true);
        const field = (name) => row.querySelector(`[data-field="${name}"]`);
        const isSelf = user.id === adminId;

        row.dataset.userId = String(user.id);
        row.dataset.name = user.name;
        field('initials').textContent = initials(user.name);
        field('name').textContent = user.name;
        field('email').textContent = user.email;
        field('role').textContent = msg[`msgRole${user.role.charAt(0)}${user.role.slice(1).toLowerCase()}`] ?? user.role;
        field('you').hidden = !isSelf;
        field('captain').hidden = !user.isCaptain;

        const deleteButton = row.querySelector('[data-action="delete"]');
        const deleteLabel = fillTemplate(msg.msgDelete, { name: user.name });
        deleteButton.setAttribute('aria-label', deleteLabel);
        deleteButton.title = deleteLabel;

        // Własnego konta nie da się zablokować ani usunąć, kapitana – usunąć (serwer i tak to sprawdza: 422/409).
        row.querySelector('[data-action="block"]').disabled = isSelf;
        deleteButton.disabled = isSelf || user.isCaptain;

        applyBlocked(row, user.isBlocked);
        return row;
    };

    const showCount = () => {
        const count = list.children.length;
        setStatus(count === 0 ? msg.msgEmpty : fillTemplate(msg.msgCount, { count }));
    };

    const loadUsers = async () => {
        setStatus(msg.msgLoading);
        list.setAttribute('aria-busy', 'true');
        refreshButton.disabled = true;
        try {
            const { data } = await request(msg.endpoint);
            list.replaceChildren(...data.map(createRow));
            showCount();
        } catch (error) {
            setStatus(error.message, true);
        } finally {
            list.setAttribute('aria-busy', 'false');
            refreshButton.disabled = false;
        }
    };

    const toggleBlocked = async (row, button) => {
        const isBlocked = row.dataset.blocked !== 'true';
        button.disabled = true;
        setStatus(msg.msgBusy);
        try {
            const { data } = await request(`${msg.endpoint}/${row.dataset.userId}/block`, {
                method: 'PATCH',
                body: { isBlocked },
            });
            applyBlocked(row, data.isBlocked);
            setStatus(fillTemplate(data.isBlocked ? msg.msgDoneBlocked : msg.msgDoneUnblocked, { name: row.dataset.name }));
        } catch (error) {
            setStatus(error.message, true);
        } finally {
            button.disabled = false;
        }
    };

    const deleteUser = async (row, button) => {
        const name = row.dataset.name;
        if (!window.confirm(fillTemplate(msg.msgConfirm, { name }))) {
            return;
        }
        button.disabled = true;
        setStatus(msg.msgBusy);
        try {
            await request(`${msg.endpoint}/${row.dataset.userId}`, { method: 'DELETE' });
            // Fokus nie może zniknąć razem z wierszem – wraca na przycisk odświeżania.
            row.remove();
            refreshButton.focus();
            setStatus(fillTemplate(msg.msgDoneDeleted, { name }));
        } catch (error) {
            button.disabled = false;
            setStatus(error.message, true);
        }
    };

    // Jeden nasłuch na całą listę (delegacja) – działa też dla wierszy dodanych po odświeżeniu.
    list.addEventListener('click', (event) => {
        const button = event.target.closest('button[data-action]');
        if (!button || button.disabled) {
            return;
        }
        const row = button.closest('[data-user-id]');
        (button.dataset.action === 'block' ? toggleBlocked : deleteUser)(row, button);
    });

    refreshButton.addEventListener('click', loadUsers);
    loadUsers();
}
