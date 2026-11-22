// Ranking bez przeładowania: zakładki zakresu i „Odśwież” pobierają ten sam adres przez fetch
// (Accept: application/json) i podmieniają tabelę, kafelki oraz bilans. Bez JS zakładki są zwykłymi linkami.
const ranking = document.querySelector('[data-ranking]');

function rankingMessage(name, params) {
    return fillTemplate(ranking.dataset[name], params);
}

function out(name) {
    return ranking.querySelector(`[data-out="${name}"]`);
}

// Wiersz z <template> (ten sam partial PHP co wiersze serwera); dane z bazy tylko przez textContent.
function buildRow(row) {
    const tr = ranking.querySelector('[data-row-template]').content.firstElementChild.cloneNode(true);
    const field = (name) => tr.querySelector(`[data-field="${name}"]`);

    tr.classList.toggle('ranking-row--me', row.isMe);
    if (row.isMe) {
        tr.setAttribute('aria-current', 'true');
    }
    field('position').textContent = row.position;
    if (row.km > 0 && row.position <= 3) {
        field('position').classList.add(`ranking-medal--${row.position}`);
    }
    field('initials').textContent = row.initials;
    field('initials').classList.toggle('activity-avatar--me', row.isMe);
    field('name').textContent = row.name;
    field('you').hidden = !row.isMe;
    field('captain').hidden = !row.isCaptain;
    field('workouts').textContent = row.workouts;
    field('km').textContent = `${formatNumber(row.km)} km`;
    field('share').textContent = rankingMessage('msgShare', { percent: formatNumber(row.share, 0) });

    row.disciplines.forEach((discipline) => {
        const item = document.createElement('li');
        const dot = document.createElement('span');
        item.className = `discipline-option--${discipline.style}`;
        dot.className = 'dot';
        dot.setAttribute('aria-hidden', 'true');
        item.append(dot, discipline.label);
        field('disciplines').append(item);
    });

    return tr;
}

function render(data, link) {
    ranking.querySelector('.ranking-table tbody').replaceChildren(...data.rows.map(buildRow));
    ranking.querySelector('[data-ranking-empty]').hidden = data.leader !== null;

    out('leader').textContent = data.leader?.name ?? '—';
    out('leader-km').textContent = data.leader ? `${formatNumber(data.leader.km)} km` : ranking.dataset.msgLeaderNone;
    out('average').textContent = formatNumber(data.averageKm);

    // Bilans tylko dla członka wyprawy (admin go nie ma).
    if (data.me && out('my-position')) {
        out('my-position').textContent = rankingMessage('msgPosition', { position: data.me.position });
        out('my-gap').textContent = data.me.leadKm !== null
            ? rankingMessage('msgLead', { km: formatNumber(data.me.leadKm) })
            : (data.me.behindKm !== null ? rankingMessage('msgBehind', { km: formatNumber(data.me.behindKm) }) : '');
    }

    const hint = ranking.querySelector('[data-stage-hint]');
    if (hint) {
        hint.hidden = data.range !== 'stage';
    }
    ranking.querySelectorAll('[data-range]').forEach((tab) => {
        if (tab === link) {
            tab.setAttribute('aria-current', 'page');
        } else {
            tab.removeAttribute('aria-current');
        }
    });
    out('table-title').textContent = rankingMessage('msgTableTitle', { range: link.textContent.trim() });
}

function setLoading(isLoading) {
    const board = ranking.querySelector('[data-ranking-board]');
    board.setAttribute('aria-busy', String(isLoading));
    ranking.querySelector('[data-ranking-refresh]').disabled = isLoading;
}

async function loadRange(link, { push }) {
    const error = ranking.querySelector('[data-ranking-error]');
    setLoading(true);
    error.textContent = '';

    try {
        const response = await fetch(link.href, { headers: { Accept: 'application/json' } });
        // Sesja wygasła – pełne przejście pod ten adres pokaże stronę logowania / błędu.
        if (response.status === 401) {
            window.location.assign(link.href);
            return;
        }
        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.error ?? response.statusText);
        }

        render(data, link);
        if (push) {
            history.pushState({ range: data.range }, '', link.href);
        }
    } catch {
        // Błąd sieci albo serwera: komunikat, a tabela zostaje taka, jaka była.
        error.textContent = ranking.dataset.msgError;
    } finally {
        setLoading(false);
    }
}

function currentLink() {
    return ranking.querySelector('[data-range][aria-current="page"]');
}

if (ranking) {
    ranking.querySelector('.exp-tabs').addEventListener('click', (event) => {
        const link = event.target.closest('[data-range]');
        // Ctrl/Cmd+klik itp. – zostawiamy przeglądarce (nowa karta).
        if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.button !== 0) {
            return;
        }
        event.preventDefault();
        if (link !== currentLink()) {
            loadRange(link, { push: true });
        }
    });

    const refresh = ranking.querySelector('[data-ranking-refresh]');
    refresh.hidden = false;
    refresh.addEventListener('click', () => loadRange(currentLink(), { push: false }));

    // Wstecz/Dalej: wczytujemy zakres z adresu, bez dokładania kolejnego wpisu w historii.
    window.addEventListener('popstate', () => {
        const range = new URLSearchParams(window.location.search).get('range') ?? 'all';
        const link = ranking.querySelector(`[data-range="${CSS.escape(range)}"]`);
        if (link) {
            loadRange(link, { push: false });
        }
    });
}
